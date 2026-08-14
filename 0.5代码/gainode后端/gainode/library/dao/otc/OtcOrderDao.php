<?php

declare(strict_types=1);

namespace library\dao\otc;

use support\extend\Dao;
use library\model\otc\OtcOrderModel;

/**
 * OtcOrder DAO — otc_orders 表查询封装
 */
class OtcOrderDao extends Dao
{
    public function __construct()
    {
        $this->model = OtcOrderModel::class;
    }

    /**
     * 按用户查询 OTC 订单
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }

    /**
     * 按幂等键查询（挂单去重）
     *
     * @param string $idempotencyKey
     * @return OtcOrderModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
