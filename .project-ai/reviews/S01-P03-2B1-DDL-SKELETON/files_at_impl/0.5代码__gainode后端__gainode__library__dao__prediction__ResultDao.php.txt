<?php

declare(strict_types=1);

namespace library\dao\prediction;

use support\extend\Dao;
use library\model\prediction\ResultModel;

/**
 * Result DAO — results 表查询封装
 */
class ResultDao extends Dao
{
    public function __construct()
    {
        $this->model = ResultModel::class;
    }

    /**
     * 按市场查询结果
     *
     * @param string $marketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByMarket(string $marketId)
    {
        return $this->fetchAll(['market_id' => $marketId]);
    }

    /**
     * 按幂等键查询（确认去重）
     *
     * @param string $idempotencyKey
     * @return ResultModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
