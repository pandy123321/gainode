<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\RefundCaseDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\prediction\RefundCaseModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 退款案件 Service — refund_cases 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer refund_cases
 *
 * 状态机（05 §4 V2.3 canonical，Owner 2B1-ENUM-02）：
 *   pending → approved → executing → completed / pending → rejected / executing → failed（可重试回 executing）
 *
 * @method RefundCaseModel create($data)
 * @method RefundCaseModel get($id, string $field = null)
 * @method RefundCaseModel find($id)
 * @method RefundCaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RefundCaseService extends Service
{
    public const EVENT_APPROVED  = 'REFUND_CASE_APPROVED';
    public const EVENT_REJECTED  = 'REFUND_CASE_REJECTED';
    public const EVENT_EXECUTING = 'REFUND_CASE_EXECUTING';
    public const EVENT_FAILED    = 'REFUND_CASE_FAILED';
    public const EVENT_RETRIED   = 'REFUND_CASE_RETRIED';

    public function __construct()
    {
        $this->dao = RefundCaseDao::class;
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
            'RefundCase create depends on refund contract/eligibility (2B-1 not frozen)'
        );
    }

    public function complete(string $refundId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'RefundCase complete depends on refund ledger write (not frozen)'
        );
    }

    public function detail(string $refundId): array
    {
        $r = $this->get($refundId);
        if (empty($r)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'refund case not found');
        }
        return [
            'refund_id'             => (string) $r->refund_id,
            'market_id'             => (string) $r->market_id,
            'batch_size'            => (int) $r->batch_size,
            'principal_total_apt'   => (string) $r->principal_total_apt,
            'service_fee_total_apt' => (string) $r->service_fee_total_apt,
            'status'                => (string) $r->status,
            'approved_by'           => (string) $r->approved_by,
            'reason_code'           => (string) $r->reason_code,
            'rule_version'          => (string) $r->rule_version,
        ];
    }

    /** pending → approved（RISK_APPROVER） */
    public function approve(string $refundId, string $actorId, string $actorRole): RefundCaseModel
    {
        return $this->transition(
            $refundId, [RefundCaseModel::STATUS_PENDING], RefundCaseModel::STATUS_APPROVED,
            self::EVENT_APPROVED, $actorId, $actorRole
        );
    }

    /** pending → rejected（RISK_APPROVER） */
    public function reject(string $refundId, string $actorId, string $actorRole): RefundCaseModel
    {
        return $this->transition(
            $refundId, [RefundCaseModel::STATUS_PENDING], RefundCaseModel::STATUS_REJECTED,
            self::EVENT_REJECTED, $actorId, $actorRole
        );
    }

    /** approved → executing */
    public function execute(string $refundId, string $actorId, string $actorRole): RefundCaseModel
    {
        return $this->transition(
            $refundId, [RefundCaseModel::STATUS_APPROVED], RefundCaseModel::STATUS_EXECUTING,
            self::EVENT_EXECUTING, $actorId, $actorRole
        );
    }

    /** executing → failed */
    public function fail(string $refundId, string $actorId, string $actorRole): RefundCaseModel
    {
        return $this->transition(
            $refundId, [RefundCaseModel::STATUS_EXECUTING], RefundCaseModel::STATUS_FAILED,
            self::EVENT_FAILED, $actorId, $actorRole
        );
    }

    /** failed → executing */
    public function retry(string $refundId, string $actorId, string $actorRole): RefundCaseModel
    {
        return $this->transition(
            $refundId, [RefundCaseModel::STATUS_FAILED], RefundCaseModel::STATUS_EXECUTING,
            self::EVENT_RETRIED, $actorId, $actorRole
        );
    }

    private function transition(
        string $refundId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): RefundCaseModel {
        return (new TransactionBoundary())->run(function () use (
            $refundId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $refund = $this->get($refundId);
            if (empty($refund)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'refund case not found');
            }
            $current = (string) $refund->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid refund case state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $refundId);

            $affected = Db::connection('mysql')
                ->table('refund_cases')
                ->where('refund_id', $refundId)
                ->where('status', $current)
                ->where('object_version', (int) $refund->object_version)
                ->update([
                    'status'         => $toStatus,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $refund->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'refund case state transition CAS conflict');
            }

            return $this->get($refundId);
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
            'target_object_type'   => 'refund_cases',
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
