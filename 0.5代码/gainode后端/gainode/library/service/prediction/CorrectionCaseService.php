<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\CorrectionCaseDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\prediction\CorrectionCaseModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 纠错案件 Service — correction_cases 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer correction_cases
 *
 * 状态机（05 §4 V2.3 canonical，Owner 2B1-ENUM-03）：
 *   pending → approved → executing → completed / pending → rejected / executing → failed（可重试回 executing）
 *
 * @method CorrectionCaseModel create($data)
 * @method CorrectionCaseModel get($id, string $field = null)
 * @method CorrectionCaseModel find($id)
 * @method CorrectionCaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class CorrectionCaseService extends Service
{
    public const EVENT_APPROVED  = 'CORRECTION_CASE_APPROVED';
    public const EVENT_REJECTED  = 'CORRECTION_CASE_REJECTED';
    public const EVENT_EXECUTING = 'CORRECTION_CASE_EXECUTING';
    public const EVENT_FAILED    = 'CORRECTION_CASE_FAILED';
    public const EVENT_RETRIED   = 'CORRECTION_CASE_RETRIED';

    public function __construct()
    {
        $this->dao = CorrectionCaseDao::class;
        parent::__construct();
    }

    public function getByMarket(string $marketId)
    {
        return $this->getNewDao()->getByMarket($marketId);
    }

    public function createCase(array $data, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'CorrectionCase create depends on correction contract/approval (2B-1 not frozen)'
        );
    }

    public function complete(string $correctionId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'CorrectionCase complete depends on correction ledger write (not frozen)'
        );
    }

    public function detail(string $correctionId): array
    {
        $c = $this->get($correctionId);
        if (empty($c)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'correction case not found');
        }
        return [
            'correction_id'  => (string) $c->correction_id,
            'market_id'      => (string) $c->market_id,
            'result_id_old'  => (string) $c->result_id_old,
            'result_id_new'  => (string) $c->result_id_new,
            'status'         => (string) $c->status,
            'approved_by'    => (string) $c->approved_by,
            'case_id'        => (string) $c->case_id,
            'approval_id'    => (string) $c->approval_id,
            'rule_version'   => (string) $c->rule_version,
        ];
    }

    /** pending → approved（RISK_APPROVER） */
    public function approve(string $correctionId, string $actorId, string $actorRole): CorrectionCaseModel
    {
        return $this->transition(
            $correctionId, [CorrectionCaseModel::STATUS_PENDING], CorrectionCaseModel::STATUS_APPROVED,
            self::EVENT_APPROVED, $actorId, $actorRole
        );
    }

    /** pending → rejected（RISK_APPROVER） */
    public function reject(string $correctionId, string $actorId, string $actorRole): CorrectionCaseModel
    {
        return $this->transition(
            $correctionId, [CorrectionCaseModel::STATUS_PENDING], CorrectionCaseModel::STATUS_REJECTED,
            self::EVENT_REJECTED, $actorId, $actorRole
        );
    }

    /** approved → executing */
    public function execute(string $correctionId, string $actorId, string $actorRole): CorrectionCaseModel
    {
        return $this->transition(
            $correctionId, [CorrectionCaseModel::STATUS_APPROVED], CorrectionCaseModel::STATUS_EXECUTING,
            self::EVENT_EXECUTING, $actorId, $actorRole
        );
    }

    /** executing → failed */
    public function fail(string $correctionId, string $actorId, string $actorRole): CorrectionCaseModel
    {
        return $this->transition(
            $correctionId, [CorrectionCaseModel::STATUS_EXECUTING], CorrectionCaseModel::STATUS_FAILED,
            self::EVENT_FAILED, $actorId, $actorRole
        );
    }

    /** failed → executing */
    public function retry(string $correctionId, string $actorId, string $actorRole): CorrectionCaseModel
    {
        return $this->transition(
            $correctionId, [CorrectionCaseModel::STATUS_FAILED], CorrectionCaseModel::STATUS_EXECUTING,
            self::EVENT_RETRIED, $actorId, $actorRole
        );
    }

    private function transition(
        string $correctionId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): CorrectionCaseModel {
        return (new TransactionBoundary())->run(function () use (
            $correctionId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $correction = $this->get($correctionId);
            if (empty($correction)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'correction case not found');
            }
            $current = (string) $correction->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid correction case state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $correctionId);

            $affected = Db::connection('mysql')
                ->table('correction_cases')
                ->where('correction_id', $correctionId)
                ->where('status', $current)
                ->where('object_version', (int) $correction->object_version)
                ->update([
                    'status'         => $toStatus,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $correction->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'correction case state transition CAS conflict');
            }

            return $this->get($correctionId);
        });
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
            'target_object_type'   => 'correction_cases',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => '',
            'approval_id'          => '0',
            'case_id'              => '0',
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
