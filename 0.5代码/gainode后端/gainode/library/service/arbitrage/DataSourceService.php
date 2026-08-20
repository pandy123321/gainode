<?php
declare(strict_types=1);

namespace library\service\arbitrage;

use library\dao\sys\DictListDao;
use library\service\sys\DictListService;
use library\service\sys\DictService;
use support\arbitrage\client\ApiFootballClient;
use support\arbitrage\client\BetBurgerClient;

/**
 * 数据源管理：读取/保存 sys_dict_list 中的 BetBurger 与 API-Football 凭证配置，
 * 并提供凭证健康测试与同步统计。
 */
class DataSourceService
{
    /** 支持的数据源登记表。 */
    public const SOURCES = [
        'api_football' => ['name' => 'API-Football 比赛数据', 'type' => 'fixture'],
        'betburger'    => ['name' => 'BetBurger 套利信号', 'type' => 'signal'],
    ];

    /** 需要脱敏展示的凭证字段。 */
    private const CREDENTIAL_FIELDS = ['api_key', 'access_token'];

    /** 保存时跳过「未修改凭证」的掩码哨兵值。 */
    private const MASK_SENTINELS = ['******', '••••••', '********'];

    /**
     * 列出全部数据源（含字段元数据 + 配置/健康状态 + 同步统计）。
     * @return list<array<string,mixed>>
     */
    public function listSources(): array
    {
        $out = [];
        foreach (self::SOURCES as $code => $meta) {
            $out[] = $this->listSource($code);
        }
        return $out;
    }

    /**
     * 单个数据源详情。
     * @return array<string,mixed>
     */
    public function listSource(string $code): array
    {
        if (!isset(self::SOURCES[$code])) {
            throw new \InvalidArgumentException('未知数据源: ' . $code);
        }
        $fieldRows = (new DictListDao())->getDictList($code);

        $fields = [];
        $values = [];
        foreach ($fieldRows as $fc => $row) {
            $value = (string) ($row['field_value'] ?? '');
            $values[$fc] = $value;
            if ($fc === 'source_name') {
                continue;
            }
            $isCredential = in_array($fc, self::CREDENTIAL_FIELDS, true);
            $fields[] = [
                'field_code'     => (string) $fc,
                'field_name'     => (string) ($row['field_name'] ?? ''),
                'field_type'     => (string) ($row['field_type'] ?? 'text'),
                'field_required' => in_array((string) ($row['field_required'] ?? ''), ['Y', '1', 'true'], true),
                'field_tips'     => (string) ($row['field_tips'] ?? ''),
                'is_credential'  => $isCredential,
                'field_value'    => $isCredential && $value !== '' ? '******' : $value,
            ];
        }

        $configured = $this->isConfigured($code, $values);
        $sync = $this->syncStats($code);

        return [
            'code'         => $code,
            'name'         => self::SOURCES[$code]['name'],
            'type'         => self::SOURCES[$code]['type'],
            'configured'   => $configured,
            'status'       => $this->computeStatus($values, $configured),
            'sync_count'   => $sync['count'],
            'last_sync_at' => $sync['last'],
            'fields'       => array_values($fields),
        ];
    }

    /**
     * 保存数据源凭证（仅更新已提供的 field_code，跳过掩码哨兵）。
     * @param array<string,string> $fields field_code => value
     * @return array<string,mixed>
     */
    public function saveSource(string $code, array $fields): array
    {
        if (!isset(self::SOURCES[$code])) {
            throw new \InvalidArgumentException('未知数据源: ' . $code);
        }

        $clean = [];
        foreach ($fields as $fc => $value) {
            if (!is_string($fc)) {
                continue;
            }
            $value = (string) $value;
            if (in_array($value, self::MASK_SENTINELS, true)) {
                continue;
            }
            $clean[$fc] = $value;
        }

        if ($clean !== []) {
            (new DictListService())->saveDictListValue($code, $clean);
        }
        return $this->listSource($code);
    }

    /**
     * 凭证健康测试：用本次提交值（或库内值）向数据源发一次轻量请求。
     * @param array<string,string> $fields 覆盖值（可选，测试未保存的新凭证）
     * @return array<string,mixed>
     */
    public function testSource(string $code, array $fields = []): array
    {
        if (!isset(self::SOURCES[$code])) {
            throw new \InvalidArgumentException('未知数据源: ' . $code);
        }

        $base = (new DictService())->getDictConfigs($code);
        $merged = array_merge($base, array_filter($fields, static fn ($v) => !in_array((string) $v, self::MASK_SENTINELS, true)));

        $conf = [
            $code => $merged,
        ];

        $start = microtime(true);
        try {
            if ($code === 'api_football') {
                ApiFootballClient::fromConfig($conf)->fetchRaw(['id' => 1]);
            } else {
                BetBurgerClient::fromConfig($conf)->fetchRaw(null, 1);
            }
            $latency = (int) round((microtime(true) - $start) * 1000);
            return ['ok' => true, 'latency_ms' => $latency, 'message' => '连接正常'];
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            return ['ok' => false, 'latency_ms' => $latency, 'message' => $e->getMessage()];
        }
    }

    /**
     * 判断是否已配置关键凭证。
     * @param array<string,string> $values
     */
    private function isConfigured(string $code, array $values): bool
    {
        if ($code === 'api_football') {
            return trim((string) ($values['api_key'] ?? '')) !== '';
        }
        return trim((string) ($values['access_token'] ?? '')) !== '';
    }

    /**
     * @param array<string,string> $values
     */
    private function computeStatus(array $values, bool $configured): string
    {
        if (!$configured) {
            return 'disabled';
        }
        $expire = (string) ($values['expire_at'] ?? '');
        if ($expire !== '') {
            $ts = strtotime($expire);
            if ($ts !== false && $ts < time()) {
                return 'error';
            }
        }
        return 'healthy';
    }

    /**
     * 数据源对应的本地同步统计（入库条数 + 最近更新时间）。
     * @return array{count:int,last:int}
     */
    private function syncStats(string $code): array
    {
        if ($code === 'api_football') {
            $row = \support\Db::table('arbitrage_fixture')
                ->where('source', 1)
                ->selectRaw('COUNT(*) AS cnt, COALESCE(MAX(updated_time),0) AS last_ts')
                ->first();
        } else {
            $row = \support\Db::table('arbitrage_signal')
                ->selectRaw('COUNT(*) AS cnt, COALESCE(MAX(updated_time),0) AS last_ts')
                ->first();
        }
        return [
            'count' => (int) ($row->cnt ?? 0),
            'last'  => (int) ($row->last_ts ?? 0),
        ];
    }
}
