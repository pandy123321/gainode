<?php

declare(strict_types=1);

namespace library\dao\otc;

use support\extend\Dao;
use support\exception\RunException;
use library\model\otc\OtcTradeModel;

/**
 * OtcTrade DAO — otc_trades 表查询封装（append-only）
 *
 * 注意：append-only 成交事实禁止物理删除/覆盖。本 DAO 对继承的 delete/deleteAll/update/
 * updateAll/updateOrCreate 全部 fail-closed 覆写，从代码层面机械阻断 DAO 层的删除/覆盖路径。
 * 仅保留只读查询与追加（create/insert）。
 */
class OtcTradeDao extends Dao
{
    public function __construct()
    {
        $this->model = OtcTradeModel::class;
    }

    /**
     * 按订单查询成交
     *
     * @param string $otcOrderId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByOrder(string $otcOrderId)
    {
        return $this->fetchAll(['otc_order_id' => $otcOrderId]);
    }

    /**
     * 按买方查询成交
     *
     * @param string $buyerUserId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByBuyer(string $buyerUserId)
    {
        return $this->fetchAll(['buyer_user_id' => $buyerUserId]);
    }

    /**
     * 按卖方查询成交
     *
     * @param string $sellerUserId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBySeller(string $sellerUserId)
    {
        return $this->fetchAll(['seller_user_id' => $sellerUserId]);
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return OtcTradeModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * append-only 成交事实：禁止删除单条。
     *
     * @throws RunException
     */
    public function delete($id, bool $force = false)
    {
        throw new RunException('otc_trades 为 append-only 成交事实：禁止删除成交记录');
    }

    /**
     * append-only 成交事实：禁止批量删除。
     *
     * @throws RunException
     */
    public function deleteAll(array $params, bool $force = false)
    {
        throw new RunException('otc_trades 为 append-only 成交事实：禁止批量删除成交记录');
    }

    /**
     * append-only 成交事实：禁止 UPDATE。
     *
     * @throws RunException
     */
    public function update($id, array $data)
    {
        throw new RunException('otc_trades 为 append-only 成交事实：禁止 UPDATE 成交记录');
    }

    /**
     * append-only 成交事实：禁止批量 UPDATE。
     *
     * @throws RunException
     */
    public function updateAll(array $params, array $data)
    {
        throw new RunException('otc_trades 为 append-only 成交事实：禁止批量 UPDATE 成交记录');
    }

    /**
     * append-only 成交事实：禁止 updateOrCreate。
     *
     * @throws RunException
     */
    public function updateOrCreate(array $params, array $data)
    {
        throw new RunException('otc_trades 为 append-only 成交事实：禁止 updateOrCreate');
    }
}
