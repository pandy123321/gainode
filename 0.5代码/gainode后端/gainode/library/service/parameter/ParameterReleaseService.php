<?php

declare(strict_types=1);

namespace library\service\parameter;

use library\dao\parameter\ParameterReleaseDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\parameter\ParameterReleaseModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 参数发布 Service — parameter_releases 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer parameter_releases
 *
 * 状态机（05 §4 canonical Parameter Release，冻结；转移矩阵 2B-2 §4.2 PR1–PR11，CANDIDATE 未 FROZEN）：
 *   draft → pending_approval → approved → scheduled/active → active/paused → rolled_back/archived
 *   - 关键不变量：approved ≠ active（批准后可排期延迟生效）；历史对象使用 ParameterSnapshot。
 *
 * 状态分类（2B-2 §4.1）：
 *   - TRUE_TERMINAL：archived（不可再激活，仅审计查询）
 *   - STABLE：active（可 paused/rolled_back）
 *   - INTERMEDIATE：pending_approval / approved / scheduled / paused
 *
 * 已知 Contract Gap（2B-2 候选合同内部矛盾，不新增状态 NO_SELF_INVENTED_STATE=YES）：
 *   - PR3 `pending_approval → changes_requested` / PR4 `changes_requested → pending_approval`
 *     引用了 `changes_requested` 态，但 canonical enum（§1/§4.1/§12）为 8 态且无 `changes_requested`，
 *     parameter_releases 表亦无该列。本 Service 不实现 PR3/PR4（目标状态不存在，FAIL_CLOSED），
 *     参数发布「要求修改」应由关联 ApprovalRequest 表达，正式 FROZEN 前登记 NEEDS_OWNER_DECISION。
 *
 * SoD（05 §8/§11.3 `PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR`）：
 *   - 表含 approved_by（= PARAM_APPROVER），故 RELEASE_OPERATOR 操作（PR5–PR11）强制 guard
 *     `actorId != approved_by`（审批人不得自行操作发布）。
 *   - PARAM_EDITOR 字段 DDL 缺失，`PARAM_APPROVER != PARAM_EDITOR`（PR2 guard）无法从表校验，
 *     降级为角色名由调用方传入（actorRole），登记为已知限制。
 *
 * 实现策略（fail-closed，与 S02-P05/P06 一致）：
 *   - 纯状态转移（PR1/PR2/PR5–PR11）完整实现（审计 + object_version CAS + audit_event_id 回写）。
 *   - 「生成 active/回滚 ParameterSnapshot」（PR6/PR7/PR10 账本效果）依赖参数值内容（TBC），
 *     由参数冻结后附加，纯状态转移不触碰快照生成（与 S02-P06 状态转移不触碰经济字段一致）。
 *
 * @method ParameterReleaseModel create($data)
 * @method ParameterReleaseModel get($id, string $field = null)
 * @method ParameterReleaseModel find($id)
 * @method ParameterReleaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ParameterReleaseService extends Service
{
    public const EVENT_SUBMITTED   = 'PARAMETER_RELEASE_SUBMITTED';
    public const EVENT_APPROVED    = 'PARAMETER_RELEASE_APPROVED';
    public const EVENT_SCHEDULED   = 'PARAMETER_RELEASE_SCHEDULED';
    public const EVENT_ACTIVATED   = 'PARAMETER_RELEASE_ACTIVATED';
    public const EVENT_PAUSED      = 'PARAMETER_RELEASE_PAUSED';
    public const EVENT_RESUMED     = 'PARAMETER_RELEASE_RESUMED';
    public const EVENT_ROLLED_BACK = 'PARAMETER_RELEASE_ROLLED_BACK';
    public const EVENT_ARCHIVED    = 'PARAMETER_RELEASE_ARCHIVED';

    public function __construct()
    {
        $this->dao = ParameterReleaseDao::class;
        parent::__construct();
    }

    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->getNewDao()->getByIdempotencyKey($idempotencyKey);
    }

    public function getActive()
    {
        return $this->getNewDao()->getActive();
    }

    public function detail(string $releaseId): array
    {
        $r = $this->get($releaseId);
        if (empty($r)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'parameter release not found');
        }
        return [
            'release_id'       => (string) $r->release_id,
            'parameter_keys'   => (string) $r->parameter_keys,
            'status'           => (string) $r->status,
            'draft_version'    => (string) $r->draft_version,
            'approved_by'      => (string) $r->approved_by,
            'scheduled_at'     => (int) $r->scheduled_at,
            'activated_at'     => (int) $r->activated_at,
            'paused_at'        => (int) $r->paused_at,
            'rolled_back_at'   => (int) $r->rolled_back_at,
            'archived_at'      => (int) $r->archived_at,
            'snapshot_id'      => (string) $r->snapshot_id,
            'object_version'   => (int) $r->object_version,
        ];
    }

    /** PR1：draft → pending_approval（提交审批） */
    public function submit(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId, [ParameterReleaseModel::STATUS_DRAFT], ParameterReleaseModel::STATUS_PENDING_APPROVAL,
            self::EVENT_SUBMITTED, $actorId, $actorRole
        );
    }

    /** PR2：pending_approval → approved（审批通过，记录 approved_by） */
    public function approve(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId, [ParameterReleaseModel::STATUS_PENDING_APPROVAL], ParameterReleaseModel::STATUS_APPROVED,
            self::EVENT_APPROVED, $actorId, $actorRole,
            ['approved_by' => $actorId]
        );
    }

    /** PR5：approved → scheduled（排期延迟生效；RELEASE_OPERATOR ≠ PARAM_APPROVER） */
    public function schedule(string $releaseId, string $actorId, string $actorRole, int $scheduledAt): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId, [ParameterReleaseModel::STATUS_APPROVED], ParameterReleaseModel::STATUS_SCHEDULED,
            self::EVENT_SCHEDULED, $actorId, $actorRole,
            ['scheduled_at' => $scheduledAt],
            fn (ParameterReleaseModel $r) => $this->guardNotApprover($r, $actorId)
        );
    }

    /** PR6：approved → active（立即生效；RELEASE_OPERATOR ≠ PARAM_APPROVER） */
    public function activateFromApproved(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId, [ParameterReleaseModel::STATUS_APPROVED], ParameterReleaseModel::STATUS_ACTIVE,
            self::EVENT_ACTIVATED, $actorId, $actorRole,
            ['activated_at' => time()],
            fn (ParameterReleaseModel $r) => $this->guardNotApprover($r, $actorId)
        );
    }

    /** PR7：scheduled → active（排期到期/提前激活；RELEASE_OPERATOR ≠ PARAM_APPROVER） */
    public function activateFromScheduled(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId, [ParameterReleaseModel::STATUS_SCHEDULED], ParameterReleaseModel::STATUS_ACTIVE,
            self::EVENT_ACTIVATED, $actorId, $actorRole,
            ['activated_at' => time()],
            fn (ParameterReleaseModel $r) => $this->guardNotApprover($r, $actorId)
        );
    }

    /** PR8：active → paused（临时停用，不删历史） */
    public function pause(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId, [ParameterReleaseModel::STATUS_ACTIVE], ParameterReleaseModel::STATUS_PAUSED,
            self::EVENT_PAUSED, $actorId, $actorRole,
            ['paused_at' => time()],
            fn (ParameterReleaseModel $r) => $this->guardNotApprover($r, $actorId)
        );
    }

    /** PR9：paused → active（恢复） */
    public function resume(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId, [ParameterReleaseModel::STATUS_PAUSED], ParameterReleaseModel::STATUS_ACTIVE,
            self::EVENT_RESUMED, $actorId, $actorRole,
            ['activated_at' => time()],
            fn (ParameterReleaseModel $r) => $this->guardNotApprover($r, $actorId)
        );
    }

    /** PR10：active/paused → rolled_back（回滚到上一版本，保留审计链） */
    public function rollback(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId,
            [ParameterReleaseModel::STATUS_ACTIVE, ParameterReleaseModel::STATUS_PAUSED],
            ParameterReleaseModel::STATUS_ROLLED_BACK,
            self::EVENT_ROLLED_BACK, $actorId, $actorRole,
            ['rolled_back_at' => time()],
            fn (ParameterReleaseModel $r) => $this->guardNotApprover($r, $actorId)
        );
    }

    /** PR11：rolled_back/scheduled → archived（不可再激活） */
    public function archive(string $releaseId, string $actorId, string $actorRole): ParameterReleaseModel
    {
        return $this->transition(
            $releaseId,
            [ParameterReleaseModel::STATUS_ROLLED_BACK, ParameterReleaseModel::STATUS_SCHEDULED],
            ParameterReleaseModel::STATUS_ARCHIVED,
            self::EVENT_ARCHIVED, $actorId, $actorRole,
            ['archived_at' => time()],
            fn (ParameterReleaseModel $r) => $this->guardNotApprover($r, $actorId)
        );
    }

    private function transition(
        string $releaseId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole,
        array $extraFields = [],
        ?callable $guard = null
    ): ParameterReleaseModel {
        return (new TransactionBoundary())->run(function () use (
            $releaseId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole, $extraFields, $guard
        ) {
            $release = $this->get($releaseId);
            if (empty($release)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'parameter release not found');
            }
            $current = (string) $release->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid parameter release state transition');
            }
            if ($guard !== null) {
                $guard($release);
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $releaseId, $release->case_id);

            $fields = array_merge([
                'status'         => $toStatus,
                'audit_event_id' => $auditId,
                'object_version' => (int) $release->object_version + 1,
                'updated_time'   => time(),
            ], $extraFields);

            $affected = Db::connection('mysql')
                ->table('parameter_releases')
                ->where('release_id', $releaseId)
                ->where('status', $current)
                ->where('object_version', (int) $release->object_version)
                ->update($fields);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'parameter release state transition CAS conflict');
            }

            return $this->get($releaseId);
        });
    }

    /** SoD：RELEASE_OPERATOR ≠ PARAM_APPROVER（审批人不得自行操作发布） */
    private function guardNotApprover(ParameterReleaseModel $release, string $actorId): void
    {
        if ((string) $release->approved_by !== '' && (string) $release->approved_by === $actorId) {
            throw new DomainException(ErrorDict::POLICY_DENIED, 'release operator must differ from approver (SoD)');
        }
    }

    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId,
        ?string $caseId
    ): string {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'       => $auditId,
            'event_code'           => $eventCode,
            'actor_id'             => $actorId,
            'actor_role'           => $actorRole,
            'target_object_type'   => 'parameter_releases',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => '',
            'approval_id'          => '0',
            'case_id'              => ($caseId !== null && $caseId !== '') ? $caseId : '0',
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
