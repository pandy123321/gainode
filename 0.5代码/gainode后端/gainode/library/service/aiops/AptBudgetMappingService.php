<?php

declare(strict_types=1);

namespace library\service\aiops;

use library\dict\ErrorDict;
use support\exception\DomainException;

/**
 * 内部 AI 经济引擎 · APT 预算映射（S02-P08，07 §S02-P08 步骤 4/5）。
 *
 * 公式（02 §5.4）：
 *   mapped_apt_budget = reference_profit_USDT / apt_reference_price * mapping_multiplier
 *
 * fail-closed 语义（步骤 4）：
 *   - APT reference price snapshot 缺失/过期/<=0 → fail-closed，不除零、不回退 mock；
 *   - mapping multiplier 缺失/<=0 → fail-closed。
 *   - reference_profit <= 0 → 确定性 mapped_apt_budget='0'（短路，不需 price/multiplier）。
 *
 * 安全约束：全程 bcmath（scale=18）decimal string，禁止 float。
 */
class AptBudgetMappingService
{
    /**
     * 计算 mapped_apt_budget。
     *
     * @param string $referenceProfitUsdt reference_profit（USDT，decimal string）
     * @param string|null $aptReferencePrice APT reference price snapshot（decimal string）
     * @param string|null $mappingMultiplier mapping multiplier（decimal string）
     * @return array<string,mixed>
     * @throws DomainException
     */
    public function mapToApt(
        string $referenceProfitUsdt,
        ?string $aptReferencePrice,
        ?string $mappingMultiplier
    ): array {
        $reference = $this->normalizeDecimal($referenceProfitUsdt);
        if ($reference === null || bccomp($reference, '0', 18) < 0) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'reference_profit invalid');
        }

        // reference_profit <= 0 → 确定性 0（短路，不需价格/乘数）。
        if (bccomp($reference, '0', 18) <= 0) {
            return [
                'reference_profit'    => $reference,
                'apt_reference_price' => '',
                'mapping_multiplier'  => '',
                'mapped_apt_budget'   => '0',
                'source_status'       => 'AVAILABLE',
            ];
        }

        $price = $this->normalizeDecimal($aptReferencePrice);
        if ($price === null || bccomp($price, '0', 18) <= 0) {
            throw new DomainException(
                ErrorDict::DEPENDENCY_UNAVAILABLE,
                'APT reference price snapshot missing/stale/<=0 (not frozen)'
            );
        }

        $multiplier = $this->normalizeDecimal($mappingMultiplier);
        if ($multiplier === null || bccomp($multiplier, '0', 18) <= 0) {
            throw new DomainException(
                ErrorDict::DEPENDENCY_UNAVAILABLE,
                'mapping multiplier missing/<=0 (06 TBC)'
            );
        }

        $mapped = $this->computeMapped($reference, $price, $multiplier);

        return [
            'reference_profit'    => $reference,
            'apt_reference_price' => $price,
            'mapping_multiplier'  => $multiplier,
            'mapped_apt_budget'   => $mapped,
            'source_status'       => 'AVAILABLE',
        ];
    }

    /**
     * 纯计算：mapped = reference / price * multiplier（bcmath，scale=18）。
     *
     * @param string $reference
     * @param string $price 已确保 > 0
     * @param string $multiplier 已确保 > 0
     */
    public function computeMapped(string $reference, string $price, string $multiplier): string
    {
        $perUnit = bcdiv($reference, $price, 18);
        return bcmul($perUnit, $multiplier, 18);
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
