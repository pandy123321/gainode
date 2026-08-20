<?php

declare(strict_types=1);

namespace library\service\robot;

use library\dao\parameter\ParameterReleaseDao;
use library\dao\parameter\ParameterSnapshotDao;

/**
 * 56 级 RobotRule / ParameterSnapshot 读取器（S02-P04，07 §S02-P04 步骤 1）。
 *
 * 只读聚合：从 parameter_releases(status='active') → parameter_snapshots.parameter_values
 * （JSON 键值对）解析 06 §4 的 AI.* 参数。**零写入**，append-only 快照只读。
 *
 * fail-closed 语义：
 *   - 无 Active Release / 无 snapshot / 缺 AI.standard_capacity_rule_version
 *     → source_status=UNAVAILABLE，所有依赖规则的能力不可用。
 *   - 单项参数缺失不整体关闭，但该能力不可用（claim 缺省 false、power_cap 缺省空、
 *     daily_yield_coefficient 允许 '0' 且 0 是合法值，不得用 empty() 判可用）。
 *
 * 安全约束：
 *   - 所有数值 AI.* 一律按字符串读取；Power Cap 映射 key(int)/value(string)。
 *   - 禁止 float；禁止 mock 数值；禁止前端写死。
 *
 * @authoritative_reader parameter_snapshots（只读）
 */
class RobotRuleReader
{
    // source_status 枚举（投影字段）
    public const SOURCE_AVAILABLE = 'AVAILABLE';
    public const SOURCE_UNAVAILABLE = 'UNAVAILABLE';

    // 06 §4 参数键（AI.*）
    public const KEY_STANDARD_CAPACITY_RULE_VERSION = 'AI.standard_capacity_rule_version';
    public const KEY_STANDARD_CAPACITY_BY_LEVEL = 'AI.standard_capacity_by_level';
    public const KEY_UPGRADE_COOLDOWN_BY_RANGE = 'AI.upgrade_cooldown_by_range';
    public const KEY_POWER_SELL_CONSUMPTION_RATIO = 'AI.power_sell_consumption_ratio';
    public const KEY_POWER_DAILY_RESTORE_RATE_BY_RANGE = 'AI.power_daily_restore_rate_by_range';
    public const KEY_UPGRADE_P_DISCOUNT_BY_LEVEL = 'AI.upgrade_p_discount_by_level';
    public const KEY_REWARD_CLAIM_WINDOW_BASE_HOURS = 'AI.ai_reward_claim_window_base_hours';
    public const KEY_REWARD_CLAIM_WINDOW_PER_LEVEL_HOURS = 'AI.ai_reward_claim_window_per_level_hours';
    public const KEY_DAILY_YIELD_COEFFICIENT_SOURCE = 'AI.daily_yield_coefficient_source';
    public const KEY_DAILY_YIELD_COEFFICIENT_PRECISION = 'AI.daily_yield_coefficient_precision';
    public const KEY_AI_REWARD_BUDGET_CAP = 'AI.ai_reward_budget_cap';
    public const KEY_AI_REWARD_PERIOD_CAP = 'AI.ai_reward_period_cap';
    public const KEY_AI_REWARD_HOLD_PERIOD = 'AI.ai_reward_hold_period';
    public const KEY_AI_REWARD_EXPIRY_PERIOD = 'AI.ai_reward_expiry_period';
    public const KEY_AI_REWARD_CLAIM_ENABLED = 'AI.ai_reward_claim_enabled';
    public const KEY_POWER_CAP_BY_ROBOT_LEVEL = 'AI.power_cap_by_robot_level';
    public const KEY_POWER_CAP_FACTOR = 'AI.power_cap_factor';
    public const KEY_UPGRADE_APT_REQUIREMENT = 'AI.upgrade_apt_requirement';

    // 无 Active Release 时的 I18N 安全 reason（不暴露内部原因 code）
    public const REASON_NO_ACTIVE_RELEASE = 'AI_RULE_NOT_ACTIVE';

    private ParameterReleaseDao $releaseDao;
    private ParameterSnapshotDao $snapshotDao;

    public function __construct(?ParameterReleaseDao $releaseDao = null, ?ParameterSnapshotDao $snapshotDao = null)
    {
        $this->releaseDao = $releaseDao ?? new ParameterReleaseDao();
        $this->snapshotDao = $snapshotDao ?? new ParameterSnapshotDao();
    }

    /**
     * 读取 Active Release 对应的参数值（JSON 键值对）。无则返回 []。
     *
     * @return array<string,mixed>
     */
    private function loadActiveValues(): array
    {
        $release = $this->releaseDao->getActive();
        if (empty($release) || empty($release->snapshot_id)) {
            return [];
        }

        $snapshot = $this->snapshotDao->get((string) $release->snapshot_id);
        if (empty($snapshot)) {
            return [];
        }

        $raw = (string) $snapshot->parameter_values;
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 主投影：56 级规则 + 预算/系数/Power/升级参数 + 来源状态。
     *
     * @return array<string,mixed>
     */
    public function getRuleSnapshot(): array
    {
        $release = $this->releaseDao->getActive();
        $releaseId = empty($release) ? '0' : (string) $release->release_id;
        $snapshotId = empty($release) ? '0' : (string) $release->snapshot_id;

        $values = $this->loadActiveValues();

        $ruleVersion = $this->strVal($values[self::KEY_STANDARD_CAPACITY_RULE_VERSION] ?? null);
        $available = $ruleVersion !== '' && $releaseId !== '0';

        $powerCap = $values[self::KEY_POWER_CAP_BY_ROBOT_LEVEL] ?? null;
        $powerCapMap = is_array($powerCap) ? $this->normalizePowerCapMap($powerCap) : [];

        $powerCapFactor = $this->strVal($values[self::KEY_POWER_CAP_FACTOR] ?? null);

        $upgradeReq = $values[self::KEY_UPGRADE_APT_REQUIREMENT] ?? null;
        $upgradeReqMap = is_array($upgradeReq) ? $this->normalizeLevelStringMap($upgradeReq) : [];

        $capacityByLevel = $values[self::KEY_STANDARD_CAPACITY_BY_LEVEL] ?? null;
        $capacityByLevelMap = is_array($capacityByLevel) ? $this->normalizeLevelStringMap($capacityByLevel) : [];

        $cooldownRange = $values[self::KEY_UPGRADE_COOLDOWN_BY_RANGE] ?? null;
        $cooldownRangeList = is_array($cooldownRange) ? $cooldownRange : [];

        $restoreRange = $values[self::KEY_POWER_DAILY_RESTORE_RATE_BY_RANGE] ?? null;
        $restoreRangeList = is_array($restoreRange) ? $restoreRange : [];

        $pDiscount = $values[self::KEY_UPGRADE_P_DISCOUNT_BY_LEVEL] ?? null;
        $pDiscountMap = is_array($pDiscount) ? $this->normalizeLevelStringMap($pDiscount) : [];

        return [
            'source_status'                   => $available ? self::SOURCE_AVAILABLE : self::SOURCE_UNAVAILABLE,
            'parameter_release_id'            => $releaseId,
            'snapshot_id'                     => $snapshotId,
            'rule_version'                    => $ruleVersion,
            'power_cap_by_level'              => $powerCapMap,
            'power_cap_factor'                => $powerCapFactor,
            'upgrade_apt_requirement'         => $upgradeReqMap,
            'standard_capacity_by_level'      => $capacityByLevelMap,
            'upgrade_cooldown_by_range'       => $cooldownRangeList,
            'power_sell_consumption_ratio'    => $this->strVal($values[self::KEY_POWER_SELL_CONSUMPTION_RATIO] ?? null),
            'power_daily_restore_rate_by_range' => $restoreRangeList,
            'upgrade_p_discount_by_level'     => $pDiscountMap,
            'reward_claim_window_base_hours'  => $this->intVal($values[self::KEY_REWARD_CLAIM_WINDOW_BASE_HOURS] ?? null),
            'reward_claim_window_per_level_hours' => $this->intVal($values[self::KEY_REWARD_CLAIM_WINDOW_PER_LEVEL_HOURS] ?? null),
            'ai_reward_budget_cap'            => $this->strVal($values[self::KEY_AI_REWARD_BUDGET_CAP] ?? null),
            'ai_reward_period_cap'            => $this->strVal($values[self::KEY_AI_REWARD_PERIOD_CAP] ?? null),
            'ai_reward_hold_period'           => $this->intVal($values[self::KEY_AI_REWARD_HOLD_PERIOD] ?? null),
            'ai_reward_expiry_period'         => $this->intVal($values[self::KEY_AI_REWARD_EXPIRY_PERIOD] ?? null),
            'ai_reward_claim_enabled'         => $this->boolVal($values[self::KEY_AI_REWARD_CLAIM_ENABLED] ?? null),
            'daily_yield_coefficient_source'  => $this->strVal($values[self::KEY_DAILY_YIELD_COEFFICIENT_SOURCE] ?? null),
            'daily_yield_coefficient_precision' => $this->strVal($values[self::KEY_DAILY_YIELD_COEFFICIENT_PRECISION] ?? null),
            'reason_code'                     => $available ? '' : self::REASON_NO_ACTIVE_RELEASE,
        ];
    }

    /**
     * 是否有可用正式规则（Active Release + 56 级规则版本）。
     */
    public function isAvailable(): bool
    {
        return $this->getRuleSnapshot()['source_status'] === self::SOURCE_AVAILABLE;
    }

    /**
     * 取指定等级的 Power Cap（decimal string）。
     *
     * 解析优先级（fail-closed）：
     *   1. 规则不可用 → null。
     *   2. 显式 56 级映射（AI.power_cap_by_robot_level）命中 → 直接返回。
     *   3. 派生规则（AI.power_cap_factor，Owner 决策 CR-20260818-002）：
     *      power_cap = standard_capacity × factor（bcmath 精确乘法）。
     *      缺 factor 或该级标准产能缺失 → null。
     */
    public function getPowerCap(int $level): ?string
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return null;
        }
        if (isset($snap['power_cap_by_level'][$level])) {
            return (string) $snap['power_cap_by_level'][$level];
        }
        $factor = $snap['power_cap_factor'];
        if ($factor === '') {
            return null;
        }
        $capacity = $snap['standard_capacity_by_level'][$level] ?? null;
        if ($capacity === null) {
            return null;
        }
        return $this->mulDecimal($capacity, $factor);
    }

    /**
     * Claim 总开关（缺省 false，fail-closed）。
     */
    public function getClaimEnabled(): bool
    {
        return (bool) $this->getRuleSnapshot()['ai_reward_claim_enabled'];
    }

    /**
     * 当日系数来源。规则不可用或未配置返回空串（不 mock 系数）。
     */
    public function getDailyYieldCoefficientSource(): string
    {
        return (string) $this->getRuleSnapshot()['daily_yield_coefficient_source'];
    }

    /**
     * 指定等级的标准产能（decimal string）。规则不可用或该级缺失返回 null。
     */
    public function getStandardCapacity(int $level): ?string
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return null;
        }
        $map = $snap['standard_capacity_by_level'] ?? [];
        return isset($map[$level]) ? (string) $map[$level] : null;
    }

    /**
     * 升级到指定等级的基准费用（decimal string）。规则不可用或该级缺失返回 null。
     */
    public function getUpgradeCost(int $toLevel): ?string
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return null;
        }
        $map = $snap['upgrade_apt_requirement'] ?? [];
        return isset($map[$toLevel]) ? (string) $map[$toLevel] : null;
    }

    /**
     * 指定等级升级后的冷却天数（按区间解析）。规则不可用或未命中返回 0。
     */
    public function getUpgradeCooldownDays(int $toLevel): int
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return 0;
        }
        foreach (($snap['upgrade_cooldown_by_range'] ?? []) as $range) {
            $from = (int) ($range['from'] ?? 0);
            $to = (int) ($range['to'] ?? 0);
            if ($toLevel >= $from && $toLevel <= $to) {
                return (int) ($range['days'] ?? 0);
            }
        }
        return 0;
    }

    /**
     * 算力卖出消耗比例（decimal string，如 '0.5'）。规则不可用返回 null。
     */
    public function getPowerSellConsumptionRatio(): ?string
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return null;
        }
        $v = $snap['power_sell_consumption_ratio'] ?? '';
        return $v === '' ? null : (string) $v;
    }

    /**
     * 指定等级的算力每日恢复率（decimal string）。规则不可用或未命中返回 null。
     */
    public function getPowerDailyRestoreRate(int $level): ?string
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return null;
        }
        foreach (($snap['power_daily_restore_rate_by_range'] ?? []) as $range) {
            $from = (int) ($range['from'] ?? 0);
            $to = (int) ($range['to'] ?? 0);
            if ($level >= $from && $level <= $to) {
                return (string) ($range['rate'] ?? '0');
            }
        }
        return null;
    }

    /**
     * P 等级升级优惠比例（decimal string，如 '0.04'）。未配置返回 null。
     */
    public function getUpgradePDiscount(int $pLevel): ?string
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return null;
        }
        $map = $snap['upgrade_p_discount_by_level'] ?? [];
        return isset($map[$pLevel]) ? (string) $map[$pLevel] : null;
    }

    /**
     * 领取有效期（小时）= base + (level - 1) × per_level。
     * 规则不可用返回 null（调用方 fail-closed）。
     */
    public function getClaimWindowHours(int $level): ?int
    {
        $snap = $this->getRuleSnapshot();
        if ($snap['source_status'] !== self::SOURCE_AVAILABLE) {
            return null;
        }
        $base = (int) ($snap['reward_claim_window_base_hours'] ?? 0);
        $per = (int) ($snap['reward_claim_window_per_level_hours'] ?? 0);
        $level = max(1, $level);
        return $base + ($level - 1) * $per;
    }

    /**
     * bcmath 精确乘法（scale=18，去尾零返回干净 decimal string）。
     * 用于 Power cap 派生：standard_capacity × factor。
     */
    private function mulDecimal(string $a, string $b): string
    {
        $result = bcmul($a, $b, 18);
        $result = rtrim(rtrim($result, '0'), '.');
        return $result === '' ? '0' : $result;
    }

    /**
     * 字符串读取（缺失→''；数值型 JSON 也归一化为 string）。
     */
    private function strVal($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return (string) $value;
    }

    /**
     * 整型读取（缺失→0，负数/非法→0）。
     */
    private function intVal($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            $v = (int) $value;
            return $v < 0 ? 0 : $v;
        }
        return 0;
    }

    /**
     * 布尔读取（兼容 bool / 'true'/'false' / '1'/'0' / 'on'/'off'）。缺省 false。
     */
    private function boolVal($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }
        if (is_string($value)) {
            $v = strtolower(trim($value));
            return in_array($v, ['true', '1', 'on', 'yes'], true);
        }
        return false;
    }

    /**
     * Power Cap 映射归一化：key→int(level)，value→string(decimal)。
     */
    private function normalizePowerCapMap(array $map): array
    {
        $out = [];
        foreach ($map as $level => $cap) {
            if (is_numeric($level)) {
                $out[(int) $level] = $this->strVal($cap);
            }
        }
        return $out;
    }

    /**
     * 等级→字符串值映射归一化：key→int(level)，value→string。
     * 用于 standard_capacity_by_level / upgrade_apt_requirement。
     */
    private function normalizeLevelStringMap(array $map): array
    {
        $out = [];
        foreach ($map as $level => $val) {
            if (is_numeric($level)) {
                $out[(int) $level] = $this->strVal($val);
            }
        }
        return $out;
    }
}
