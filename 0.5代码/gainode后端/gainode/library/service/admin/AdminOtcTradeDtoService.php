<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\otc\OtcTradeDao;
use support\extend\Service;

/**
 * Admin V2 OTC 成交记录 DTO 服务（A-OTC-001 补充）。
 *
 * 只读全量分页：otc_trades（append-only 成交事实）。
 * 字段口径：仅返回已确认列；金额/数量为 string decimal；时间为 UTC。
 * 供 Admin 2.0 OTC 订单详情页经 /api/v1/admin/otc/trades 对接。
 */
class AdminOtcTradeDtoService extends Service
{
    public function __construct()
    {
        $this->dao = OtcTradeDao::class;
        parent::__construct();
    }

    /**
     * 分页 OTC 成交记录 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $otcOrderId 订单筛选（可选）
     * @return array{trades:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $otcOrderId = ''): array
    {
        $params = [];
        if ($otcOrderId !== '') {
            $params['otc_order_id'] = $otcOrderId;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new OtcTradeDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['trade_id', 'otc_order_id', 'buyer_user_id', 'seller_user_id', 'quantity_apt', 'price_apt', 'fee_apt', 'power_consumed', 'status', 'ledger_batch_id', 'audit_event_id', 'created_time']
        );

        $trades = [];
        foreach ($paginator->items() as $t) {
            $trades[] = [
                'trade_id'       => (string) $t->trade_id,
                'otc_order_id'   => (string) $t->otc_order_id,
                'buyer_user_id'  => (string) $t->buyer_user_id,
                'seller_user_id' => (string) $t->seller_user_id,
                'quantity_apt'   => (string) $t->quantity_apt,
                'price_apt'      => (string) $t->price_apt,
                'fee_apt'        => (string) $t->fee_apt,
                'power_consumed' => (string) $t->power_consumed,
                'status'         => (string) $t->status,
                'ledger_batch_id'=> (string) $t->ledger_batch_id,
                'audit_event_id' => (string) $t->audit_event_id,
                'created_time'   => (int) $t->getRawOriginal('created_time'),
            ];
        }

        return [
            'trades' => $trades,
            'total'  => (int) $paginator->total(),
            'page'   => $page,
            'size'   => $size,
        ];
    }
}
