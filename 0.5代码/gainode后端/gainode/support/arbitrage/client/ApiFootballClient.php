<?php
declare(strict_types=1);

namespace support\arbitrage\client;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use support\arbitrage\exception\ArbitrageException;
use support\arbitrage\http\ArbHttpClient;

/**
 * API-Football 比赛数据客户端（纯拉取 + 归一化，不入库）。
 *
 * 配置键（可直接传 config('arbitrage')['api_football']，或完整 arbitrage 配置）：
 * - base_url / api_key / timeout
 */
final class ApiFootballClient
{
    private ArbHttpClient $http;

    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    /** @param array<string,mixed> $config */
    public function __construct(array $config = [])
    {
        $af = isset($config['api_football']) && is_array($config['api_football'])
            ? $config['api_football']
            : $config;

        $this->baseUrl = rtrim((string) ($af['base_url'] ?? 'https://v3.football.api-sports.io'), '/');
        $this->apiKey  = (string) ($af['api_key'] ?? $config['api_football_api_key'] ?? '');
        $this->timeout = (int) ($af['timeout'] ?? $config['api_football_timeout'] ?? 20);

        $this->http = new ArbHttpClient((int) ($config['http_max_attempts'] ?? $af['http_max_attempts'] ?? 3));
    }

    /** 从项目 config/arbitrage.php 构建。 */
    public static function fromConfig(?array $arbitrageConfig = null): self
    {
        $conf = $arbitrageConfig ?? (function_exists('config') ? (array) config('arbitrage', []) : []);
        return new self($conf);
    }

    /**
     * 按查询参数拉取 fixtures（透传 API-Football /fixtures）。
     *
     * 常用 query：
     * - ['live' => 'all']              滚球
     * - ['date' => '2026-07-23']        某日
     * - ['id' => 123456]               单场
     * - ['from'=>..,'to'=>..,'league'=>..] 区间
     *
     * @param array<string,scalar> $query
     * @return list<array<string,mixed>>
     */
    public function fetchFixtures(array $query = ['live' => 'all']): array
    {
        return $this->normalize($this->fetchRaw($query));
    }

    /** 滚球全部。 @return list<array<string,mixed>> */
    public function fetchLive(): array
    {
        return $this->fetchFixtures(['live' => 'all']);
    }

    /** 按日历日拉取（默认业务时区当天可由调用方传入）。 @return list<array<string,mixed>> */
    public function fetchByDate(string $date): array
    {
        return $this->fetchFixtures(['date' => $date]);
    }

    /**
     * 按 API-Football fixture_id 拉单场。
     *
     * @return array<string,mixed>|null
     */
    public function fetchById(int $fixtureId): ?array
    {
        if ($fixtureId <= 0) {
            return null;
        }
        $list = $this->fetchFixtures(['id' => $fixtureId]);
        return $list[0] ?? null;
    }

    /**
     * 拉取业务日当天 + 前一天（覆盖跨时区未完赛），用于定时同步。
     *
     * @return list<array<string,mixed>>
     */
    public function fetchBusinessWindow(?string $timezone = null): array
    {
        $tz = new DateTimeZone($timezone ?: 'UTC');
        $today = new DateTimeImmutable('now', $tz);
        $yesterday = $today->modify('-1 day');

        $merged = [];
        $seen = [];
        foreach ([$today->format('Y-m-d'), $yesterday->format('Y-m-d')] as $day) {
            foreach ($this->fetchByDate($day) as $row) {
                $id = (int) ($row['source_id'] ?? 0);
                if ($id <= 0 || isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $merged[] = $row;
            }
        }
        return $merged;
    }

    /**
     * 原始 API 响应（含 response / errors / results）。
     *
     * @param array<string,scalar> $query
     * @return array<string,mixed>
     */
    public function fetchRaw(array $query): array
    {
        $this->assertConfigured();

        $url = $this->baseUrl . '/fixtures?' . http_build_query($query);
        $response = $this->http->get($url, [
            'x-apisports-key' => $this->apiKey,
        ], $this->timeout);

        if ($response['status'] !== 200) {
            throw new ArbitrageException(
                'API_FOOTBALL_HTTP_ERROR',
                'API-Football returned non-200 status',
                ['status' => $response['status']]
            );
        }

        try {
            $data = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ArbitrageException('API_FOOTBALL_INVALID_RESPONSE', 'API-Football response is not valid JSON', [
                'message' => $e->getMessage(),
            ]);
        }

        if (!is_array($data)) {
            throw new ArbitrageException('API_FOOTBALL_INVALID_RESPONSE', 'API-Football response must be JSON');
        }

        $errors = $data['errors'] ?? null;
        // API 可能返回 [] / {} / null；仅在有实质内容时报错
        if (is_array($errors) && $errors !== []) {
            throw new ArbitrageException('API_FOOTBALL_API_ERROR', 'API-Football returned errors', [
                'errors' => $errors,
            ]);
        }
        if (is_object($errors) && get_object_vars($errors) !== []) {
            throw new ArbitrageException('API_FOOTBALL_API_ERROR', 'API-Football returned errors', [
                'errors' => $errors,
            ]);
        }

        return $data;
    }

    /**
     * 归一化为 v2 fixture 友好结构。
     *
     * 字段约定：
     * - source = 1 (API-Football)
     * - source_id = API fixture.id
     * - kickoff_at = unix 时间戳
     * - is_finished = FT/AET/PEN/AWD 等完赛短码
     *
     * @param array<string,mixed> $data
     * @return list<array<string,mixed>>
     */
    public function normalize(array $data): array
    {
        $finishedShort = ['FT', 'AET', 'PEN', 'AWD', 'WO'];
        $fixtures = [];

        foreach (($data['response'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $fixture = is_array($item['fixture'] ?? null) ? $item['fixture'] : [];
            $league  = is_array($item['league'] ?? null) ? $item['league'] : [];
            $teams   = is_array($item['teams'] ?? null) ? $item['teams'] : [];
            $goals   = is_array($item['goals'] ?? null) ? $item['goals'] : [];
            $status  = is_array($fixture['status'] ?? null) ? $fixture['status'] : [];

            $sourceId = (int) ($fixture['id'] ?? 0);
            if ($sourceId <= 0) {
                continue;
            }

            $dateStr = (string) ($fixture['date'] ?? '');
            $kickoffAt = 0;
            if ($dateStr !== '') {
                try {
                    $kickoffAt = (new DateTimeImmutable($dateStr))->getTimestamp();
                } catch (Throwable) {
                    $kickoffAt = (int) ($fixture['timestamp'] ?? 0);
                }
            } else {
                $kickoffAt = (int) ($fixture['timestamp'] ?? 0);
            }

            $statusShort = (string) ($status['short'] ?? '');

            $fixtures[] = [
                'source'       => 1,
                'source_id'    => $sourceId,
                'fixture_id'   => $sourceId, // 兼容旧调用方
                'league'       => (string) ($league['name'] ?? ''),
                'home'         => (string) ($teams['home']['name'] ?? ''),
                'away'         => (string) ($teams['away']['name'] ?? ''),
                'timezone'     => (string) ($fixture['timezone'] ?? ''),
                'date'         => $dateStr,
                'kickoff_at'   => $kickoffAt,
                'status_short' => $statusShort,
                'status_long'  => (string) ($status['long'] ?? ''),
                'score_home'   => (int) ($goals['home'] ?? 0),
                'score_away'   => (int) ($goals['away'] ?? 0),
                'is_finished'  => in_array($statusShort, $finishedShort, true) ? 1 : 0,
                'is_placeholder' => 0,
                'raw'          => $item,
            ];
        }

        return $fixtures;
    }

    private function assertConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new ArbitrageException(
                'API_FOOTBALL_CONFIG_MISSING',
                'API-Football API key is missing'
            );
        }
    }
}
