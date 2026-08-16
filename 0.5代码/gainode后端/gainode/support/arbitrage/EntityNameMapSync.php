<?php
declare(strict_types=1);

namespace support\arbitrage;

use support\arbitrage\http\ArrayNameMap;
use support\arbitrage\http\EntityNameMapStore;
use support\Db;
use support\extend\Log;

/**
 * BetBurger entity_ids 同步：抓取 HTML 表格 → Redis/本地缓存 → 可选回填信号展示名。
 */
final class EntityNameMapSync
{
    public const ENTITY_IDS_URL = 'https://www.betburger.com/api/entity_ids';

    /**
     * 从公开页抓取并落库映射。
     *
     * @return array{
     *   bookmakers:array<int,string>,
     *   markets:array<int,string>,
     *   sports:array<int,string>,
     *   saved:bool
     * }
     */
    public function sync(bool $dryRun = false): array
    {
        $html = $this->fetchHtml(self::ENTITY_IDS_URL);
        if ($html === '') {
            throw new \RuntimeException('无法抓取 BetBurger entity_ids 页面（请检查外网）');
        }

        $tables = $this->parseTables($html);
        $bookmakers = $tables['Bookmakers'] ?? $tables['bookmakers'] ?? [];
        $markets = $tables['Variation'] ?? $tables['variation'] ?? [];
        $sports = $tables['Sports'] ?? $tables['sports'] ?? [];

        if (count($bookmakers) < 50 || count($markets) < 200) {
            throw new \RuntimeException(sprintf(
                '解析数量异常 bookmakers=%d markets=%d，疑似页面结构变化',
                count($bookmakers),
                count($markets)
            ));
        }

        $saved = false;
        if (!$dryRun) {
            EntityNameMapStore::save($bookmakers, $markets, $sports);
            $saved = true;
        }

        return [
            'bookmakers' => $bookmakers,
            'markets'    => $markets,
            'sports'     => $sports,
            'saved'      => $saved,
        ];
    }

    /**
     * 按当前映射回填 arbitrage_signal 展示名。
     *
     * @return int 实际更新条数
     */
    public function backfillSignals(bool $dryRun = false, int $limit = 5000): int
    {
        $maps = EntityNameMapStore::load();
        $nameMap = new ArrayNameMap($maps['bookmakers'], $maps['markets']);

        $rows = Db::table('arbitrage_signal')
            ->select([
                'id',
                'leg1_bookmaker_id', 'leg1_market_type', 'leg1_market_param',
                'leg2_bookmaker_id', 'leg2_market_type', 'leg2_market_param',
                'leg1_bookmaker', 'leg1_market', 'leg2_bookmaker', 'leg2_market',
            ])
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        $updated = 0;
        $now = time();
        foreach ($rows as $row) {
            $leg1Book = $nameMap->bookmaker((int) $row->leg1_bookmaker_id);
            $leg2Book = $nameMap->bookmaker((int) $row->leg2_bookmaker_id);
            $leg1Market = $nameMap->market((int) $row->leg1_market_type, (float) $row->leg1_market_param);
            $leg2Market = $nameMap->market((int) $row->leg2_market_type, (float) $row->leg2_market_param);

            $changed = $leg1Book !== (string) $row->leg1_bookmaker
                || $leg2Book !== (string) $row->leg2_bookmaker
                || $leg1Market !== (string) $row->leg1_market
                || $leg2Market !== (string) $row->leg2_market;
            if (!$changed) {
                continue;
            }

            if (!$dryRun) {
                Db::table('arbitrage_signal')->where('id', (int) $row->id)->update([
                    'leg1_bookmaker' => $leg1Book,
                    'leg2_bookmaker' => $leg2Book,
                    'leg1_market'    => $leg1Market,
                    'leg2_market'    => $leg2Market,
                    'updated_time'   => $now,
                ]);
            }
            $updated++;
        }

        return $updated;
    }

    /**
     * 回填仓位展示名（开仓时快照，映射晚到会导致长期显示 Bookmaker#/Market#）。
     * 优先用关联 signal 的 market_type；无信号时用仓位 bookmaker_id + 解析 Market#id(param)。
     *
     * @return int 实际更新条数
     */
    public function backfillPositions(bool $dryRun = false, int $limit = 5000): int
    {
        $maps = EntityNameMapStore::load();
        $nameMap = new ArrayNameMap($maps['bookmakers'], $maps['markets']);

        $rows = Db::table('arbitrage_position as p')
            ->leftJoin('arbitrage_signal as s', 's.id', '=', 'p.signal_id')
            ->select([
                'p.id',
                'p.leg1_bookmaker_id', 'p.leg2_bookmaker_id',
                'p.leg1_bookmaker', 'p.leg2_bookmaker',
                'p.leg1_market', 'p.leg2_market',
                's.leg1_market_type', 's.leg1_market_param',
                's.leg2_market_type', 's.leg2_market_param',
            ])
            ->orderBy('p.id')
            ->limit(max(1, $limit))
            ->get();

        $updated = 0;
        $now = time();
        foreach ($rows as $row) {
            $leg1Book = $nameMap->bookmaker((int) $row->leg1_bookmaker_id);
            $leg2Book = $nameMap->bookmaker((int) $row->leg2_bookmaker_id);

            if ($row->leg1_market_type !== null && (int) $row->leg1_market_type > 0) {
                $leg1Market = $nameMap->market((int) $row->leg1_market_type, (float) $row->leg1_market_param);
            } else {
                $leg1Market = $this->remapMarketPlaceholder((string) $row->leg1_market, $nameMap)
                    ?? (string) $row->leg1_market;
            }
            if ($row->leg2_market_type !== null && (int) $row->leg2_market_type > 0) {
                $leg2Market = $nameMap->market((int) $row->leg2_market_type, (float) $row->leg2_market_param);
            } else {
                $leg2Market = $this->remapMarketPlaceholder((string) $row->leg2_market, $nameMap)
                    ?? (string) $row->leg2_market;
            }

            $changed = $leg1Book !== (string) $row->leg1_bookmaker
                || $leg2Book !== (string) $row->leg2_bookmaker
                || $leg1Market !== (string) $row->leg1_market
                || $leg2Market !== (string) $row->leg2_market;
            if (!$changed) {
                continue;
            }

            if (!$dryRun) {
                Db::table('arbitrage_position')->where('id', (int) $row->id)->update([
                    'leg1_bookmaker' => $leg1Book,
                    'leg2_bookmaker' => $leg2Book,
                    'leg1_market'    => $leg1Market,
                    'leg2_market'    => $leg2Market,
                    'updated_time'   => $now,
                ]);
            }
            $updated++;
        }

        return $updated;
    }

    /** 把 Market#23(0.50) 这类占位符按映射还原为正式玩法名 */
    private function remapMarketPlaceholder(string $raw, ArrayNameMap $nameMap): ?string
    {
        if (!preg_match('/^Market#(\d+)(?:\(([-+]?\d+(?:\.\d+)?)\))?$/', trim($raw), $m)) {
            return null;
        }
        $id = (int) $m[1];
        $param = isset($m[2]) ? (float) $m[2] : 0.0;
        $name = $nameMap->market($id, $param);
        return str_starts_with($name, 'Market#') ? null : $name;
    }

    /**
     * 解析 entity_ids 页面中的 ID/Name 表格。
     *
     * @return array<string,array<int,string>> key=表标题（Bookmakers/Variation/...）
     */
    public function parseTables(string $html): array
    {
        $out = [];
        if (!preg_match_all('/<table.*?<\/table>/is', $html, $matches)) {
            return $out;
        }

        foreach ($matches[0] as $tableHtml) {
            $idx = strpos($html, $tableHtml);
            $head = $idx === false ? '' : substr($html, max(0, $idx - 400), 400);
            $label = 'unknown';
            if (preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', $head, $hm)) {
                $label = trim(strip_tags((string) end($hm[1])));
            }

            if (!preg_match_all('/<tr>\s*<td>(\d+)<\/td>\s*<td>(.*?)<\/td>\s*<\/tr>/is', $tableHtml, $rows, PREG_SET_ORDER)) {
                continue;
            }

            $map = [];
            foreach ($rows as $row) {
                $id = (int) $row[1];
                $name = trim(preg_replace('/\s+/', ' ', strip_tags($row[2])) ?? '');
                if ($name !== '') {
                    $map[$id] = $name;
                }
            }
            if ($map !== []) {
                $out[$label !== '' ? $label : ('table_' . count($out))] = $map;
            }
        }

        return $out;
    }

    private function fetchHtml(string $url): string
    {
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout'    => 45,
                    'user_agent' => 'Mozilla/5.0 (CrmProject EntityNameMapSync)',
                ],
            ]);
            $body = @file_get_contents($url, false, $ctx);
            return $body === false ? '' : (string) $body;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (CrmProject EntityNameMapSync)',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false || $code < 200 || $code >= 300) {
            Log::error('EntityNameMapSync 抓取失败', ['url' => $url, 'http' => $code]);
            curl_close($ch);
            return '';
        }
        curl_close($ch);
        return (string) $body;
    }
}
