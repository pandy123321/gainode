<?php
declare(strict_types=1);

namespace support\arbitrage\http;

use support\extend\Log;
use support\extend\Redis;

/**
 * BetBurger ID→名称映射存储（Redis 主存 + 本地 JSON 兜底）。
 */
final class EntityNameMapStore
{
    public const REDIS_BOOKMAKERS = 'arbitrage:entity_map:bookmakers';
    public const REDIS_MARKETS = 'arbitrage:entity_map:markets';
    public const REDIS_SPORTS = 'arbitrage:entity_map:sports';
    public const REDIS_META = 'arbitrage:entity_map:meta';

    /** @return array{bookmakers:array<int|string,string>,markets:array<int|string,string>,sports:array<int|string,string>} */
    public static function load(): array
    {
        $bookmakers = self::loadMap(self::REDIS_BOOKMAKERS);
        $markets = self::loadMap(self::REDIS_MARKETS);
        $sports = self::loadMap(self::REDIS_SPORTS);

        // Redis 为空或映射过少时，用本地 JSON 兜底（避免常驻进程启动过早拿到空映射后长期写 Bookmaker#）
        if (count($bookmakers) < 20 || count($markets) < 50) {
            $file = self::filePath();
            if (is_file($file)) {
                $json = json_decode((string) file_get_contents($file), true);
                if (is_array($json)) {
                    $fileBm = self::normalizeMap($json['bookmakers'] ?? []);
                    $fileMk = self::normalizeMap($json['markets'] ?? $json['variation'] ?? []);
                    $fileSp = self::normalizeMap($json['sports'] ?? []);
                    if (count($fileBm) > count($bookmakers)) {
                        $bookmakers = $fileBm;
                    }
                    if (count($fileMk) > count($markets)) {
                        $markets = $fileMk;
                    }
                    if (count($fileSp) > count($sports)) {
                        $sports = $fileSp;
                    }
                }
            }
        }

        // 配置兜底（旧逻辑）
        if ($bookmakers === [] || $markets === []) {
            $conf = function_exists('config') ? (array) config('arbitrage', []) : [];
            if ($bookmakers === []) {
                $bookmakers = self::normalizeMap($conf['bookmaker_names'] ?? []);
            }
            if ($markets === []) {
                $markets = self::normalizeMap($conf['market_names'] ?? []);
            }
        }

        return [
            'bookmakers' => $bookmakers,
            'markets'    => $markets,
            'sports'     => $sports,
        ];
    }

    public static function toArrayNameMap(): ArrayNameMap
    {
        $maps = self::load();
        return new ArrayNameMap($maps['bookmakers'], $maps['markets']);
    }

    /**
     * @param array<int|string,string> $bookmakers
     * @param array<int|string,string> $markets
     * @param array<int|string,string> $sports
     */
    public static function save(array $bookmakers, array $markets, array $sports = []): void
    {
        $bookmakers = self::normalizeMap($bookmakers);
        $markets = self::normalizeMap($markets);
        $sports = self::normalizeMap($sports);
        $meta = [
            'updated_at'        => time(),
            'bookmaker_count'   => count($bookmakers),
            'market_count'      => count($markets),
            'sport_count'       => count($sports),
        ];

        try {
            Redis::set(self::REDIS_BOOKMAKERS, json_encode($bookmakers, JSON_UNESCAPED_UNICODE));
            Redis::set(self::REDIS_MARKETS, json_encode($markets, JSON_UNESCAPED_UNICODE));
            Redis::set(self::REDIS_SPORTS, json_encode($sports, JSON_UNESCAPED_UNICODE));
            Redis::set(self::REDIS_META, json_encode($meta, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            Log::error('EntityNameMap Redis 写入失败: ' . $e->getMessage());
        }

        $dir = dirname(self::filePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents(self::filePath(), json_encode([
            'bookmakers' => $bookmakers,
            'markets'    => $markets,
            'sports'     => $sports,
            'meta'       => $meta,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public static function filePath(): string
    {
        return runtime_path() . '/arbitrage/entity_maps.json';
    }

    /** @return array<int|string,string> */
    private static function loadMap(string $redisKey): array
    {
        try {
            $raw = Redis::get($redisKey);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return self::normalizeMap($decoded);
                }
            }
        } catch (\Throwable $e) {
            Log::error('EntityNameMap Redis 读取失败: ' . $e->getMessage());
        }
        return [];
    }

    /**
     * @param mixed $map
     * @return array<int|string,string>
     */
    private static function normalizeMap($map): array
    {
        if (!is_array($map)) {
            return [];
        }
        $out = [];
        foreach ($map as $id => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $out[(int) $id] = $name;
            $out[(string) (int) $id] = $name;
        }
        return $out;
    }
}
