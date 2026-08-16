<?php

declare(strict_types=1);

namespace library\dao\prediction;

use support\extend\Dao;
use library\model\prediction\SettlementModel;

/**
 * Settlement DAO — settlements 表查询封装
 */
class SettlementDao extends Dao
{
    public function __construct()
    {
        $this->model = SettlementModel::class;
    }

    /**
     * 按市场查询结算单
     *
     * @param string $marketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByMarket(string $marketId)
    {
        return $this->fetchAll(['market_id' => $marketId]);
    }

    /**
     * 按结算批查询结算单
     *
     * @param string $batchId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByBatch(string $batchId)
    {
        return $this->fetchAll(['batch_id' => $batchId]);
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return SettlementModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
