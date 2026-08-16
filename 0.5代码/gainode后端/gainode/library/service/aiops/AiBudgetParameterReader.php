<?php

declare(strict_types=1);

namespace library\service\aiops;

use library\dao\parameter\ParameterReleaseDao;
use library\dao\parameter\ParameterSnapshotDao;

/**
 * 内部 AI 经济引擎 · 预算参数读取器（S02-P08，07 §S02-P08 Parameter/Snapshot adapter）。
 *
 * 只读聚合：从 parameter_releases(status='active') → parameter_snapshots.parameter_values
 * （JSON 键值对）解析预算计算所需的 AI.* 参数。**零写入**，append-only 快照只读。
 *
 * fail-closed 语义：无 Active Release / 无 snapshot / 键缺失 → 单项返回 ''（不可用），
 * 下游引擎在依赖这些参数时 fail-closed。
 *
 * 冻结状态：本读取器所需键（smoothing 规则 / mapping multiplier / 四 cap / rounding precision）
 * 在 06 参数字典尚未定义（全 TBC），故生产上恒 UNAVAILABLE；本读取器为契约骨架 + 纯解析逻辑。
 *
 * @authoritative_reader parameter_snapshots（只读）
 */
class AiBudgetParameterReader
{
    public const SOURCE_AVAILABLE = 'AVAILABLE';
    public const SOURCE_UNAVAILABLE = 'UNAVAILABLE';

    // 预算计算参数键（06 未定义 → TBC，仅作契约占位，禁自创数值）
    public const KEY_SMOOTHING_RULE = 'AI.reference_profit_smoothing_rule';
    public const KEY_MAPPING_MULTIPLIER = 'AI.apt_budget_mapping_multiplier';
    public const KEY_STAGE_EXPECTED_BUDGET = 'AI.stage_expected_budget';
    public const KEY_STAGE_HARD_CAP = 'AI.stage_hard_cap';
    public const KEY_CASH_SUPPORT_CAP = 'AI.cash_support_cap';
    public const KEY_HUMAN_APPROVED_CAP = 'AI.human_approved_cap';
    public const KEY_ROUNDING_PRECISION = 'AI.ai_budget_rounding_precision';

    public const REASON_NO_ACTIVE_RELEASE = 'AI_BUDGET_RULE_NOT_ACTIVE';

    private ParameterReleaseDao $releaseDao;
    private ParameterSnapshotDao $snapshotDao;

    public function __construct(?ParameterReleaseDao $releaseDao = null, ?ParameterSnapshotDao $snapshotDao = null)
    {
        $this->releaseDao = $releaseDao ?? new ParameterReleaseDao();
        $this->snapshotDao = $snapshotDao ?? new ParameterSnapshotDao();
    }

    /**
     * 主投影：预算参数 + 来源状态。
     *
     * @return array<string,mixed>
     */
    public function getBudgetParameterSnapshot(): array
    {
        $release = $this->releaseDao->getActive();
        $releaseId = empty($release) ? '0' : (string) $release->release_id;
        $snapshotId = empty($release) ? '0' : (string) $release->snapshot_id;

        $values = $this->loadActiveValues();

        $mappingMultiplier = $this->strVal($values[self::KEY_MAPPING_MULTIPLIER] ?? null);
        $stageExpected = $this->strVal($values[self::KEY_STAGE_EXPECTED_BUDGET] ?? null);
        $stageHardCap = $this->strVal($values[self::KEY_STAGE_HARD_CAP] ?? null);
        $cashSupportCap = $this->strVal($values[self::KEY_CASH_SUPPORT_CAP] ?? null);
        $humanApprovedCap = $this->strVal($values[self::KEY_HUMAN_APPROVED_CAP] ?? null);

        // 任一 required cap 或 multiplier 可用才视为 AVAILABLE；否则 UNAVAILABLE（fail-closed）。
        $available = $releaseId !== '0'
            && $mappingMultiplier !== ''
            && $stageExpected !== ''
            && $stageHardCap !== ''
            && $cashSupportCap !== ''
            && $humanApprovedCap !== '';

        return [
            'source_status'            => $available ? self::SOURCE_AVAILABLE : self::SOURCE_UNAVAILABLE,
            'parameter_release_id'     => $releaseId,
            'snapshot_id'              => $snapshotId,
            'smoothing_rule'           => $this->strVal($values[self::KEY_SMOOTHING_RULE] ?? null),
            'mapping_multiplier'       => $mappingMultiplier,
            'stage_expected_budget'    => $stageExpected,
            'stage_hard_cap'           => $stageHardCap,
            'cash_support_cap'         => $cashSupportCap,
            'human_approved_cap'       => $humanApprovedCap,
            'rounding_precision'       => $this->strVal($values[self::KEY_ROUNDING_PRECISION] ?? null),
            'reason_code'              => $available ? '' : self::REASON_NO_ACTIVE_RELEASE,
        ];
    }

    /**
     * 预算参数是否可用（Active Release + 全部 required cap/multiplier 存在）。
     */
    public function isAvailable(): bool
    {
        return $this->getBudgetParameterSnapshot()['source_status'] === self::SOURCE_AVAILABLE;
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
}
