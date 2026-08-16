<?php

declare(strict_types=1);

namespace library\dao\prediction;

use support\extend\Dao;
use library\model\prediction\RefundCaseModel;

/**
 * RefundCase DAO — refund_cases 表查询封装
 */
class RefundCaseDao extends Dao
{
    public function __construct()
    {
        $this->model = RefundCaseModel::class;
    }

    /**
     * 按市场查询退款案件
     *
     * @param string $marketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByMarket(string $marketId)
    {
        return $this->fetchAll(['market_id' => $marketId]);
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return RefundCaseModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
