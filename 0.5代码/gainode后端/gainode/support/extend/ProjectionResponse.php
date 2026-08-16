<?php

declare(strict_types=1);

namespace support\extend;

/**
 * 非持久投影响应基类（05 §10 数据新鲜度契约）。
 *
 * 所有投影 Response 必须携带 8 个数据元字段，客户端据此判断数据可信度，
 * 不得在前端推断资格/Power/容量。TBC/null 一律不回退旧值、不填 mock。
 *
 * data_status（05 §10）：
 *   REALTIME / NEAR_REALTIME / STALE / UNAVAILABLE
 *
 * source_status（本包约定，05 §10 未冻结枚举值）：
 *   READY / PARTIAL / UNAVAILABLE
 */
class ProjectionResponse
{
    public const DATA_STATUS_REALTIME = 'REALTIME';
    public const DATA_STATUS_NEAR_REALTIME = 'NEAR_REALTIME';
    public const DATA_STATUS_STALE = 'STALE';
    public const DATA_STATUS_UNAVAILABLE = 'UNAVAILABLE';

    public const SOURCE_STATUS_READY = 'READY';
    public const SOURCE_STATUS_PARTIAL = 'PARTIAL';
    public const SOURCE_STATUS_UNAVAILABLE = 'UNAVAILABLE';

    /** @var string 数据状态（REALTIME/NEAR_REALTIME/STALE/UNAVAILABLE） */
    public string $data_status = self::DATA_STATUS_UNAVAILABLE;

    /** @var int|null 数据反映的截止时间点（Unix 秒） */
    public ?int $as_of = null;

    /** @var int|null 数据最后更新时间（Unix 秒） */
    public ?int $updated_at = null;

    /** @var int|null 预期下次刷新时间（可 null） */
    public ?int $next_refresh_at = null;

    /** @var string 刷新建议文案 I18N key */
    public string $refresh_hint = '';

    /** @var int|null 超过该时长数据视为陈旧（可 null/TBC） */
    public ?int $stale_after = null;

    /** @var string|null 关联快照 ID */
    public ?string $snapshot_id = null;

    /** @var string 数据源状态（READY/PARTIAL/UNAVAILABLE） */
    public string $source_status = self::SOURCE_STATUS_UNAVAILABLE;

    /**
     * 序列化为响应数组（含全部 public 字段，子类字段一并纳入）。
     */
    public function toArray(): array
    {
        $out = [];
        foreach (get_object_vars($this) as $key => $value) {
            $out[$key] = $value;
        }
        return $out;
    }
}
