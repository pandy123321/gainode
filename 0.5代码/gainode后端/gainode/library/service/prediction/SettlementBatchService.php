<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\SettlementBatchDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\prediction\SettlementBatchModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 结算批 Service — settlement_batches 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer settlement_batches
 *
 * 状态机（05 §4 V2.3 canonical，Owner 2B1-ENUM-01）：
 *   created → processing → completed / processing → partially_failed（可重试回 processing）/ * → failed
 *
 * @method SettlementBatchModel create($data)
 * @method SettlementBatchModel get($id, string $field = null)
 * @method SettlementBatchModel find($id)
 * @method SettlementBatchModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class SettlementBatchService extends Service
{
    public const EVENT_PROCESSING       = 'SETTLEMENT_BATCH_PROCESSING';
    public const EVENT_COMPLETED        = 'SETTLEMENT_BATCH_COMPLETED';
    public const EVENT_PARTIALLY_FAILED = 'SETTLEMENT_BATCH_PARTIALLY_FAILED';
    public const EVENT_RETRIED          = 'SETTLEMENT_BATCH_RETRIED';
    public const EVENT_FAILED           = 'SETTLEMENT_BATCH_FAILED';

    public function __construct()
    {
        $this->dao = SettlementBatchDao::class;
        parent::__construct();
    }

    public function createBatch(array $data, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'SettlementBatch create depends on settlement slicing parameters (06 TBC) — not frozen'
        );
    }

    public function detail(string $batchId): array
    {
        $b = $this->get($batchId);
        if (empty($b)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'settlement batch not found');
        }
        return [
            'batch_id'              => (string) $b->batch_id,
            'status'                => (string) $b->status,
            'market_count'          => (int) $b->market_count,
            'order_count'           => (int) $b->order_count,
            'total_principal_apt'   => (string) $b->total_principal_apt,
            'total_reward_apt'      => (string) $b->total_reward_apt,
            'total_service_fee_apt' => (string) $b->total_service_fee_apt,
            'rule_version'          => (string) $b->rule_version,
        ];
    }

    /** created → processing */
    public function process(string $batchId, string $actorId, string $actorRole): SettlementBatchModel
    {
        return $this->transition(
            $batchId, [SettlementBatchModel::STATUS_CREATED], SettlementBatchModel::STATUS_PROCESSING,
            self::EVENT_PROCESSING, $actorId, $actorRole
        );
    }

    /** processing → completed */
    public function complete(string $batchId, string $actorId, string $actorRole): SettlementBatchModel
    {
        return $this->transition(
            $batchId, [SettlementBatchModel::STATUS_PROCESSING], SettlementBatchModel::STATUS_COMPLETED,
            self::EVENT_COMPLETED, $actorId, $actorRole
        );
    }

    /** processing → partially_failed */
    public function partiallyFail(string $batchId, string $actorId, string $actorRole): SettlementBatchModel
    {
        return $this->transition(
            $batchId, [SettlementBatchModel::STATUS_PROCESSING], SettlementBatchModel::STATUS_PARTIALLY_FAILED,
            self::EVENT_PARTIALLY_FAILED, $actorId, $actorRole
        );
    }

    /** partially_failed → processing */
    public function retry(string $batchId, string $actorId, string $actorRole): SettlementBatchModel
    {
        return $this->transition(
            $batchId, [SettlementBatchModel::STATUS_PARTIALLY_FAILED], SettlementBatchModel::STATUS_PROCESSING,
            self::EVENT_RETRIED, $actorId, $actorRole
        );
    }

    /** processing/partially_failed → failed */
    public function fail(string $batchId, string $actorId, string $actorRole): SettlementBatchModel
    {
        return $this->transition(
            $batchId,
            [SettlementBatchModel::STATUS_PROCESSING, SettlementBatchModel::STATUS_PARTIALLY_FAILED],
            SettlementBatchModel::STATUS_FAILED,
            self::EVENT_FAILED, $actorId, $actorRole
        );
    }

    private function transition(
        string $batchId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): SettlementBatchModel {
        return (new TransactionBoundary())->run(function () use (
            $batchId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $batch = $this->get($batchId);
            if (empty($batch)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'settlement batch not found');
            }
            $current = (string) $batch->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid settlement batch state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $batchId);

            $affected = Db::connection('mysql')
                ->table('settlement_batches')
                ->where('batch_id', $batchId)
                ->where('status', $current)
                ->where('object_version', (int) $batch->object_version)
                ->update([
                    'status'         => $toStatus,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $batch->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'settlement batch state transition CAS conflict');
            }

            return $this->get($batchId);
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
            'target_object_type'   => 'settlement_batches',
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
