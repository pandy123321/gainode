<?php

declare(strict_types=1);

namespace library\dao\prediction;

use support\extend\Dao;
use library\model\prediction\CorrectionCaseModel;

/**
 * CorrectionCase DAO — correction_cases 表查询封装
 */
class CorrectionCaseDao extends Dao
{
    public function __construct()
    {
        $this->model = CorrectionCaseModel::class;
    }

    /**
     * 按市场查询纠错案件
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
     * @return CorrectionCaseModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
