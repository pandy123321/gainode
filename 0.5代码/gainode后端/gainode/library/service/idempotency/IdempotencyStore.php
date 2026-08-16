<?php

declare(strict_types=1);

namespace library\service\idempotency;

/**
 * 幂等记录存储接口（07 §S02-P01 步骤 5）。
 *
 * 语义（05 §1 WRITE_IDEMPOTENCY + §7 RESULT_UNKNOWN）：
 *   - find() 返回已完成/处理中的原结果，调用方据此返回原响应或 RESULT_UNKNOWN；
 *   - reserve() 在处理开始时登记，防止并发重复；
 *   - complete() 在成功后记录原响应。
 *
 * 冻结状态：IdempotencyRecord 持久表未在 STAGE-01 冻结（CONSUMED_UNFROZEN_CONTRACT）。
 * 本接口为契约；真实存储实现待表冻结后落盘（快照 2）。
 */
interface IdempotencyStore
{
    /**
     * 存储是否可用。返回 false 时调用方必须 fail-closed（拒绝无法保证幂等的写操作）。
     */
    public function isAvailable(): bool;

    /**
     * 查询原请求结果。
     *
     * @param string $idempotencyKey
     * @param string $objectType
     * @return array|null null=无记录（首次请求）；否则 ['status' => 'completed'|'processing', 'response' => array|null, 'object_id' => string]
     */
    public function find(string $idempotencyKey, string $objectType): ?array;

    /**
     * 登记处理中（预留幂等槽位）。
     *
     * @param string $idempotencyKey
     * @param string $objectType
     * @param string $objectId
     * @param string $requestId
     * @return void
     */
    public function reserve(string $idempotencyKey, string $objectType, string $objectId, string $requestId): void;

    /**
     * 完成并记录原响应。
     *
     * @param string $idempotencyKey
     * @param string $objectType
     * @param string $objectId
     * @param array  $response
     * @return void
     */
    public function complete(string $idempotencyKey, string $objectType, string $objectId, array $response): void;
}
