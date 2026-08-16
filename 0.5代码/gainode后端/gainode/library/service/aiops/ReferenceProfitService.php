<?php

declare(strict_types=1);

namespace library\service\aiops;

use library\dict\ErrorDict;
use support\exception\DomainException;

/**
 * 内部 AI 经济引擎 · 参考利润计算（S02-P08，07 §S02-P08 步骤 2/3）。
 *
 * 计算结构（02 §5.4）：
 *   if confirmed_profit <= 0: reference_profit = 0（确定性，不得调用 smoothing）
 *   else: reference_profit = approved_smoothing(confirmed_profit, historical_reference)
 *
 * fail-closed 语义：
 *   - confirmed_profit <= 0 → 确定性 reference_profit='0'（完整实现，可测）。
 *   - confirmed_profit > 0 → 依赖 Active Release approved smoothing 规则 +
 *     historical_reference snapshot（06 未定义 / snapshot 对象未冻结）→ FAIL_CLOSED。
 *
 * 安全约束：金额 decimal string 禁 float；短路分支不得调用 smoothing 产生正值。
 */
class ReferenceProfitService
{
    // 计算算法标识（记录进决策元数据，供审计重放）
    public const ALGORITHM_ZERO_SHORTCUT = 'ZERO_SHORTCUT';
    public const ALGORITHM_APPROVED_SMOOTHING = 'APPROVED_SMOOTHING';

    /**
     * 计算 reference_profit。
     *
     * @param string $confirmedProfit 已确认利润（decimal string，非负）
     * @param array<string,mixed>|null $smoothingContext
     *   - algorithm: string（'APPROVED_SMOOTHING'）
     *   - rule_version: string
     *   - reference_profit: string（外部 approved rule 计算的非负结果）
     *   - input_hash: string|null
     * @return array<string,mixed>
     * @throws DomainException
     */
    public function computeReference(string $confirmedProfit, ?array $smoothingContext = null): array
    {
        $profit = $this->normalizeDecimal($confirmedProfit);
        if ($profit === null) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'confirmed_profit invalid');
        }

        // 步骤 2：confirmed_profit <= 0 → 确定性 reference_profit = 0，不调用 smoothing。
        if (bccomp($profit, '0', 18) <= 0) {
            return [
                'confirmed_profit' => $profit,
                'reference_profit' => '0',
                'algorithm'        => self::ALGORITHM_ZERO_SHORTCUT,
                'rule_version'     => '',
                'input_hash'       => '',
                'source_status'    => 'AVAILABLE',
            ];
        }

        // 步骤 3：>0 需 approved smoothing 规则 + historical_reference snapshot。
        if (empty($smoothingContext) || empty($smoothingContext['reference_profit'])) {
            throw new DomainException(
                ErrorDict::DEPENDENCY_UNAVAILABLE,
                'positive smoothing depends on Active Release approved smoothing rule + historical_reference snapshot (06 TBC)'
            );
        }

        $reference = $this->normalizeDecimal($smoothingContext['reference_profit']);
        if ($reference === null || bccomp($reference, '0', 18) < 0) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'smoothing reference_profit invalid');
        }

        return [
            'confirmed_profit' => $profit,
            'reference_profit' => $reference,
            'algorithm'        => self::ALGORITHM_APPROVED_SMOOTHING,
            'rule_version'     => trim((string) ($smoothingContext['rule_version'] ?? '')),
            'input_hash'       => trim((string) ($smoothingContext['input_hash'] ?? '')),
            'source_status'    => 'AVAILABLE',
        ];
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
            return null; // 禁止 float
        }
        if (is_int($value)) {
            return (string) $value;
        }
        $s = trim((string) $value);
        if ($s === '' || !preg_match('/^-?\d+(\.\d+)?$/', $s)) {
            return null;
        }
        return $s;
    }
}
