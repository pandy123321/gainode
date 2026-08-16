<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\PredictionMarketDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\prediction\PredictionMarketModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 预测市场 Service — prediction_markets 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer prediction_markets
 *
 * 状态机（05 §4 canonical，MC1 冻结；转移矩阵 MC2 §3.4 M1–M12，CANDIDATE 未 FROZEN）：
 *   draft → open → closing → locked → awaiting_result → settlement → settled
 *   旁路：void（作废）/ exception（异常，可重试回 settlement）
 *
 * 实现策略（fail-closed）：
 *   - 纯状态转移（M1–M12）完整实现（审计 + object_version CAS）。
 *   - create 依赖赛事源 Fixture（TBC）→ FAIL_CLOSED。
 *   - prediction_markets 表无 audit_event_id 列（MC1 DDL），审计经 audit_events.target_object_type 单向关联。
 *
 * @method PredictionMarketModel create($data)
 * @method PredictionMarketModel get($id, string $field = null)
 * @method PredictionMarketModel find($id)
 * @method PredictionMarketModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class PredictionMarketService extends Service
{
    public const EVENT_PUBLISHED           = 'MARKET_PUBLISHED';
    public const EVENT_CLOSING             = 'MARKET_CLOSING';
    public const EVENT_LOCKED              = 'MARKET_LOCKED';
    public const EVENT_AWAITING_RESULT     = 'MARKET_AWAITING_RESULT';
    public const EVENT_SETTLEMENT_STARTED  = 'MARKET_SETTLEMENT_STARTED';
    public const EVENT_SETTLED             = 'MARKET_SETTLED';
    public const EVENT_VOIDED              = 'MARKET_VOIDED';
    public const EVENT_SETTLEMENT_FAILED   = 'MARKET_SETTLEMENT_FAILED';
    public const EVENT_SETTLEMENT_RETRIED  = 'MARKET_SETTLEMENT_RETRIED';
    public const EVENT_SETTLED_MANUAL      = 'MARKET_SETTLED_MANUAL';
    public const EVENT_SETTLEMENT_REOPENED = 'MARKET_SETTLEMENT_REOPENED';

    public function __construct()
    {
        $this->dao = PredictionMarketDao::class;
        parent::__construct();
    }

    public function getByEvent(string $eventId)
    {
        return $this->getNewDao()->getByEvent($eventId);
    }

    public function createMarket(array $data, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Market create depends on Fixture source (event_id/template/selections, TBC) — not frozen'
        );
    }

    public function listByEvent(string $eventId): array
    {
        $items = [];
        foreach ($this->getByEvent($eventId) as $m) {
            $items[] = [
                'market_id'     => (string) $m->market_id,
                'event_id'      => (string) $m->event_id,
                'template_id'   => (string) $m->template_id,
                'market_status' => (string) $m->market_status,
                'lock_at'       => (int) $m->lock_at,
                'rule_version'  => (string) $m->rule_version,
            ];
        }
        return ['markets' => $items];
    }

    public function detail(string $marketId): array
    {
        $m = $this->get($marketId);
        if (empty($m)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'market not found');
        }
        return [
            'market_id'      => (string) $m->market_id,
            'event_id'       => (string) $m->event_id,
            'template_id'    => (string) $m->template_id,
            'market_status'  => (string) $m->market_status,
            'lock_at'        => (int) $m->lock_at,
            'selections'     => $m->selections,
            'result_status'  => $m->result_status,
            'rule_version'   => (string) $m->rule_version,
            'object_version' => (int) $m->object_version,
        ];
    }

    public function allowedActions(string $marketId): array
    {
        $m = $this->get($marketId);
        if (empty($m)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'market not found');
        }
        $candidates = [];
        if ((string) $m->market_status === PredictionMarketModel::MARKET_STATUS_OPEN) {
            $candidates = ['place_bet'];
        }
        return [
            'market_id'       => $marketId,
            'market_status'   => (string) $m->market_status,
            'allowed_actions' => [],
            'blocked_actions' => $candidates,
        ];
    }

    /** M1：draft → open */
    public function publish(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_DRAFT], PredictionMarketModel::MARKET_STATUS_OPEN,
            self::EVENT_PUBLISHED, $actorId, $actorRole
        );
    }

    /** M2：open → closing */
    public function startClosing(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_OPEN], PredictionMarketModel::MARKET_STATUS_CLOSING,
            self::EVENT_CLOSING, $actorId, $actorRole
        );
    }

    /** M3/M4：closing/open → locked */
    public function lock(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId,
            [PredictionMarketModel::MARKET_STATUS_CLOSING, PredictionMarketModel::MARKET_STATUS_OPEN],
            PredictionMarketModel::MARKET_STATUS_LOCKED,
            self::EVENT_LOCKED, $actorId, $actorRole
        );
    }

    /** M5：locked → awaiting_result */
    public function awaitResult(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_LOCKED], PredictionMarketModel::MARKET_STATUS_AWAITING_RESULT,
            self::EVENT_AWAITING_RESULT, $actorId, $actorRole
        );
    }

    /** M6：awaiting_result → settlement */
    public function startSettlement(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_AWAITING_RESULT], PredictionMarketModel::MARKET_STATUS_SETTLEMENT,
            self::EVENT_SETTLEMENT_STARTED, $actorId, $actorRole
        );
    }

    /** M7：settlement → settled */
    public function completeSettlement(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_SETTLEMENT], PredictionMarketModel::MARKET_STATUS_SETTLED,
            self::EVENT_SETTLED, $actorId, $actorRole
        );
    }

    /** M8：draft/open/closing/locked/awaiting_result → void */
    public function voidMarket(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId,
            [
                PredictionMarketModel::MARKET_STATUS_DRAFT,
                PredictionMarketModel::MARKET_STATUS_OPEN,
                PredictionMarketModel::MARKET_STATUS_CLOSING,
                PredictionMarketModel::MARKET_STATUS_LOCKED,
                PredictionMarketModel::MARKET_STATUS_AWAITING_RESULT,
            ],
            PredictionMarketModel::MARKET_STATUS_VOID,
            self::EVENT_VOIDED, $actorId, $actorRole
        );
    }

    /** M9：settlement → exception */
    public function failSettlement(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_SETTLEMENT], PredictionMarketModel::MARKET_STATUS_EXCEPTION,
            self::EVENT_SETTLEMENT_FAILED, $actorId, $actorRole
        );
    }

    /** M10：exception → settlement */
    public function retrySettlement(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_EXCEPTION], PredictionMarketModel::MARKET_STATUS_SETTLEMENT,
            self::EVENT_SETTLEMENT_RETRIED, $actorId, $actorRole
        );
    }

    /** M11：exception → settled（人工完成，OPS + RISK） */
    public function completeSettlementManual(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_EXCEPTION], PredictionMarketModel::MARKET_STATUS_SETTLED,
            self::EVENT_SETTLED_MANUAL, $actorId, $actorRole
        );
    }

    /** M12：settled → settlement（重开结算） */
    public function reopenSettlement(string $marketId, string $actorId, string $actorRole): PredictionMarketModel
    {
        return $this->transition(
            $marketId, [PredictionMarketModel::MARKET_STATUS_SETTLED], PredictionMarketModel::MARKET_STATUS_SETTLEMENT,
            self::EVENT_SETTLEMENT_REOPENED, $actorId, $actorRole
        );
    }

    private function transition(
        string $marketId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): PredictionMarketModel {
        return (new TransactionBoundary())->run(function () use (
            $marketId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $market = $this->get($marketId);
            if (empty($market)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'market not found');
            }
            $current = (string) $market->market_status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid market state transition');
            }

            $this->appendAudit($eventCode, $actorId, $actorRole, $marketId);

            $affected = Db::connection('mysql')
                ->table('prediction_markets')
                ->where('market_id', $marketId)
                ->where('market_status', $current)
                ->where('object_version', (int) $market->object_version)
                ->update([
                    'market_status'  => $toStatus,
                    'object_version' => (int) $market->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'market state transition CAS conflict');
            }

            return $this->get($marketId);
        });
    }

    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId
    ): void {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'       => $auditId,
            'event_code'           => $eventCode,
            'actor_id'             => $actorId,
            'actor_role'           => $actorRole,
            'target_object_type'   => 'prediction_markets',
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
    }
}
