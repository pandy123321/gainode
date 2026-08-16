<?php
declare(strict_types=1);

namespace support\arbitrage\client;

use Throwable;
use support\arbitrage\exception\ArbitrageException;
use support\arbitrage\http\ArrayNameMap;
use support\arbitrage\http\ArbHttpClient;
use support\arbitrage\http\EntityNameMapStore;
use support\arbitrage\math\ArbitrageCalculator;

/**
 * BetBurger 套利信号 API 客户端（纯拉取 + 归一化，不入库）。
 *
 * 配置键（可直接传 config('arbitrage')['betburger']，或完整 arbitrage 配置）：
 * - base_url / access_token / search_filter / per_page / timeout
 * - bookmaker_names / market_names（可选，展示名映射）
 */
final class BetBurgerClient
{
    private ArbHttpClient $http;
    private ArbitrageCalculator $calculator;
    private ArrayNameMap $nameMap;

    private string $baseUrl;
    private string $accessToken;
    private string $searchFilter;
    private int $perPage;
    private int $timeout;

    /** @param array<string,mixed> $config */
    public function __construct(array $config = [])
    {
        $bb = isset($config['betburger']) && is_array($config['betburger'])
            ? $config['betburger']
            : $config;

        $this->baseUrl      = rtrim((string) ($bb['base_url'] ?? ''), '/');
        $this->accessToken  = (string) ($bb['access_token'] ?? '');
        $this->searchFilter = (string) ($bb['search_filter'] ?? '');
        $this->perPage      = (int) ($bb['per_page'] ?? 50);
        $this->timeout      = (int) ($bb['timeout'] ?? 15);

        $this->http        = new ArbHttpClient((int) ($config['http_max_attempts'] ?? $bb['http_max_attempts'] ?? 3));
        $this->calculator  = new ArbitrageCalculator();
        $this->nameMap     = EntityNameMapStore::toArrayNameMap();
        // 允许构造参数覆盖（测试/手工注入）
        $overrideBm = $config['bookmaker_names'] ?? $bb['bookmaker_names'] ?? null;
        $overrideMk = $config['market_names'] ?? $bb['market_names'] ?? null;
        if ((is_array($overrideBm) && $overrideBm !== []) || (is_array($overrideMk) && $overrideMk !== [])) {
            $loaded = EntityNameMapStore::load();
            $this->nameMap = new ArrayNameMap(
                (is_array($overrideBm) && $overrideBm !== []) ? $overrideBm : $loaded['bookmakers'],
                (is_array($overrideMk) && $overrideMk !== []) ? $overrideMk : $loaded['markets']
            );
        }
    }

    /** 从项目 config/arbitrage.php 构建。 */
    public static function fromConfig(?array $arbitrageConfig = null): self
    {
        $conf = $arbitrageConfig ?? (function_exists('config') ? (array) config('arbitrage', []) : []);
        return new self($conf);
    }

    /**
     * 拉取 BetBurger 原始 JSON（arbs + bets）。
     *
     * @return array<string,mixed>
     */
    public function fetchRaw(?string $searchFilter = null, ?int $perPage = null): array
    {
        $this->assertConfigured();

        $response = $this->http->postForm($this->baseUrl . '/api/v1/arbs/pro_search', [
            'access_token'    => $this->accessToken,
            'search_filter[]' => $searchFilter ?? $this->searchFilter,
            'per_page'        => $perPage ?? $this->perPage,
            'grouped'         => 'true',
            'auto_update'     => 'true',
        ], [], $this->timeout);

        if ($response['status'] !== 200) {
            throw new ArbitrageException(
                'BETBURGER_HTTP_ERROR',
                'BetBurger returned non-200 status',
                ['status' => $response['status']]
            );
        }

        try {
            $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ArbitrageException('BETBURGER_INVALID_RESPONSE', 'BetBurger response is not valid JSON', [
                'message' => $e->getMessage(),
            ]);
        }

        if (!is_array($data)) {
            throw new ArbitrageException('BETBURGER_INVALID_RESPONSE', 'BetBurger response must be a JSON object');
        }

        return $data;
    }

    /**
     * 拉取并归一化为业务信号列表。
     *
     * @return list<array<string,mixed>>
     */
    public function fetchSignals(float $previewStake = 10000.0): array
    {
        // 常驻进程会缓存本实例：每次拉取前刷新映射，避免启动时 Redis/文件为空导致长期写入 Bookmaker#/Market#
        $this->nameMap = EntityNameMapStore::toArrayNameMap();
        return $this->normalize($this->fetchRaw(), $previewStake);
    }

    /**
     * 在当前搜索结果中按 event_id + arb_hash 定位一条信号（用于下单前刷新赔率）。
     *
     * @return array<string,mixed>|null
     */
    public function findSignal(int $eventId, string $arbHash, float $previewStake = 10000.0): ?array
    {
        if ($eventId <= 0 || $arbHash === '') {
            return null;
        }
        foreach ($this->fetchSignals($previewStake) as $signal) {
            if ((int) ($signal['event_id'] ?? 0) === $eventId
                && (string) ($signal['arb_hash'] ?? '') === $arbHash) {
                return $signal;
            }
        }
        return null;
    }

    /**
     * 将 BetBurger 响应归一化。
     *
     * 返回字段约定（与 v2 表 / 业务层对齐）：
     * - profit_rate: 小数（0.0120 = 1.20%）
     * - betburger_pct: API 原始百分比（1.20）
     * - leg1 / leg2: 嵌套结构，同时展开 leg1_* 扁平字段便于入库
     *
     * @param array<string,mixed> $response
     * @return list<array<string,mixed>>
     */
    public function normalize(array $response, float $previewStake = 10000.0): array
    {
        $bets = [];
        foreach (($response['bets'] ?? []) as $bet) {
            if (is_array($bet) && isset($bet['id'])) {
                $bets[(string) $bet['id']] = $bet;
            }
        }

        $signals = [];
        foreach (($response['arbs'] ?? []) as $arb) {
            if (!is_array($arb)) {
                continue;
            }
            $bet1 = $bets[(string) ($arb['bet1_id'] ?? '')] ?? null;
            $bet2 = $bets[(string) ($arb['bet2_id'] ?? '')] ?? null;
            if (!is_array($bet1) || !is_array($bet2)) {
                continue;
            }

            $odds1 = (float) ($bet1['koef'] ?? 0);
            $odds2 = (float) ($bet2['koef'] ?? 0);
            try {
                $calc = $this->calculator->calculate($odds1, $odds2, $previewStake);
            } catch (Throwable) {
                continue;
            }

            $bm1Id = (int) ($bet1['bookmaker_id'] ?? 0);
            $bm2Id = (int) ($bet2['bookmaker_id'] ?? 0);
            $m1Type = (int) ($bet1['market_and_bet_type'] ?? 0);
            $m2Type = (int) ($bet2['market_and_bet_type'] ?? 0);
            $m1Param = (float) ($bet1['market_and_bet_type_param'] ?? 0);
            $m2Param = (float) ($bet2['market_and_bet_type_param'] ?? 0);

            $leg1Market = $this->nameMap->market($m1Type, $m1Param);
            $leg2Market = $this->nameMap->market($m2Type, $m2Param);
            $leg1Book   = $this->nameMap->bookmaker($bm1Id);
            $leg2Book   = $this->nameMap->bookmaker($bm2Id);

            $arbId = (string) ($arb['id'] ?? '');
            $hash  = trim((string) ($arb['arb_hash'] ?? '')) ?: $arbId;
            $pct   = (float) ($arb['percent'] ?? 0);
            $stake1 = (float) $calc['stake1'];
            $stake2 = (float) $calc['stake2'];

            $signals[] = [
                'arb_id'        => $arbId,
                'arb_hash'      => $hash,
                'event_id'      => (int) ($arb['event_id'] ?? 0),
                'event_name'    => (string) ($arb['event_name'] ?? ''),
                'home'          => (string) ($arb['home'] ?? $bet1['home'] ?? ''),
                'away'          => (string) ($arb['away'] ?? $bet1['away'] ?? ''),
                'league'        => (string) ($arb['league'] ?? $bet1['league'] ?? ''),
                'started_at'    => (int) ($arb['started_at'] ?? 0),
                'is_live'       => (bool) ($arb['is_live'] ?? false),
                'current_score' => (string) ($bet1['current_score'] ?? $bet2['current_score'] ?? ''),
                'betburger_pct' => round($pct, 2),
                'profit_rate'   => round((float) $calc['profit_rate'], 4),
                'preview_stake' => round($previewStake, 2),
                'leg1_bookmaker_id' => $bm1Id,
                'leg1_bookmaker'    => $leg1Book,
                'leg1_market'       => $leg1Market,
                'leg1_odds'         => round($odds1, 2),
                'leg1_market_param' => $m1Param,
                'leg1_market_type'  => $m1Type,
                'leg1_stake'        => round($stake1, 2),
                'leg2_bookmaker_id' => $bm2Id,
                'leg2_bookmaker'    => $leg2Book,
                'leg2_market'       => $leg2Market,
                'leg2_odds'         => round($odds2, 2),
                'leg2_market_param' => $m2Param,
                'leg2_market_type'  => $m2Type,
                'leg2_stake'        => round($stake2, 2),
                'leg1' => [
                    'bookmaker_id' => $bm1Id,
                    'bookmaker'    => $leg1Book,
                    'market'       => $leg1Market,
                    'odds'         => round($odds1, 2),
                    'market_param' => $m1Param,
                    'market_type'  => $m1Type,
                    'stake'        => round($stake1, 2),
                ],
                'leg2' => [
                    'bookmaker_id' => $bm2Id,
                    'bookmaker'    => $leg2Book,
                    'market'       => $leg2Market,
                    'odds'         => round($odds2, 2),
                    'market_param' => $m2Param,
                    'market_type'  => $m2Type,
                    'stake'        => round($stake2, 2),
                ],
                'raw' => $arb,
            ];
        }

        return $signals;
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '' || $this->accessToken === '' || $this->searchFilter === '') {
            throw new ArbitrageException(
                'BETBURGER_CONFIG_MISSING',
                'BetBurger configuration is incomplete (base_url / access_token / search_filter)'
            );
        }
    }
}
