<?php

declare(strict_types=1);

namespace library\service\outbox;

/**
 * 空 Outbox 存储（fail-closed 占位）。
 *
 * Outbox 持久表未冻结（CONSUMED_UNFROZEN_CONTRACT），本实现不持久化任何记录，
 * isAvailable() 恒返回 false。需要可靠投递的业务流程在接入真实存储前不得假设消息已入队。
 */
final class NullOutboxStore implements OutboxStore
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function append(string $dedupeKey, string $eventType, array $payload, string $requestId): void
    {
        // no-op：未冻结存储，不追加
    }
}
