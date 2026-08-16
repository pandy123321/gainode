<?php

declare(strict_types=1);

namespace library\dao\prediction;

use support\extend\Dao;
use library\model\prediction\SettlementBatchModel;

/**
 * SettlementBatch DAO — settlement_batches 表查询封装
 */
class SettlementBatchDao extends Dao
{
    public function __construct()
    {
        $this->model = SettlementBatchModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return SettlementBatchModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
