<?php

declare(strict_types=1);

namespace library\dao\prediction;

use support\extend\Dao;
use library\model\prediction\PredictionOrderModel;

/**
 * PredictionOrder DAO — prediction_orders 表查询封装
 */
class PredictionOrderDao extends Dao
{
    public function __construct()
    {
        $this->model = PredictionOrderModel::class;
    }

    /**
     * 按用户查询订单
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }

    /**
     * 按市场查询订单
     *
     * @param string $marketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByMarket(string $marketId)
    {
        return $this->fetchAll(['market_id' => $marketId]);
    }

    /**
     * 按幂等键查询（下单去重）
     *
     * @param string $idempotencyKey
     * @return PredictionOrderModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
