<?php

declare(strict_types=1);

namespace library\service\idempotency;

/**
 * 空幂等存储（fail-closed 占位）。
 *
 * IdempotencyRecord 持久表未冻结（CONSUMED_UNFROZEN_CONTRACT），本实现不持久化任何记录，
 * isAvailable() 恒返回 false。依赖幂等保证的写流程在接入真实存储前必须 fail-closed：
 * 检测到 isAvailable() === false 时拒绝执行（返回 DEPENDENCY_UNAVAILABLE），
 * 不得以「无记录」为由继续重复写入。
 */
final class NullIdempotencyStore implements IdempotencyStore
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function find(string $idempotencyKey, string $objectType): ?array
    {
        return null;
    }

    public function reserve(string $idempotencyKey, string $objectType, string $objectId, string $requestId): void
    {
        // no-op：未冻结存储，不预留
    }

    public function complete(string $idempotencyKey, string $objectType, string $objectId, array $response): void
    {
        // no-op：未冻结存储，不记录
    }
}
