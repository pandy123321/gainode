<?php

declare(strict_types=1);

namespace library\service\aiops;

use library\dict\ErrorDict;
use support\exception\DomainException;

/**
 * 内部 AI 经济引擎 · 每日 AI 预算（S02-P08，07 §S02-P08 步骤 6）。
 *
 * 公式（02 §5.4）：
 *   daily_ai_budget = min(
 *       mapped_apt_budget,
 *       stage_expected_budget,
 *       stage_hard_cap,
 *       cash_support_cap,
 *       human_approved_cap
 *   )
 *
 * fail-closed 语义（步骤 6）：任一 required cap 缺失 → closed。
 * min 取值纯函数完整实现（可测）；cap 来源 Active Release/Snapshot（06 未定义 → TBC）。
 */
class DailyAIBudgetService
{
    public const CAP_MAPPED_APT_BUDGET = 'mapped_apt_budget';
    public const CAP_STAGE_EXPECTED = 'stage_expected_budget';
    public const CAP_STAGE_HARD = 'stage_hard_cap';
    public const CAP_CASH_SUPPORT = 'cash_support_cap';
    public const CAP_HUMAN_APPROVED = 'human_approved_cap';

    /**
     * 四个 required cap 键（步骤 6 明确列出，不含 mapped_apt_budget 自身）。
     */
    public const REQUIRED_CAPS = [
        self::CAP_STAGE_EXPECTED,
        self::CAP_STAGE_HARD,
        self::CAP_CASH_SUPPORT,
        self::CAP_HUMAN_APPROVED,
    ];

    /**
     * 计算 daily_ai_budget：五个候选值取最小（bcmath）。
     *
     * @param array<string,string> $candidates key => decimal string
     * @return string
     * @throws DomainException
     */
    public function computeDaily(array $candidates): string
    {
        $mapped = $this->normalizeDecimal($candidates[self::CAP_MAPPED_APT_BUDGET] ?? null);
        if ($mapped === null) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'mapped_apt_budget missing');
        }

        $min = $mapped;
        foreach (self::REQUIRED_CAPS as $capKey) {
            $v = $this->normalizeDecimal($candidates[$capKey] ?? null);
            if ($v === null) {
                throw new DomainException(
                    ErrorDict::DEPENDENCY_UNAVAILABLE,
                    "required cap missing: {$capKey}"
                );
            }
            if (bccomp($v, $min, 18) < 0) {
                $min = $v;
            }
        }

        return $min;
    }

    /**
     * 解析并校验四 cap（步骤 6：任一 required cap 缺失则 closed）。
     *
     * @param array<string,mixed>|null $caps
     * @return array<string,string> 归一化后的四 cap decimal string
     * @throws DomainException
     */
    public function resolveCaps(?array $caps): array
    {
        $resolved = [];
        foreach (self::REQUIRED_CAPS as $capKey) {
            $v = $this->normalizeDecimal($caps[$capKey] ?? null);
            if ($v === null) {
                throw new DomainException(
                    ErrorDict::DEPENDENCY_UNAVAILABLE,
                    "required cap missing from Active Release: {$capKey}"
                );
            }
            $resolved[$capKey] = $v;
        }
        return $resolved;
    }

    private function normalizeDecimal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_float($value)) {
            return null;
        }
        if (is_int($value)) {
            return (string) $value;
        }
        $s = trim((string) $value);
        if ($s === '' || !preg_match('/^\d+(\.\d+)?$/', $s)) {
            return null;
        }
        return $s;
    }
}
