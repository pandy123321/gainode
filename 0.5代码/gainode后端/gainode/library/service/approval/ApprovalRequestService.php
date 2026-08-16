<?php

declare(strict_types=1);

namespace library\service\approval;

use library\dao\approval\ApprovalRequestDao;
use library\dict\ErrorDict;
use library\model\approval\ApprovalRequestModel;
use library\model\audit\AuditEventModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 审批请求 Service — approval_requests 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer approval_requests
 *
 * 状态机（05 §4 canonical Approval，复制冻结；转移矩阵 2B-2 §3 AR1–AR8，CANDIDATE 未 FROZEN）：
 *   draft → pending → changes_requested ⇄ pending
 *   pending → approved / rejected
 *   approved → executing → executed / failed
 *
 * 状态分类（2B-2 §3.1）：
 *   - TRUE_TERMINAL：executed / rejected
 *   - RETRYABLE_TERMINAL：failed（可升级/重试，形成新执行对象）
 *   - INTERMEDIATE：pending / changes_requested / approved / executing
 *
 * 关键不变量（2B-2 §3）：
 *   - 审批人 ≠ 申请人（SoD，approve/reject/requestChanges 强制 guard）。
 *   - Approval 回滚不修改旧 Approval 状态，形成新执行对象 + 审计链。
 *   - executing ≠ executed。
 *
 * 实现策略（fail-closed，与 S02-P05/P06 一致）：
 *   - 纯状态转移（AR1–AR8）完整实现（审计 + object_version CAS + audit_event_id 回写）。
 *   - 「执行」的具体业务副作用（依 request_type 的账本/资金变更）由对应 Authoritative Writer
 *     承担，本 Service 只做状态流转，不触碰经济字段（与 S02-P05 Settlement 状态转移不计算金额一致）。
 *
 * @method ApprovalRequestModel create($data)
 * @method ApprovalRequestModel get($id, string $field = null)
 * @method ApprovalRequestModel find($id)
 * @method ApprovalRequestModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ApprovalRequestService extends Service
{
    public const EVENT_SUBMITTED            = 'APPROVAL_SUBMITTED';
    public const EVENT_CHANGES_REQUESTED    = 'APPROVAL_CHANGES_REQUESTED';
    public const EVENT_RESUBMITTED          = 'APPROVAL_RESUBMITTED';
    public const EVENT_APPROVED             = 'APPROVAL_APPROVED';
    public const EVENT_REJECTED             = 'APPROVAL_REJECTED';
    public const EVENT_EXECUTION_STARTED    = 'APPROVAL_EXECUTION_STARTED';
    public const EVENT_EXECUTION_COMPLETED  = 'APPROVAL_EXECUTION_COMPLETED';
    public const EVENT_EXECUTION_FAILED     = 'APPROVAL_EXECUTION_FAILED';

    public function __construct()
    {
        $this->dao = ApprovalRequestDao::class;
        parent::__construct();
    }

    public function getByObject(string $objectType, string $objectId)
    {
        return $this->getNewDao()->getByObject($objectType, $objectId);
    }

    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->getNewDao()->getByIdempotencyKey($idempotencyKey);
    }

    public function detail(string $approvalId): array
    {
        $a = $this->get($approvalId);
        if (empty($a)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'approval request not found');
        }
        return [
            'approval_id'              => (string) $a->approval_id,
            'request_type'             => (string) $a->request_type,
            'request_object_type'      => (string) $a->request_object_type,
            'request_object_id'        => (string) $a->request_object_id,
            'status'                   => (string) $a->status,
            'submitted_by'             => (string) $a->submitted_by,
            'submitter_role'           => (string) $a->submitter_role,
            'assigned_to'              => (string) $a->assigned_to,
            'decided_by'               => (string) $a->decided_by,
            'decided_at'               => (int) $a->decided_at,
            'reason_key'               => (string) $a->reason_key,
            'changes_requested_reason' => (string) $a->changes_requested_reason,
            'execution_id'             => (string) $a->execution_id,
            'case_id'                  => (string) $a->case_id,
            'object_version'           => (int) $a->object_version,
        ];
    }

    /** AR1：draft → pending（提交审批） */
    public function submit(string $approvalId, string $actorId, string $actorRole): ApprovalRequestModel
    {
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_DRAFT], ApprovalRequestModel::STATUS_PENDING,
            self::EVENT_SUBMITTED, $actorId, $actorRole
        );
    }

    /** AR2：pending → changes_requested（要求修改，审批人 ≠ 申请人） */
    public function requestChanges(string $approvalId, string $actorId, string $actorRole, string $reason = ''): ApprovalRequestModel
    {
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_PENDING], ApprovalRequestModel::STATUS_CHANGES_REQUESTED,
            self::EVENT_CHANGES_REQUESTED, $actorId, $actorRole,
            ['decided_by' => $actorId, 'decided_at' => time(), 'changes_requested_reason' => $reason],
            fn (ApprovalRequestModel $a) => $this->guardNotSelf($a, $actorId)
        );
    }

    /** AR3：changes_requested → pending（修改后重提，不篡改原审批记录） */
    public function resubmit(string $approvalId, string $actorId, string $actorRole): ApprovalRequestModel
    {
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_CHANGES_REQUESTED], ApprovalRequestModel::STATUS_PENDING,
            self::EVENT_RESUBMITTED, $actorId, $actorRole
        );
    }

    /** AR4：pending → approved（审批通过，审批人 ≠ 申请人） */
    public function approve(string $approvalId, string $actorId, string $actorRole, string $reasonKey = ''): ApprovalRequestModel
    {
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_PENDING], ApprovalRequestModel::STATUS_APPROVED,
            self::EVENT_APPROVED, $actorId, $actorRole,
            ['decided_by' => $actorId, 'decided_at' => time(), 'reason_key' => $reasonKey],
            fn (ApprovalRequestModel $a) => $this->guardNotSelf($a, $actorId)
        );
    }

    /** AR5：pending → rejected（审批驳回，审批人 ≠ 申请人） */
    public function reject(string $approvalId, string $actorId, string $actorRole, string $reasonKey = ''): ApprovalRequestModel
    {
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_PENDING], ApprovalRequestModel::STATUS_REJECTED,
            self::EVENT_REJECTED, $actorId, $actorRole,
            ['decided_by' => $actorId, 'decided_at' => time(), 'reason_key' => $reasonKey],
            fn (ApprovalRequestModel $a) => $this->guardNotSelf($a, $actorId)
        );
    }

    /** AR6：approved → executing（开始执行） */
    public function startExecution(string $approvalId, string $actorId, string $actorRole, string $executionId = ''): ApprovalRequestModel
    {
        $extra = [];
        if ($executionId !== '') {
            $extra['execution_id'] = $executionId;
        }
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_APPROVED], ApprovalRequestModel::STATUS_EXECUTING,
            self::EVENT_EXECUTION_STARTED, $actorId, $actorRole, $extra
        );
    }

    /** AR7：executing → executed（执行完成；具体业务副作用由对应 Writer 承担） */
    public function completeExecution(string $approvalId, string $actorId, string $actorRole): ApprovalRequestModel
    {
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_EXECUTING], ApprovalRequestModel::STATUS_EXECUTED,
            self::EVENT_EXECUTION_COMPLETED, $actorId, $actorRole
        );
    }

    /** AR8：executing → failed（执行异常；不半执行，冻结） */
    public function failExecution(string $approvalId, string $actorId, string $actorRole): ApprovalRequestModel
    {
        return $this->transition(
            $approvalId, [ApprovalRequestModel::STATUS_EXECUTING], ApprovalRequestModel::STATUS_FAILED,
            self::EVENT_EXECUTION_FAILED, $actorId, $actorRole
        );
    }

    private function transition(
        string $approvalId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole,
        array $extraFields = [],
        ?callable $guard = null
    ): ApprovalRequestModel {
        return (new TransactionBoundary())->run(function () use (
            $approvalId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole, $extraFields, $guard
        ) {
            $approval = $this->get($approvalId);
            if (empty($approval)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'approval request not found');
            }
            $current = (string) $approval->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid approval state transition');
            }
            if ($guard !== null) {
                $guard($approval);
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $approvalId, $approval->case_id);

            $fields = array_merge([
                'status'         => $toStatus,
                'audit_event_id' => $auditId,
                'object_version' => (int) $approval->object_version + 1,
                'updated_time'   => time(),
            ], $extraFields);

            $affected = Db::connection('mysql')
                ->table('approval_requests')
                ->where('approval_id', $approvalId)
                ->where('status', $current)
                ->where('object_version', (int) $approval->object_version)
                ->update($fields);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'approval state transition CAS conflict');
            }

            return $this->get($approvalId);
        });
    }

    /** SoD：审批人 ≠ 申请人（approval actor != execution authority） */
    private function guardNotSelf(ApprovalRequestModel $approval, string $actorId): void
    {
        if ((string) $approval->submitted_by === $actorId) {
            throw new DomainException(ErrorDict::POLICY_DENIED, 'approval actor must differ from submitter (SoD)');
        }
    }

    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId,
        string $caseId
    ): string {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'       => $auditId,
            'event_code'           => $eventCode,
            'actor_id'             => $actorId,
            'actor_role'           => $actorRole,
            'target_object_type'   => 'approval_requests',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => '',
            'approval_id'          => $targetObjectId,
            'case_id'              => ($caseId !== null && $caseId !== '') ? $caseId : '0',
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
