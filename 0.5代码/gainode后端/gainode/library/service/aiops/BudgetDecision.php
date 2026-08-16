<?php

declare(strict_types=1);

namespace library\service\aiops;

/**
 * 内部 AI 经济引擎 · 预算决定 DTO（S02-P08，07 §S02-P08 内部 DTO/Response）。
 *
 * 承载四个产出字段（confirmed_profit / reference_profit / mapped_apt_budget /
 * daily_ai_budget）及其版本/审计元数据，支持审计重放。
 *
 * 内部/外部边界（07 §S02-P08 步骤 8）：
 *   - forInternal()：完整结构（含 source_hash / algorithm / rule_version /
 *     parameter_release_id / snapshot_id / idempotency_key）。
 *   - forExternal()：脱敏结构，仅四字段 + source_status，不暴露供应商、arbitrage
 *     signal、profit detail、position、cap 明细或内部模型参数。
 */
class BudgetDecision
{
    /** @var string[] 对外禁止泄露的敏感字段（负向扫描基准） */
    public const SENSITIVE_KEYS = [
        'source_hash',
        'source_object_type',
        'source_object_id',
        'algorithm',
        'rule_version',
        'input_hash',
        'parameter_release_id',
        'snapshot_id',
        'idempotency_key',
        'mapping_multiplier',
        'apt_reference_price',
        'stage_expected_budget',
        'stage_hard_cap',
        'cash_support_cap',
        'human_approved_cap',
        'rounding_precision',
        'position',
        'signal',
        'supplier',
    ];

    private string $confirmedProfit;
    private string $referenceProfit;
    private string $mappedAptBudget;
    private string $dailyAiBudget;
    private string $sourceStatus;
    private string $algorithm;
    private string $ruleVersion;
    private string $sourceHash;
    private string $parameterReleaseId;
    private string $snapshotId;
    private string $businessDate;
    private string $idempotencyKey;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->confirmedProfit    = (string) ($data['confirmed_profit'] ?? '0');
        $this->referenceProfit    = (string) ($data['reference_profit'] ?? '0');
        $this->mappedAptBudget    = (string) ($data['mapped_apt_budget'] ?? '0');
        $this->dailyAiBudget      = (string) ($data['daily_ai_budget'] ?? '0');
        $this->sourceStatus       = (string) ($data['source_status'] ?? 'UNAVAILABLE');
        $this->algorithm          = (string) ($data['algorithm'] ?? '');
        $this->ruleVersion        = (string) ($data['rule_version'] ?? '');
        $this->sourceHash         = (string) ($data['source_hash'] ?? '');
        $this->parameterReleaseId = (string) ($data['parameter_release_id'] ?? '0');
        $this->snapshotId         = (string) ($data['snapshot_id'] ?? '0');
        $this->businessDate       = (string) ($data['business_date'] ?? '');
        $this->idempotencyKey     = (string) ($data['idempotency_key'] ?? '');
    }

    /**
     * 完整内部结构（审计重放）。
     *
     * @return array<string,mixed>
     */
    public function forInternal(): array
    {
        return [
            'confirmed_profit'      => $this->confirmedProfit,
            'reference_profit'      => $this->referenceProfit,
            'mapped_apt_budget'     => $this->mappedAptBudget,
            'daily_ai_budget'       => $this->dailyAiBudget,
            'source_status'         => $this->sourceStatus,
            'algorithm'             => $this->algorithm,
            'rule_version'          => $this->ruleVersion,
            'source_hash'           => $this->sourceHash,
            'parameter_release_id'  => $this->parameterReleaseId,
            'snapshot_id'           => $this->snapshotId,
            'business_date'         => $this->businessDate,
            'idempotency_key'       => $this->idempotencyKey,
        ];
    }

    /**
     * 脱敏对外结构（仅四字段 + source_status，C 端安全）。
     *
     * @return array<string,mixed>
     */
    public function forExternal(): array
    {
        return [
            'confirmed_profit'  => $this->confirmedProfit,
            'reference_profit'  => $this->referenceProfit,
            'mapped_apt_budget' => $this->mappedAptBudget,
            'daily_ai_budget'   => $this->dailyAiBudget,
            'source_status'     => $this->sourceStatus,
        ];
    }

    /**
     * 校验对外结构不含任何敏感键（步骤 8 负向扫描）。
     */
    public function assertExternalSafe(array $external): bool
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            if (array_key_exists($key, $external)) {
                return false;
            }
        }
        return true;
    }
}
