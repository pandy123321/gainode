<?php

declare(strict_types=1);

namespace library\service\otc;

use library\dao\otc\OtcTradeDao;
use library\dict\ErrorDict;
use library\model\otc\OtcTradeModel;
use support\extend\Service;
use support\exception\DomainException;

/**
 * OTC 成交事实 Service — otc_trades 表唯一 Authoritative Writer（S02-P06）
 *
 * @authoritative_writer otc_trades
 *
 * 状态机（05 §4 V2.3 canonical，Owner 2B1-ENUM-04）：
 *   completed（单态，append-only 成交事实）
 *   - 争议/冲正不覆盖 Trade，走 RiskCase + ledger reversal（作用于 otc_orders / apt_ledger_entries）
 *
 * append-only 约束（MC2 Freeze §3.6 + OtcTradeModel/OtcTradeAppendOnlyBuilder/OtcTradeDao）：
 *   - 成交事实一经写入永不覆盖，物理删除禁止。
 *
 * 实现策略（fail-closed）：
 *   - recordTrade 依赖撮合结果 + Ledger 过账 + Power 消耗（06 TBC）→ FAIL_CLOSED。
 *   - 只读查询（by order/buyer/seller/idempotency）透传 DAO。
 *
 * @method OtcTradeModel create($data)
 * @method OtcTradeModel get($id, string $field = null)
 * @method OtcTradeModel find($id)
 * @method OtcTradeModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class OtcTradeService extends Service
{
    public const EVENT_TRADE_RECORDED = 'OTC_TRADE_RECORDED';

    public function __construct()
    {
        $this->dao = OtcTradeDao::class;
        parent::__construct();
    }

    public function getByOrder(string $otcOrderId)
    {
        return $this->getNewDao()->getByOrder($otcOrderId);
    }

    public function getByBuyer(string $buyerUserId)
    {
        return $this->getNewDao()->getByBuyer($buyerUserId);
    }

    public function getBySeller(string $sellerUserId)
    {
        return $this->getNewDao()->getBySeller($sellerUserId);
    }

    /**
     * 记录成交（append-only）。依赖撮合结果 + Ledger 过账 + Power 消耗（06 TBC）→ FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function recordTrade(array $data, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'OTC trade record depends on matching rules + Ledger posting + Power consume (06 TBC) — not frozen'
        );
    }

    public function listByOrder(string $otcOrderId): array
    {
        $items = [];
        foreach ($this->getByOrder($otcOrderId) as $t) {
            $items[] = [
                'trade_id'        => (string) $t->trade_id,
                'otc_order_id'    => (string) $t->otc_order_id,
                'buyer_user_id'   => (string) $t->buyer_user_id,
                'seller_user_id'  => (string) $t->seller_user_id,
                'quantity_apt'    => (string) $t->quantity_apt,
                'price_apt'       => (string) $t->price_apt,
                'status'          => (string) $t->status,
                'created_time'    => (int) $t->getRawOriginal('created_time'),
            ];
        }
        return ['trades' => $items];
    }

    public function detail(string $tradeId): array
    {
        $t = $this->get($tradeId);
        if (empty($t)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'otc trade not found');
        }
        return [
            'trade_id'         => (string) $t->trade_id,
            'otc_order_id'     => (string) $t->otc_order_id,
            'buyer_user_id'    => (string) $t->buyer_user_id,
            'seller_user_id'   => (string) $t->seller_user_id,
            'quantity_apt'     => (string) $t->quantity_apt,
            'price_apt'        => (string) $t->price_apt,
            'fee_apt'          => (string) $t->fee_apt,
            'power_consumed'   => (string) $t->power_consumed,
            'status'           => (string) $t->status,
            'ledger_entry_ids' => $t->ledger_entry_ids,
            'ledger_batch_id'  => (string) $t->ledger_batch_id,
            'created_time'     => (int) $t->getRawOriginal('created_time'),
        ];
    }
}
