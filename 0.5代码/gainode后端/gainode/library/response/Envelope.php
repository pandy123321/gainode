<?php

declare(strict_types=1);

namespace library\response;

/**
 * 统一 Response Envelope（05 §1 + §10）。
 *
 * 成功响应：request_id + data + 数据新鲜度契约 8 字段。
 * 写操作通过 $extra 追加 05 §1 最少返回字段（idempotency_key / object_type /
 * object_id / status / result_code / result_message / next_action / rule_version /
 * parameter_release_id / policy_version / snapshot_id / approval_id / audit_event_id）。
 *
 * 错误响应：request_id + result_code + result_message + http_status + details。
 *
 * 数组合并采用 `$base + $extra`（左优先），保证调用方无法覆盖 request_id/data 等
 * 固定字段，仅能追加写操作元数据。
 */
final class Envelope
{
    // 数据新鲜度状态（05 §10）
    public const DATA_STATUS_REALTIME = 'REALTIME';
    public const DATA_STATUS_NEAR_REALTIME = 'NEAR_REALTIME';
    public const DATA_STATUS_STALE = 'STALE';
    public const DATA_STATUS_UNAVAILABLE = 'UNAVAILABLE';

    // 数据源状态
    public const SOURCE_STATUS_OK = 'OK';
    public const SOURCE_STATUS_DEGRADED = 'DEGRADED';
    public const SOURCE_STATUS_UNAVAILABLE = 'UNAVAILABLE';

    /**
     * 成功响应。
     *
     * @param mixed  $data
     * @param array  $meta      数据新鲜度契约 8 字段（as_of / updated_at / next_refresh_at /
     *                          refresh_hint / stale_after / snapshot_id / source_status）
     * @param array  $extra     写操作附加字段（05 §1，不会覆盖固定字段）
     * @param string $requestId
     * @return array
     */
    public static function success($data, array $meta = [], array $extra = [], string $requestId = ''): array
    {
        $base = [
            'request_id'      => $requestId,
            'data'            => $data,
            'data_status'     => $meta['data_status'] ?? self::DATA_STATUS_REALTIME,
            'as_of'           => $meta['as_of'] ?? time(),
            'updated_at'      => $meta['updated_at'] ?? null,
            'next_refresh_at' => $meta['next_refresh_at'] ?? null,
            'refresh_hint'    => $meta['refresh_hint'] ?? null,
            'stale_after'     => $meta['stale_after'] ?? null,
            'snapshot_id'     => $meta['snapshot_id'] ?? null,
            'source_status'   => $meta['source_status'] ?? self::SOURCE_STATUS_OK,
        ];

        // 写操作元数据（左优先，禁止覆盖固定字段）
        return $base + $extra;
    }

    /**
     * 错误响应。
     *
     * @param string $resultCode    05 §7 错误码（library\dict\ErrorDict）
     * @param string $resultMessage
     * @param int    $httpStatus    HTTP 状态码
     * @param array  $details       结构化错误详情
     * @param string $requestId
     * @return array
     */
    public static function error(string $resultCode, string $resultMessage, int $httpStatus, array $details = [], string $requestId = ''): array
    {
        return [
            'request_id'     => $requestId,
            'result_code'    => $resultCode,
            'result_message' => $resultMessage,
            'http_status'    => $httpStatus,
            'details'        => $details,
        ];
    }
}
