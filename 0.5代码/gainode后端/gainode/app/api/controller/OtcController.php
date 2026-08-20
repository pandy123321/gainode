<?php

declare(strict_types=1);

namespace app\api\controller;

use library\service\otc\OtcEligibilityProjectionService;
use library\service\otc\OtcOrderService;
use library\service\otc\OtcTradeService;
use support\controller\ApiV2;
use support\Response;

/**
 * OTC / Power 只读 C 端控制器（05 §6；S02-P06 骨架）。
 *
 * 只读：挂单列表（order-book）、用户订单、订单详情、成交记录、资格投影。
 * 写路径（quote/order create/cancel/review/matching）依赖 06 OTC 参数与 Power freeze
 * 规则（全部 TBC）→ 不暴露写方法（fail-closed）。
 */
class OtcController extends ApiV2
{
    /** GET /api/v1/otc/order-book */
    public function orderBook(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new OtcOrderService())->listByUser($userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/otc/orders/{id} */
    public function orderDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new OtcOrderService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/otc/users/{id}/orders */
    public function userOrders(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new OtcOrderService())->listByUser($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/otc/trades (me) */
    public function trades(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $service = new OtcTradeService();
            $rows = array_merge(
                $service->getByBuyer($userId)->all(),
                $service->getBySeller($userId)->all()
            );
            $items = [];
            foreach ($rows as $t) {
                $items[] = [
                    'trade_id'       => (string) $t->trade_id,
                    'otc_order_id'   => (string) $t->otc_order_id,
                    'buyer_user_id'  => (string) $t->buyer_user_id,
                    'seller_user_id' => (string) $t->seller_user_id,
                    'quantity_apt'   => (string) $t->quantity_apt,
                    'price_apt'      => (string) $t->price_apt,
                    'status'         => (string) $t->status,
                    'created_time'   => (int) $t->getRawOriginal('created_time'),
                ];
            }
            return $this->envelope(['trades' => $items]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/otc/eligibility (me) */
    public function eligibility(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new OtcEligibilityProjectionService())->getEligibility($userId, $userId);
            return $this->envelope($result->toArray());
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
