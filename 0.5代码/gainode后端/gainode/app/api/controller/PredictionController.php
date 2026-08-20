<?php

declare(strict_types=1);

namespace app\api\controller;

use library\service\policy\ConsentReceiptService;
use library\service\prediction\PredictionMarketService;
use library\service\prediction\PredictionOrderService;
use support\controller\ApiV2;
use support\Response;

/**
 * Prediction C 端只读控制器（05 §6；S02-P05 骨架）。
 *
 * 只读：市场列表/详情、订单列表/详情/回执、Consent 收据。
 * 写路径（market create / order submit / consent grant / appeal）由内部/Admin writer
 * 触发或依赖未冻结规则，本控制器不暴露写方法（fail-closed）。
 */
class PredictionController extends ApiV2
{
    /** GET /api/v1/markets?event_id= */
    public function markets(): Response
    {
        try {
            $this->request->getTokenUser();
            $eventId = (string) $this->request->get('event_id', '');
            if ($eventId === '') {
                return $this->envelope(['markets' => []]);
            }
            $result = (new PredictionMarketService())->listByEvent($eventId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/markets/{id} */
    public function marketDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new PredictionMarketService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/orders (me) */
    public function myOrders(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new PredictionOrderService())->listByUser($userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/orders/{id}/receipt */
    public function orderReceipt(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new PredictionOrderService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/consent-receipts (me) */
    public function myConsentReceipts(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new ConsentReceiptService())->getByUser($userId);
            $items = [];
            foreach ($result as $c) {
                $items[] = [
                    'receipt_id'    => (string) $c->receipt_id,
                    'user_id'       => (string) $c->user_id,
                    'content_hash'  => (string) $c->content_hash,
                    'consent_version'=> (string) $c->consent_version,
                    'created_time'  => (int) $c->getRawOriginal('created_time'),
                ];
            }
            return $this->envelope(['receipts' => $items]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
