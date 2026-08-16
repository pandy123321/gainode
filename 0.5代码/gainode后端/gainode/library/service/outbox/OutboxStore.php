<?php

declare(strict_types=1);

namespace library\service\outbox;

/**
 * Outbox 存储接口（07 §S02-P01 步骤 5）。
 *
 * 语义（07 §S02-P03/P07，业务事务与通知解耦）：
 *   - 业务事务内只追加 Outbox 记录；
 *   - 消费者按 channel/dedupe/retry/backoff 投递，失败进 dead-letter，不回滚业务；
 *   - dedupe key 与业务对象 / idempotency key 关联。
 *
 * 冻结状态：Outbox 持久表未在 STAGE-01 冻结（CONSUMED_UNFROZEN_CONTRACT）。
 * 本接口为契约；真实存储实现待表冻结后落盘（快照 2）。
 */
interface OutboxStore
{
    /**
     * 存储是否可用。返回 false 时调用方对「必须可靠投递」的通知应 fail-closed
     * （不假装已入队），或明确降级为同步尽力投递。
     */
    public function isAvailable(): bool;

    /**
     * 追加一条 Outbox 记录（业务事务内调用）。
     *
     * @param string $dedupeKey 去重键（与业务对象/idempotency key 关联）
     * @param string $eventType 事件类型
     * @param array  $payload   投递载荷
     * @param string $requestId
     * @return void
     */
    public function append(string $dedupeKey, string $eventType, array $payload, string $requestId): void;
}
