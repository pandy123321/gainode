<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\ResultDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\prediction\ResultModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 预测结果 Service — results 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer results
 *
 * 状态机（05 §4 V2.3 canonical；转移 RS1–RS5，CANDIDATE 未 FROZEN）：
 *   provisional → official → disputed → corrected
 *   - provisional → official（RS1 confirm，依赖赛果源）→ FAIL_CLOSED
 *   - official → disputed（RS2 dispute，依赖 RiskCase）→ FAIL_CLOSED
 *   - disputed → official（RS3 uphold，RISK_APPROVER 裁决恢复）
 *   - disputed → corrected（RS4）/ official → corrected（RS5，仅一次，MC2 #11）
 *   - corrected 为终态；Result official ≠ Settlement paid；confirmer ≠ approver（SoD）
 *
 * @method ResultModel create($data)
 * @method ResultModel get($id, string $field = null)
 * @method ResultModel find($id)
 * @method ResultModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ResultService extends Service
{
    public const EVENT_UPHELD    = 'RESULT_UPHELD';
    public const EVENT_CORRECTED = 'RESULT_CORRECTED';

    public function __construct()
    {
        $this->dao = ResultDao::class;
        parent::__construct();
    }

    public function getByMarket(string $marketId)
    {
        return $this->getNewDao()->getByMarket($marketId);
    }

    public function confirm(string $resultId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Result confirm depends on score source primary/backup consistency (TBC) — not frozen'
        );
    }

    public function dispute(string $resultId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Result dispute depends on RiskCase machine contract (2B-2 not frozen)'
        );
    }

    public function detail(string $resultId): array
    {
        $r = $this->get($resultId);
        if (empty($r)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'result not found');
        }
        return [
            'result_id'          => (string) $r->result_id,
            'market_id'          => (string) $r->market_id,
            'event_id'           => (string) $r->event_id,
            'scores'             => $r->scores,
            'outcome'            => (string) $r->outcome,
            'status'             => (string) $r->status,
            'confirmed_by'       => (string) $r->confirmed_by,
            'correction_version' => (int) $r->correction_version,
            'rule_version'       => (string) $r->rule_version,
        ];
    }

    /** RS3：disputed → official（RISK_APPROVER 裁决恢复） */
    public function uphold(string $resultId, string $actorId, string $actorRole): ResultModel
    {
        return $this->transition(
            $resultId, [ResultModel::STATUS_DISPUTED], ResultModel::STATUS_OFFICIAL,
            self::EVENT_UPHELD, $actorId, $actorRole
        );
    }

    /** RS4：disputed → corrected（RISK_APPROVER 裁决纠错，仅一次） */
    public function correctFromDisputed(string $resultId, string $actorId, string $actorRole): ResultModel
    {
        $this->assertNotCorrected($resultId);
        return $this->transition(
            $resultId, [ResultModel::STATUS_DISPUTED], ResultModel::STATUS_CORRECTED,
            self::EVENT_CORRECTED, $actorId, $actorRole, ['correction_version' => 1]
        );
    }

    /** RS5：official → corrected（纠错，仅一次，MC2 #11） */
    public function correctFromOfficial(string $resultId, string $actorId, string $actorRole): ResultModel
    {
        $this->assertNotCorrected($resultId);
        return $this->transition(
            $resultId, [ResultModel::STATUS_OFFICIAL], ResultModel::STATUS_CORRECTED,
            self::EVENT_CORRECTED, $actorId, $actorRole, ['correction_version' => 1]
        );
    }

    private function assertNotCorrected(string $resultId): void
    {
        $result = $this->get($resultId);
        if (!empty($result) && (int) $result->correction_version >= 1) {
            throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'result already corrected once (MC2 #11)');
        }
    }

    private function transition(
        string $resultId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole,
        array $extraUpdate = []
    ): ResultModel {
        return (new TransactionBoundary())->run(function () use (
            $resultId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole, $extraUpdate
        ) {
            $result = $this->get($resultId);
            if (empty($result)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'result not found');
            }
            $current = (string) $result->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid result state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $resultId);

            $update = array_merge([
                'status'         => $toStatus,
                'audit_event_id' => $auditId,
                'object_version' => (int) $result->object_version + 1,
                'updated_time'   => time(),
            ], $extraUpdate);

            $affected = Db::connection('mysql')
                ->table('results')
                ->where('result_id', $resultId)
                ->where('status', $current)
                ->where('object_version', (int) $result->object_version)
                ->update($update);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'result state transition CAS conflict');
            }

            return $this->get($resultId);
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
            'target_object_type'   => 'results',
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
