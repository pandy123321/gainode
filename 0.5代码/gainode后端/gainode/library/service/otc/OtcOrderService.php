<?php

declare(strict_types=1);

namespace library\service\otc;

use library\dao\otc\OtcOrderDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\otc\OtcOrderModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * OTC 订单 Service — otc_orders 表唯一 Authoritative Writer（S02-P06）
 *
 * @authoritative_writer otc_orders
 *
 * 状态机（05 §4 canonical，MC1 冻结；转移矩阵 MC2 §3.6 O1–O12，CANDIDATE 未 FROZEN）：
 *   draft → review → matching → partial → completed
 *   旁路：cancelled（主动取消）/ expired（自然到期）/ rejected（审核驳回）/ disputed（争议冻结）
 *   - partial + cancelled/expired 仅释放 remaining
 *   - disputed 保持冻结直到 RISK_APPROVER 裁决（cancelled 或 completed 二选一）
 *
 * 状态分类（MC2 §3.6 IR 629 P2-1）：
 *   - TRUE_TERMINAL：cancelled / expired / rejected
 *   - STABLE_WITH_EXCEPTION_TRANSITIONS：completed（可经 O11 争议）
 *   - disputed = 中间态
 *
 * 实现策略（fail-closed，与 S02-P05 一致）：
 *   - 纯状态转移（O1–O12）完整实现（审计 + object_version CAS + audit_event_id 回写）。
 *   - quote / createOrder 依赖 06 OTC 参数（min/max/fee/inventory，全部 TBC）→ FAIL_CLOSED。
 *   - 成交记录（recordTrade）由 OtcTradeService 承载（append-only + Ledger + Power，TBC）→ FAIL_CLOSED。
 *   - 金额/Power 字段（filled/remaining/fee/power_*）的更新由成交/释放动作在参数冻结后附加，
 *     纯状态转移不触碰经济字段（与 S02-P05 Settlement 状态转移不计算金额一致）。
 *
 * @method OtcOrderModel create($data)
 * @method OtcOrderModel get($id, string $field = null)
 * @method OtcOrderModel find($id)
 * @method OtcOrderModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class OtcOrderService extends Service
{
    public const EVENT_CREATED             = 'OTC_ORDER_CREATED';
    public const EVENT_SUBMITTED_REVIEW    = 'OTC_ORDER_SUBMITTED_REVIEW';
    public const EVENT_SUBMITTED_MATCHING  = 'OTC_ORDER_SUBMITTED_MATCHING';
    public const EVENT_REVIEW_APPROVED     = 'OTC_ORDER_REVIEW_APPROVED';
    public const EVENT_REJECTED            = 'OTC_ORDER_REJECTED';
    public const EVENT_PARTIAL_FILLED      = 'OTC_ORDER_PARTIAL_FILLED';
    public const EVENT_COMPLETED           = 'OTC_ORDER_COMPLETED';
    public const EVENT_CANCELLED           = 'OTC_ORDER_CANCELLED';
    public const EVENT_EXPIRED             = 'OTC_ORDER_EXPIRED';
    public const EVENT_DISPUTED            = 'OTC_ORDER_DISPUTED';
    public const EVENT_DISPUTE_RESOLVED    = 'OTC_ORDER_DISPUTE_RESOLVED';

    public function __construct()
    {
        $this->dao = OtcOrderDao::class;
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

    /**
     * 报价（不产生账本）。价格/fee/Power 依赖 06 OTC 参数（全部 TBC）→ FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function quote(array $data, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'OTC quote depends on Active Parameter (fee/limit/inventory, 06 TBC) — not frozen'
        );
    }

    /**
     * 创建订单（挂单）。min/max/fee/库存参数 TBC + Power freeze 规则未冻结 → FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function createOrder(array $data, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'OTC createOrder depends on order_min/max_amount + fee_rate + inventory_limit + Power freeze (06 TBC) — not frozen'
        );
    }

    public function listByUser(string $userId): array
    {
        $items = [];
        foreach ($this->getByUser($userId) as $o) {
            $items[] = [
                'otc_order_id'          => (string) $o->otc_order_id,
                'side'                  => (string) $o->side,
                'price'                 => (string) $o->price,
                'quantity_apt'          => (string) $o->quantity_apt,
                'filled_quantity_apt'   => (string) $o->filled_quantity_apt,
                'remaining_quantity_apt'=> (string) $o->remaining_quantity_apt,
                'status'                => (string) $o->status,
                'created_time'          => (int) $o->getRawOriginal('created_time'),
            ];
        }
        return ['orders' => $items];
    }

    public function detail(string $otcOrderId): array
    {
        $o = $this->get($otcOrderId);
        if (empty($o)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'otc order not found');
        }
        return [
            'otc_order_id'           => (string) $o->otc_order_id,
            'user_id'                => (string) $o->user_id,
            'side'                   => (string) $o->side,
            'price'                  => (string) $o->price,
            'quantity_apt'           => (string) $o->quantity_apt,
            'filled_quantity_apt'    => (string) $o->filled_quantity_apt,
            'remaining_quantity_apt' => (string) $o->remaining_quantity_apt,
            'fee_apt'                => (string) $o->fee_apt,
            'power_required'         => (string) $o->power_required,
            'power_consumed'         => (string) $o->power_consumed,
            'power_frozen'           => (string) $o->power_frozen,
            'status'                 => (string) $o->status,
            'review_required'        => (int) $o->review_required,
            'quote_id'               => (string) $o->quote_id,
            'snapshot_id'            => (string) $o->snapshot_id,
            'rule_version'           => (string) $o->rule_version,
            'policy_version'         => (string) $o->policy_version,
            'object_version'         => (int) $o->object_version,
        ];
    }

    /** O1：draft → review（提交审核，review_required=1） */
    public function submitReview(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_DRAFT], OtcOrderModel::STATUS_REVIEW,
            self::EVENT_SUBMITTED_REVIEW, $actorId, $actorRole
        );
    }

    /** O2：draft → matching（提交撮合，review_required=0，资格通过） */
    public function submitMatching(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_DRAFT], OtcOrderModel::STATUS_MATCHING,
            self::EVENT_SUBMITTED_MATCHING, $actorId, $actorRole
        );
    }

    /** O3：review → matching（审核通过） */
    public function approveReview(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_REVIEW], OtcOrderModel::STATUS_MATCHING,
            self::EVENT_REVIEW_APPROVED, $actorId, $actorRole
        );
    }

    /** O4：review → rejected（审核驳回） */
    public function reject(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_REVIEW], OtcOrderModel::STATUS_REJECTED,
            self::EVENT_REJECTED, $actorId, $actorRole
        );
    }

    /** O5：matching → partial（部分成交） */
    public function partialFill(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_MATCHING], OtcOrderModel::STATUS_PARTIAL,
            self::EVENT_PARTIAL_FILLED, $actorId, $actorRole
        );
    }

    /** O6：matching → completed（全部成交） */
    public function completeFromMatching(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_MATCHING], OtcOrderModel::STATUS_COMPLETED,
            self::EVENT_COMPLETED, $actorId, $actorRole
        );
    }

    /** O7：matching → cancelled（用户取消，释放 remaining） */
    public function cancel(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_MATCHING], OtcOrderModel::STATUS_CANCELLED,
            self::EVENT_CANCELLED, $actorId, $actorRole
        );
    }

    /** O8：matching → expired（有效期到期，释放 remaining） */
    public function expire(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_MATCHING], OtcOrderModel::STATUS_EXPIRED,
            self::EVENT_EXPIRED, $actorId, $actorRole
        );
    }

    /** O9：partial → completed（剩余全部成交） */
    public function completeFromPartial(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_PARTIAL], OtcOrderModel::STATUS_COMPLETED,
            self::EVENT_COMPLETED, $actorId, $actorRole
        );
    }

    /** O10（cancelled 分支）：partial → cancelled（取消剩余，仅释放 remaining） */
    public function cancelRemaining(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_PARTIAL], OtcOrderModel::STATUS_CANCELLED,
            self::EVENT_CANCELLED, $actorId, $actorRole
        );
    }

    /** O10（expired 分支）：partial → expired（到期，仅释放 remaining） */
    public function expireRemaining(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_PARTIAL], OtcOrderModel::STATUS_EXPIRED,
            self::EVENT_EXPIRED, $actorId, $actorRole
        );
    }

    /** O11：completed → disputed（成交后争议，冻结） */
    public function dispute(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_COMPLETED], OtcOrderModel::STATUS_DISPUTED,
            self::EVENT_DISPUTED, $actorId, $actorRole
        );
    }

    /** O12（cancelled 分支）：disputed → cancelled（裁决取消退钱） */
    public function resolveDisputeCancel(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_DISPUTED], OtcOrderModel::STATUS_CANCELLED,
            self::EVENT_DISPUTE_RESOLVED, $actorId, $actorRole
        );
    }

    /** O12（completed 分支）：disputed → completed（裁决维持成交） */
    public function resolveDisputeComplete(string $otcOrderId, string $actorId, string $actorRole): OtcOrderModel
    {
        return $this->transition(
            $otcOrderId, [OtcOrderModel::STATUS_DISPUTED], OtcOrderModel::STATUS_COMPLETED,
            self::EVENT_DISPUTE_RESOLVED, $actorId, $actorRole
        );
    }

    private function transition(
        string $otcOrderId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): OtcOrderModel {
        return (new TransactionBoundary())->run(function () use (
            $otcOrderId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $order = $this->get($otcOrderId);
            if (empty($order)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'otc order not found');
            }
            $current = (string) $order->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid otc order state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $otcOrderId);

            $affected = Db::connection('mysql')
                ->table('otc_orders')
                ->where('otc_order_id', $otcOrderId)
                ->where('status', $current)
                ->where('object_version', (int) $order->object_version)
                ->update([
                    'status'          => $toStatus,
                    'audit_event_id'  => $auditId,
                    'object_version'  => (int) $order->object_version + 1,
                    'updated_time'    => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'otc order state transition CAS conflict');
            }

            return $this->get($otcOrderId);
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
            'target_object_type'   => 'otc_orders',
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
