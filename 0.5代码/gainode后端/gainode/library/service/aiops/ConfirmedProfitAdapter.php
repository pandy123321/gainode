<?php

declare(strict_types=1);

namespace library\service\aiops;

use library\dict\ErrorDict;
use library\service\idempotency\IdempotencyStore;
use support\exception\DomainException;

/**
 * 内部 AI 经济引擎 · 已确认利润输入适配器（S02-P08，07 §S02-P08 步骤 1）。
 *
 * 只接受「已确认、可追溯、去重」的内部可审计执行结果，记录
 * source object / hash / currency / confirmed_at；未确认或重复输入拒绝。
 *
 * 语义（02 §5.4 + 07 §S02-P08）：
 *   - confirmed 必须为 true（未确认/候选结果一律拒绝，不得进入预算计算）；
 *   - source_object_type/source_object_id/source_hash 非空（可追溯）；
 *   - currency 非空、confirmed_at 有效、profit_amount 为合法非负 decimal string；
 *   - dedupe_key 由 source_hash 派生，重复输入经 IdempotencyStore 检测拒绝。
 *
 * 安全约束：金额 decimal string 禁 float；未确认/不可追溯/非法金额 fail-closed
 * （VALIDATION_ERROR 400）；去重存储不可用 → DEPENDENCY_UNAVAILABLE 503（无法保证去重）。
 *
 * 冻结状态：预算持久对象未冻结 → 去重依赖 IdempotencyStore 接口（默认 Null fail-closed）。
 */
class ConfirmedProfitAdapter
{
    // 输入确认状态
    public const SOURCE_STATUS_CONFIRMED = 'CONFIRMED';
    public const SOURCE_STATUS_UNCONFIRMED = 'UNCONFIRMED';

    // 默认功能货币（02 §四账：功能货币收入账）
    public const DEFAULT_CURRENCY = 'USDT';

    /**
     * 归一化已确认内部结果（纯函数，零写入）。
     *
     * @param array<string,mixed> $raw
     *   - confirmed: bool（必须 true）
     *   - source_object_type: string（非空）
     *   - source_object_id: string（非空）
     *   - source_hash: string|null（可追溯哈希，空则由 source_object_id 兜底计算）
     *   - currency: string|null（默认 USDT）
     *   - confirmed_at: int（Unix 秒，必须 >0）
     *   - profit_amount: string|int|float（非负 decimal）
     * @return array<string,mixed>
     * @throws DomainException
     */
    public function normalize(array $raw): array
    {
        if (($raw['confirmed'] ?? false) !== true) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'confirmed_profit input not confirmed');
        }

        $sourceType = trim((string) ($raw['source_object_type'] ?? ''));
        $sourceId   = trim((string) ($raw['source_object_id'] ?? ''));
        if ($sourceType === '' || $sourceId === '') {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'confirmed_profit source not traceable');
        }

        $currency = trim((string) ($raw['currency'] ?? self::DEFAULT_CURRENCY));
        if ($currency === '') {
            $currency = self::DEFAULT_CURRENCY;
        }

        $confirmedAt = (int) ($raw['confirmed_at'] ?? 0);
        if ($confirmedAt <= 0) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'confirmed_profit confirmed_at invalid');
        }

        // 已确认利润可为负（亏损）——「<=0 短路」在 ReferenceProfitService 处理，
        // 本适配器只校验金额为合法 decimal string（禁 float、禁非法字符）。
        $profit = $this->normalizeDecimal($raw['profit_amount'] ?? null);
        if ($profit === null) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'confirmed_profit amount invalid');
        }

        $sourceHash = trim((string) ($raw['source_hash'] ?? ''));
        if ($sourceHash === '') {
            $sourceHash = hash('sha256', $sourceType . '|' . $sourceId);
        }

        $dedupeKey = substr(hash('sha256', $sourceHash . '|' . $currency . '|' . $confirmedAt), 0, 64);

        return [
            'source_status'       => self::SOURCE_STATUS_CONFIRMED,
            'confirmed_profit'    => $profit,
            'source_object_type'  => $sourceType,
            'source_object_id'    => $sourceId,
            'source_hash'         => $sourceHash,
            'currency'            => $currency,
            'confirmed_at'        => $confirmedAt,
            'dedupe_key'          => $dedupeKey,
        ];
    }

    /**
     * 重复输入拒绝（步骤 1「重复输入拒绝」）。
     *
     * 依赖 IdempotencyStore 去重：存储不可用 → 无法保证去重，fail-closed；
     * 已存在记录 → IDEMPOTENCY_CONFLICT（409）。
     *
     * @param array<string,mixed> $confirmed normalize() 返回值
     * @throws DomainException
     */
    public function assertNotDuplicate(array $confirmed, IdempotencyStore $store): void
    {
        if (!$store->isAvailable()) {
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'idempotency store unavailable, dedupe not guaranteed');
        }
        $existing = $store->find($confirmed['dedupe_key'], 'confirmed_profit');
        if ($existing !== null) {
            throw new DomainException(ErrorDict::IDEMPOTENCY_CONFLICT, 'confirmed_profit duplicate input');
        }
    }

    /**
     * 归一化金额为 bcmath 可用 decimal string；非法返回 null。
     */
    private function normalizeDecimal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_float($value)) {
            return null; // 禁止 float 进入内部金额
        }
        if (is_int($value)) {
            return (string) $value;
        }
        $s = trim((string) $value);
        // 允许整数/小数（可负）：^-?\d+(\.\d+)?$
        if ($s === '' || !preg_match('/^-?\d+(\.\d+)?$/', $s)) {
            return null;
        }
        return $s;
    }
}
