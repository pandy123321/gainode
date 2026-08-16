<?php

declare(strict_types=1);

namespace library\service\aiops;

use library\dict\ErrorDict;
use library\service\idempotency\IdempotencyStore;
use library\service\outbox\OutboxStore;
use support\exception\DomainException;

/**
 * 内部 AI 经济引擎 · 流水线编排（S02-P08，07 §S02-P08 固定步骤 1-7）。
 *
 * 计算流水线（02 §5.4）：
 *   confirmed_profit → reference_profit → mapped_apt_budget → daily_ai_budget
 *
 * 职责：
 *   - compute()：纯计算流水线（显式参数，可离线测试）。
 *   - generateFromActiveRelease()：生产入口，从 Active Release/Snapshot 读参数（fail-closed）。
 *   - persist()：预算决定持久化 + AuditEvent + Outbox（预算持久对象未冻结 → fail-closed）。
 *   - buildIdempotencyKey()：source + parameter release + snapshot + business date。
 *
 * 冻结状态：预算持久对象未冻结；smoothing/price/multiplier/cap/rounding 06 全 TBC。
 * 引擎保持 closed，不写默认值。
 */
class AiBudgetEngine
{
    private ConfirmedProfitAdapter $adapter;
    private ReferenceProfitService $referenceService;
    private AptBudgetMappingService $mappingService;
    private DailyAIBudgetService $dailyService;
    private AiBudgetParameterReader $reader;

    public function __construct(?AiBudgetParameterReader $reader = null)
    {
        $this->adapter          = new ConfirmedProfitAdapter();
        $this->referenceService = new ReferenceProfitService();
        $this->mappingService   = new AptBudgetMappingService();
        $this->dailyService     = new DailyAIBudgetService();
        $this->reader           = $reader ?? new AiBudgetParameterReader();
    }

    /**
     * 纯计算流水线（显式参数）。
     *
     * @param array<string,mixed> $input 内部可审计执行结果（见 ConfirmedProfitAdapter::normalize）
     * @param array<string,mixed> $context
     *   - smoothing: array|null（ReferenceProfitService 上下文）
     *   - apt_reference_price: string|null
     *   - mapping_multiplier: string|null
     *   - caps: array<string,string>|null（四 cap，见 DailyAIBudgetService::REQUIRED_CAPS）
     *   - parameter_release_id: string
     *   - snapshot_id: string
     *   - business_date: string
     * @return BudgetDecision
     * @throws DomainException
     */
    public function compute(array $input, array $context = []): BudgetDecision
    {
        // 步骤 1：确认/可追溯/归一化
        $confirmed = $this->adapter->normalize($input);

        // 步骤 2/3：reference_profit（<=0 短路；>0 smoothing）
        $reference = $this->referenceService->computeReference(
            $confirmed['confirmed_profit'],
            $context['smoothing'] ?? null
        );

        // 步骤 4/5：mapped_apt_budget
        $mapped = $this->mappingService->mapToApt(
            $reference['reference_profit'],
            $context['apt_reference_price'] ?? null,
            $context['mapping_multiplier'] ?? null
        );

        // 步骤 6：daily_ai_budget（五 cap 取最小）
        $caps = $context['caps'] ?? null;
        $candidates = [
            DailyAIBudgetService::CAP_MAPPED_APT_BUDGET => $mapped['mapped_apt_budget'],
        ];
        foreach (DailyAIBudgetService::REQUIRED_CAPS as $capKey) {
            $candidates[$capKey] = $caps[$capKey] ?? null;
        }
        $daily = $this->dailyService->computeDaily($candidates);

        $parameterReleaseId = (string) ($context['parameter_release_id'] ?? '0');
        $snapshotId         = (string) ($context['snapshot_id'] ?? '0');
        $businessDate       = (string) ($context['business_date'] ?? '');

        $idemKey = $this->buildIdempotencyKey(
            $confirmed['source_hash'],
            $parameterReleaseId,
            $snapshotId,
            $businessDate
        );

        return new BudgetDecision([
            'confirmed_profit'      => $confirmed['confirmed_profit'],
            'reference_profit'      => $reference['reference_profit'],
            'mapped_apt_budget'     => $mapped['mapped_apt_budget'],
            'daily_ai_budget'       => $daily,
            'source_status'         => 'AVAILABLE',
            'algorithm'             => $reference['algorithm'],
            'rule_version'          => $reference['rule_version'],
            'source_hash'           => $confirmed['source_hash'],
            'parameter_release_id'  => $parameterReleaseId,
            'snapshot_id'           => $snapshotId,
            'business_date'         => $businessDate,
            'idempotency_key'       => $idemKey,
        ]);
    }

    /**
     * 生产入口：从 Active Release/Snapshot 读预算参数后计算。
     *
     * 因 smoothing/price/multiplier/cap 06 全 TBC → 参数不可用 → fail-closed。
     *
     * @throws DomainException
     */
    public function generateFromActiveRelease(array $input): BudgetDecision
    {
        $params = $this->reader->getBudgetParameterSnapshot();
        if ($params['source_status'] !== AiBudgetParameterReader::SOURCE_AVAILABLE) {
            throw new DomainException(
                ErrorDict::DEPENDENCY_UNAVAILABLE,
                'AI budget parameters not active (06 TBC) — engine closed'
            );
        }

        return $this->compute($input, [
            'mapping_multiplier'     => $params['mapping_multiplier'],
            'caps'                   => [
                DailyAIBudgetService::CAP_STAGE_EXPECTED => $params['stage_expected_budget'],
                DailyAIBudgetService::CAP_STAGE_HARD     => $params['stage_hard_cap'],
                DailyAIBudgetService::CAP_CASH_SUPPORT   => $params['cash_support_cap'],
                DailyAIBudgetService::CAP_HUMAN_APPROVED => $params['human_approved_cap'],
            ],
            'parameter_release_id'   => $params['parameter_release_id'],
            'snapshot_id'            => $params['snapshot_id'],
            'business_date'          => date('Y-m-d'),
        ]);
    }

    /**
     * 步骤 7：持久化预算决定 + AuditEvent + Outbox。
     *
     * 预算持久对象未冻结 → fail-closed（不建表、不写默认值）。
     *
     * @throws DomainException
     */
    public function persist(BudgetDecision $decision, ?IdempotencyStore $idem = null, ?OutboxStore $outbox = null): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'budget decision persistence object not frozen (no DDL)'
        );
    }

    /**
     * 步骤 7：幂等键 = sha256(source | parameter_release | snapshot | business_date) 前 64 位。
     */
    public function buildIdempotencyKey(
        string $sourceHash,
        string $parameterReleaseId,
        string $snapshotId,
        string $businessDate
    ): string {
        return substr(
            hash('sha256', $sourceHash . '|' . $parameterReleaseId . '|' . $snapshotId . '|' . $businessDate),
            0,
            64
        );
    }
}
