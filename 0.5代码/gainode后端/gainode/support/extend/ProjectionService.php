<?php

declare(strict_types=1);

namespace support\extend;

/**
 * 非持久投影服务基类。
 *
 * 投影服务是只读聚合：只从 source-of-truth 读取并计算，绝不写回任何表（NOT_PERSISTED）。
 * 当聚合依赖（规则参数）未冻结 / 无 Active Release 时，必须默认 deny（返回 UNAVAILABLE，
 * 不回退旧值、不填 mock），遵守 05 §9 / §10 与 06 约束。
 */
class ProjectionService
{
    /**
     * 数据状态常量转发（引用 ProjectionResponse，避免重复定义）。
     */
    public const DATA_STATUS_REALTIME = ProjectionResponse::DATA_STATUS_REALTIME;
    public const DATA_STATUS_NEAR_REALTIME = ProjectionResponse::DATA_STATUS_NEAR_REALTIME;
    public const DATA_STATUS_STALE = ProjectionResponse::DATA_STATUS_STALE;
    public const DATA_STATUS_UNAVAILABLE = ProjectionResponse::DATA_STATUS_UNAVAILABLE;

    public const SOURCE_STATUS_READY = ProjectionResponse::SOURCE_STATUS_READY;
    public const SOURCE_STATUS_PARTIAL = ProjectionResponse::SOURCE_STATUS_PARTIAL;
    public const SOURCE_STATUS_UNAVAILABLE = ProjectionResponse::SOURCE_STATUS_UNAVAILABLE;

    /**
     * 当前 Unix 时间戳。
     */
    protected function now(): int
    {
        return time();
    }

    /**
     * 从 Model 读取原始 Unix 时间字段。
     *
     * Eloquent 在 $dateFormat='U' 下会把 created_time/updated_time 自动 cast 成 Carbon，
     * 直接 (int) 强转会报 warning。此处取原始值，兼容 int 与 DateTimeInterface。
     *
     * @param \support\extend\Model $model
     */
    protected function rawUnix($model, string $field): ?int
    {
        $raw = $model->getRawOriginal($field);
        if ($raw === null || $raw === '') {
            return null;
        }
        if ($raw instanceof \DateTimeInterface) {
            return $raw->getTimestamp();
        }
        return (int) $raw;
    }

    /**
     * 构造默认 deny 的元数据（依赖未冻结或越权时使用）。
     *
     * 返回数组供子类填充 Response 基类字段；data_status/source_status 均为 UNAVAILABLE，
     * as_of/updated_at 记为当前时间，refresh_hint 提示数据源不可用或访问拒绝，其余元字段保持 null。
     *
     * @param string $refreshHint I18N key（默认 source_unavailable；越权用 access_denied）
     */
    protected function unavailableMetadata(string $refreshHint = 'projection.source_unavailable'): array
    {
        $now = $this->now();
        return [
            'data_status' => self::DATA_STATUS_UNAVAILABLE,
            'as_of' => $now,
            'updated_at' => $now,
            'next_refresh_at' => null,
            'refresh_hint' => $refreshHint,
            'stale_after' => null,
            'snapshot_id' => null,
            'source_status' => self::SOURCE_STATUS_UNAVAILABLE,
        ];
    }

    /**
     * 构造实时读取的元数据（source 已建表且可读时使用）。
     *
     * @param int|null $updatedAt 源数据最后更新时间
     */
    protected function realtimeMetadata(?int $updatedAt = null): array
    {
        $now = $this->now();
        return [
            'data_status' => self::DATA_STATUS_REALTIME,
            'as_of' => $now,
            'updated_at' => $updatedAt ?? $now,
            'next_refresh_at' => null,
            'refresh_hint' => '',
            'stale_after' => null,
            'snapshot_id' => null,
            'source_status' => self::SOURCE_STATUS_READY,
        ];
    }

    /**
     * 将元数据数组写入响应对象（子类共用，避免重复赋值）。
     *
     * @param ProjectionResponse $response
     * @param array $metadata 由 unavailableMetadata()/realtimeMetadata() 返回
     */
    protected function applyMetadata(ProjectionResponse $response, array $metadata): void
    {
        $response->data_status = $metadata['data_status'];
        $response->as_of = $metadata['as_of'];
        $response->updated_at = $metadata['updated_at'];
        $response->next_refresh_at = $metadata['next_refresh_at'];
        $response->refresh_hint = $metadata['refresh_hint'];
        $response->stale_after = $metadata['stale_after'];
        $response->snapshot_id = $metadata['snapshot_id'];
        $response->source_status = $metadata['source_status'];
    }
}
