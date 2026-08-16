<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\PredictionOrderDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\prediction\PredictionOrderModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * 预测订单 Service — prediction_orders 表唯一 Authoritative Writer（S02-P05）
 *
 * @authoritative_writer prediction_orders
 *
 * 状态机（05 §4 canonical，MC1 冻结；转移矩阵 MC2 §3.5 P1–P12，CANDIDATE 未 FROZEN）：
 *   submitted → locked → awaiting_result → settling → settled
 *   旁路：refunding → refunded（退款）/ correcting → corrected（settlement error 触发）
 *
 * 实现策略（fail-closed）：
 *   - 纯状态转移（P1–P4）完整实现（审计 + object_version CAS + audit_event_id 回写）。
 *   - submit 依赖锁盘参数/资格/stake（06 TBC）→ FAIL_CLOSED。
 *   - 退款/纠错依赖 RefundCase/CorrectionCase 契约与账本写（未冻结）→ FAIL_CLOSED。
 *
 * @method PredictionOrderModel create($data)
 * @method PredictionOrderModel get($id, string $field = null)
 * @method PredictionOrderModel find($id)
 * @method PredictionOrderModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class PredictionOrderService extends Service
{
    public const EVENT_LOCKED          = 'ORDER_LOCKED';
    public const EVENT_AWAITING_RESULT = 'ORDER_AWAITING_RESULT';
    public const EVENT_SETTLING        = 'ORDER_SETTLING';
    public const EVENT_SETTLED         = 'ORDER_SETTLED';

    public function __construct()
    {
        $this->dao = PredictionOrderDao::class;
        parent::__construct();
    }

    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    public function getByMarket(string $marketId)
    {
        return $this->getNewDao()->getByMarket($marketId);
    }

    public function submit(array $data, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Order submit depends on lock_at parameter/eligibility/stake (06 TBC) — not frozen'
        );
    }

    public function startRefund(string $orderId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Order startRefund depends on RefundCase contract (2B-1 not frozen)'
        );
    }

    public function completeRefund(string $orderId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Order completeRefund depends on refund ledger write (not frozen)'
        );
    }

    public function startCorrect(string $orderId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Order startCorrect depends on CorrectionCase contract/approval (not frozen)'
        );
    }

    public function completeCorrect(string $orderId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Order completeCorrect depends on correction ledger write (not frozen)'
        );
    }

    public function listByUser(string $userId): array
    {
        $items = [];
        foreach ($this->getByUser($userId) as $o) {
            $items[] = [
                'order_id'     => (string) $o->order_id,
                'market_id'    => (string) $o->market_id,
                'selection'    => (string) $o->selection,
                'amount_apt'   => (string) $o->amount_apt,
                'order_status' => (string) $o->order_status,
                'created_time' => (int) $o->getRawOriginal('created_time'),
            ];
        }
        return ['orders' => $items];
    }

    public function detail(string $orderId): array
    {
        $o = $this->get($orderId);
        if (empty($o)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'order not found');
        }
        return [
            'order_id'           => (string) $o->order_id,
            'user_id'            => (string) $o->user_id,
            'market_id'          => (string) $o->market_id,
            'selection'          => (string) $o->selection,
            'amount_apt'         => (string) $o->amount_apt,
            'order_status'       => (string) $o->order_status,
            'asset_status'       => $o->asset_status,
            'risk_status'        => $o->risk_status,
            'consent_receipt_id' => (string) $o->consent_receipt_id,
            'policy_version'     => (string) $o->policy_version,
            'object_version'     => (int) $o->object_version,
        ];
    }

    /** P1：submitted → locked */
    public function lock(string $orderId, string $actorId, string $actorRole): PredictionOrderModel
    {
        return $this->transition(
            $orderId, [PredictionOrderModel::ORDER_STATUS_SUBMITTED], PredictionOrderModel::ORDER_STATUS_LOCKED,
            self::EVENT_LOCKED, $actorId, $actorRole
        );
    }

    /** P2：locked → awaiting_result */
    public function awaitResult(string $orderId, string $actorId, string $actorRole): PredictionOrderModel
    {
        return $this->transition(
            $orderId, [PredictionOrderModel::ORDER_STATUS_LOCKED], PredictionOrderModel::ORDER_STATUS_AWAITING_RESULT,
            self::EVENT_AWAITING_RESULT, $actorId, $actorRole
        );
    }

    /** P3：awaiting_result → settling */
    public function startSettling(string $orderId, string $actorId, string $actorRole): PredictionOrderModel
    {
        return $this->transition(
            $orderId, [PredictionOrderModel::ORDER_STATUS_AWAITING_RESULT], PredictionOrderModel::ORDER_STATUS_SETTLING,
            self::EVENT_SETTLING, $actorId, $actorRole
        );
    }

    /** P4：settling → settled */
    public function settle(string $orderId, string $actorId, string $actorRole): PredictionOrderModel
    {
        return $this->transition(
            $orderId, [PredictionOrderModel::ORDER_STATUS_SETTLING], PredictionOrderModel::ORDER_STATUS_SETTLED,
            self::EVENT_SETTLED, $actorId, $actorRole
        );
    }

    private function transition(
        string $orderId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): PredictionOrderModel {
        return (new TransactionBoundary())->run(function () use (
            $orderId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $order = $this->get($orderId);
            if (empty($order)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'order not found');
            }
            $current = (string) $order->order_status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid order state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $orderId);

            $affected = Db::connection('mysql')
                ->table('prediction_orders')
                ->where('order_id', $orderId)
                ->where('order_status', $current)
                ->where('object_version', (int) $order->object_version)
                ->update([
                    'order_status'   => $toStatus,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $order->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'order state transition CAS conflict');
            }

            return $this->get($orderId);
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
            'target_object_type'   => 'prediction_orders',
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
