<?php

declare(strict_types=1);

namespace library\service\risk;

use library\dao\risk\RiskCaseDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\risk\RiskCaseModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\middleware\RequestContext;
use support\utils\Random;

/**
 * 风控案件 Service — risk_cases 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer risk_cases
 *
 * 状态机（05 §4 V2.4 canonical，Owner 裁决 2B2-ENUM-03；转移矩阵 2B-2 §8.3，CANDIDATE 未 FROZEN）：
 *   open → investigating → under_review → resolved → closed
 *   旁路：open → closed（误报关闭）；resolved → investigating（申诉重开，appeal_eligible=1）
 *   - appeal_eligible 为字段非状态。
 *
 * 状态分类（2B-2 §8.3）：
 *   - TRUE_TERMINAL：closed
 *   - STABLE：resolved（申诉窗口内保持此态，可 closed 或 investigating 重开）
 *   - INTERMEDIATE：investigating / under_review
 *
 * SoD（05 §8/§11.3 `RISK_ANALYST != RISK_APPROVER`）：
 *   - 表含 detected_by（检测人）、reviewed_by（处置审批人）。
 *   - resolve（under_review → resolved，RISK_APPROVER）强制 guard `actorId != detected_by`
 *     （检测/分析者不得审批本人案件，对「RISK_ANALYST != RISK_APPROVER」的可校验近似）。
 *   - 分析者（RISK_ANALYST）字段 DDL 缺失，完整「analyst != approver」无法从表校验，登记为已知限制。
 *
 * 实现策略（fail-closed，与 S02-P05/P06 一致）：
 *   - 纯状态转移完整实现（审计 + object_version CAS + audit_event_id 回写）。
 *   - execute（依 disposition 执行 restrictions 冻结/放行）依赖风险策略（TBC）→ FAIL_CLOSED。
 *   - 状态转移不触碰 restrictions 字段（由 execute 在策略冻结后附加）。
 *
 * @method RiskCaseModel create($data)
 * @method RiskCaseModel get($id, string $field = null)
 * @method RiskCaseModel find($id)
 * @method RiskCaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RiskCaseService extends Service
{
    public const EVENT_INVESTIGATING = 'RISK_CASE_INVESTIGATING';
    public const EVENT_UNDER_REVIEW  = 'RISK_CASE_UNDER_REVIEW';
    public const EVENT_RESOLVED      = 'RISK_CASE_RESOLVED';
    public const EVENT_CLOSED        = 'RISK_CASE_CLOSED';
    public const EVENT_REOPENED      = 'RISK_CASE_REOPENED';

    // ---- 05 §8 最小角色（本包仅引用这 2 个，canonical 冻结；两者互斥 SoD）----
    public const ROLE_RISK_ANALYST  = 'RISK_ANALYST';
    public const ROLE_RISK_APPROVER = 'RISK_APPROVER';

    public function __construct()
    {
        $this->dao = RiskCaseDao::class;
        parent::__construct();
    }

    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->getNewDao()->getByIdempotencyKey($idempotencyKey);
    }

    public function listByUser(string $userId): array
    {
        $items = [];
        foreach ($this->getByUser($userId) as $c) {
            $items[] = [
                'case_id'       => (string) $c->case_id,
                'risk_type'     => (string) $c->risk_type,
                'severity'      => (string) $c->severity,
                'status'        => (string) $c->status,
                'disposition'   => (string) $c->disposition,
                'created_time'  => (int) $c->getRawOriginal('created_time'),
            ];
        }
        return ['risk_cases' => $items];
    }

    public function detail(string $caseId): array
    {
        $c = $this->get($caseId);
        if (empty($c)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'risk case not found');
        }
        return [
            'case_id'                => (string) $c->case_id,
            'user_id'                => (string) $c->user_id,
            'risk_type'              => (string) $c->risk_type,
            'severity'               => (string) $c->severity,
            'status'                 => (string) $c->status,
            'detected_at'            => (int) $c->detected_at,
            'detected_by'            => (string) $c->detected_by,
            'reviewed_by'            => (string) $c->reviewed_by,
            'disposition'            => (string) $c->disposition,
            'disposition_reason_key' => (string) $c->disposition_reason_key,
            'restrictions'           => (string) $c->restrictions,
            'appeal_eligible'        => (int) $c->appeal_eligible,
            'object_version'         => (int) $c->object_version,
        ];
    }

    /** open → investigating（RISK_ANALYST 开始分析） */
    public function startInvestigate(string $caseId, string $actorId, string $actorRole): RiskCaseModel
    {
        return $this->transition(
            $caseId, [RiskCaseModel::STATUS_OPEN], RiskCaseModel::STATUS_INVESTIGATING,
            self::EVENT_INVESTIGATING, $actorId, $actorRole,
            [],
            fn (RiskCaseModel $c) => $this->guardRole([self::ROLE_RISK_ANALYST], $actorRole)
        );
    }

    /** investigating → under_review（RISK_ANALYST 提交处置建议） */
    public function submitDecision(string $caseId, string $actorId, string $actorRole): RiskCaseModel
    {
        return $this->transition(
            $caseId, [RiskCaseModel::STATUS_INVESTIGATING], RiskCaseModel::STATUS_UNDER_REVIEW,
            self::EVENT_UNDER_REVIEW, $actorId, $actorRole,
            [],
            fn (RiskCaseModel $c) => $this->guardRole([self::ROLE_RISK_ANALYST], $actorRole)
        );
    }

    /** under_review → resolved（RISK_APPROVER 批准处置；处置措施执行依赖风险策略 TBC → FAIL_CLOSED） */
    public function resolve(
        string $caseId,
        string $actorId,
        string $actorRole,
        string $disposition = '',
        string $dispositionReasonKey = ''
    ): RiskCaseModel {
        return $this->guardedFailClosed(
            $caseId,
            fn (RiskCaseModel $c) => $this->guardApprover($c, $actorId, $actorRole),
            'RiskCase resolve (executing restriction disposition) depends on risk restriction policy (TBC) — not frozen'
        );
    }

    /** resolved → closed（归档终态；RISK_APPROVER） */
    public function closeResolved(string $caseId, string $actorId, string $actorRole): RiskCaseModel
    {
        return $this->transition(
            $caseId, [RiskCaseModel::STATUS_RESOLVED], RiskCaseModel::STATUS_CLOSED,
            self::EVENT_CLOSED, $actorId, $actorRole,
            [],
            fn (RiskCaseModel $c) => $this->guardRole([self::ROLE_RISK_APPROVER], $actorRole)
        );
    }

    /** open → closed（误报关闭；RISK_APPROVER） */
    public function closeFalsePositive(string $caseId, string $actorId, string $actorRole): RiskCaseModel
    {
        return $this->transition(
            $caseId, [RiskCaseModel::STATUS_OPEN], RiskCaseModel::STATUS_CLOSED,
            self::EVENT_CLOSED, $actorId, $actorRole,
            [],
            fn (RiskCaseModel $c) => $this->guardRole([self::ROLE_RISK_APPROVER], $actorRole)
        );
    }

    /** resolved → investigating（申诉重开，appeal_eligible=1；RISK_APPROVER） */
    public function reopenAppeal(string $caseId, string $actorId, string $actorRole): RiskCaseModel
    {
        return $this->transition(
            $caseId, [RiskCaseModel::STATUS_RESOLVED], RiskCaseModel::STATUS_INVESTIGATING,
            self::EVENT_REOPENED, $actorId, $actorRole,
            [],
            function (RiskCaseModel $c) use ($actorRole) {
                $this->guardRole([self::ROLE_RISK_APPROVER], $actorRole);
                if ((int) $c->appeal_eligible !== 1) {
                    throw new DomainException(ErrorDict::POLICY_DENIED, 'risk case is not appeal eligible');
                }
            }
        );
    }

    /**
     * 执行处置措施（restrictions 冻结/放行）。依赖风险策略（TBC）→ FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function execute(string $caseId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'RiskCase execute depends on risk restriction policy (TBC) — not frozen'
        );
    }

    private function transition(
        string $caseId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole,
        array $extraFields = [],
        ?callable $guard = null
    ): RiskCaseModel {
        return (new TransactionBoundary())->run(function () use (
            $caseId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole, $extraFields, $guard
        ) {
            $case = $this->get($caseId);
            if (empty($case)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'risk case not found');
            }
            $current = (string) $case->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid risk case state transition');
            }
            if ($guard !== null) {
                $guard($case);
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $caseId);

            $fields = array_merge([
                'status'         => $toStatus,
                'audit_event_id' => $auditId,
                'object_version' => (int) $case->object_version + 1,
                'updated_time'   => time(),
            ], $extraFields);

            $affected = Db::connection('mysql')
                ->table('risk_cases')
                ->where('case_id', $caseId)
                ->where('status', $current)
                ->where('object_version', (int) $case->object_version)
                ->update($fields);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'risk case state transition CAS conflict');
            }

            return $this->get($caseId);
        });
    }

    /** SoD + 角色：处置审批人 ≠ 检测人（RISK_APPROVER != RISK_ANALYST） */
    private function guardApprover(RiskCaseModel $case, string $actorId, string $actorRole): void
    {
        $this->guardRole([self::ROLE_RISK_APPROVER], $actorRole);
        $detectedBy = (string) $case->detected_by;
        if ($detectedBy !== '' && $detectedBy !== '0' && $detectedBy === $actorId) {
            throw new DomainException(ErrorDict::POLICY_DENIED, 'risk approver must differ from detector (SoD)');
        }
    }

    /** 角色白名单：不在冻结角色集合内 → AUTH_FORBIDDEN（fail-closed） */
    private function guardRole(array $allowedRoles, string $actorRole): void
    {
        if (!in_array($actorRole, $allowedRoles, true)) {
            throw new DomainException(ErrorDict::AUTH_FORBIDDEN, 'risk case actor role forbidden');
        }
    }

    /** 先过守卫（approver 角色 + SoD）再依赖 fail-closed；守卫失败优先于依赖不可用。 */
    private function guardedFailClosed(string $caseId, callable $guard, string $dependency): RiskCaseModel
    {
        $case = $this->get($caseId);
        if (empty($case)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'risk case not found');
        }
        $guard($case);
        throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, $dependency);
    }

    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId
    ): string {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'       => $auditId,
            'event_code'           => $eventCode,
            'actor_id'             => $actorId,
            'actor_role'           => $actorRole,
            'target_object_type'   => 'risk_cases',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => RequestContext::getRequestId(),
            'approval_id'          => '0',
            'case_id'              => $targetObjectId,
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
