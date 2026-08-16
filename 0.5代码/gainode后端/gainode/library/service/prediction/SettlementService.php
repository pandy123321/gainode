<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\SettlementDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\prediction\SettlementModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 结算单 Service — settlements 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer settlements
 *
 * 状态机（05 §4 V2.3 canonical；转移 ST1–ST7，CANDIDATE 未 FROZEN）：
 *   queued → calculating → review → payable → paid
 *   旁路：failed（异常，可重试回 queued）
 *   - paid：唯一「已结算」真值；Result official ≠ Settlement paid；confirmer ≠ approver（SoD）
 *
 * @method SettlementModel create($data)
 * @method SettlementModel get($id, string $field = null)
 * @method SettlementModel find($id)
 * @method SettlementModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class SettlementService extends Service
{
    public const EVENT_CALCULATING     = 'SETTLEMENT_CALCULATING';
    public const EVENT_REVIEW_REQUIRED = 'SETTLEMENT_REVIEW_REQUIRED';
    public const EVENT_REVIEW_APPROVED = 'SETTLEMENT_REVIEW_APPROVED';
    public const EVENT_FAILED          = 'SETTLEMENT_FAILED';
    public const EVENT_RETRIED         = 'SETTLEMENT_RETRIED';

    public function __construct()
    {
        $this->dao = SettlementDao::class;
        parent::__construct();
    }

    public function getByMarket(string $marketId)
    {
        return $this->getNewDao()->getByMarket($marketId);
    }

    public function getByBatch(string $batchId)
    {
        return $this->getNewDao()->getByBatch($batchId);
    }

    public function calculate(string $settlementId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Settlement calculate depends on settlement parameters (odds/coefficient, 06 TBC) — not frozen'
        );
    }

    public function pay(string $settlementId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Settlement pay depends on ledger posting (not frozen)'
        );
    }

    public function detail(string $settlementId): array
    {
        $s = $this->get($settlementId);
        if (empty($s)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'settlement not found');
        }
        return [
            'settlement_id'         => (string) $s->settlement_id,
            'market_id'             => (string) $s->market_id,
            'batch_id'              => (string) $s->batch_id,
            'status'                => (string) $s->status,
            'principal_total_apt'   => (string) $s->principal_total_apt,
            'reward_total_apt'      => (string) $s->reward_total_apt,
            'service_fee_total_apt' => (string) $s->service_fee_total_apt,
            'ledger_batch_id'       => (string) $s->ledger_batch_id,
            'approved_by'           => (string) $s->approved_by,
            'rule_version'          => (string) $s->rule_version,
        ];
    }

    /** ST1：queued → calculating */
    public function start(string $settlementId, string $actorId, string $actorRole): SettlementModel
    {
        return $this->transition(
            $settlementId, [SettlementModel::STATUS_QUEUED], SettlementModel::STATUS_CALCULATING,
            self::EVENT_CALCULATING, $actorId, $actorRole
        );
    }

    /** ST3：calculating → review */
    public function reviewRequired(string $settlementId, string $actorId, string $actorRole): SettlementModel
    {
        return $this->transition(
            $settlementId, [SettlementModel::STATUS_CALCULATING], SettlementModel::STATUS_REVIEW,
            self::EVENT_REVIEW_REQUIRED, $actorId, $actorRole
        );
    }

    /** ST4：review → payable（RISK_APPROVER） */
    public function approveReview(string $settlementId, string $actorId, string $actorRole): SettlementModel
    {
        return $this->transition(
            $settlementId, [SettlementModel::STATUS_REVIEW], SettlementModel::STATUS_PAYABLE,
            self::EVENT_REVIEW_APPROVED, $actorId, $actorRole
        );
    }

    /** ST6：queued/calculating/review/payable → failed */
    public function fail(string $settlementId, string $actorId, string $actorRole): SettlementModel
    {
        return $this->transition(
            $settlementId,
            [
                SettlementModel::STATUS_QUEUED,
                SettlementModel::STATUS_CALCULATING,
                SettlementModel::STATUS_REVIEW,
                SettlementModel::STATUS_PAYABLE,
            ],
            SettlementModel::STATUS_FAILED,
            self::EVENT_FAILED, $actorId, $actorRole
        );
    }

    /** ST7：failed → queued */
    public function retry(string $settlementId, string $actorId, string $actorRole): SettlementModel
    {
        return $this->transition(
            $settlementId, [SettlementModel::STATUS_FAILED], SettlementModel::STATUS_QUEUED,
            self::EVENT_RETRIED, $actorId, $actorRole
        );
    }

    private function transition(
        string $settlementId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): SettlementModel {
        return (new TransactionBoundary())->run(function () use (
            $settlementId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $settlement = $this->get($settlementId);
            if (empty($settlement)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'settlement not found');
            }
            $current = (string) $settlement->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid settlement state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $settlementId);

            $affected = Db::connection('mysql')
                ->table('settlements')
                ->where('settlement_id', $settlementId)
                ->where('status', $current)
                ->where('object_version', (int) $settlement->object_version)
                ->update([
                    'status'         => $toStatus,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $settlement->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'settlement state transition CAS conflict');
            }

            return $this->get($settlementId);
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
            'target_object_type'   => 'settlements',
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
