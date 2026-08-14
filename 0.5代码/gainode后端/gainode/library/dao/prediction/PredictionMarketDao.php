<?php

declare(strict_types=1);

namespace library\dao\prediction;

use support\extend\Dao;
use library\model\prediction\PredictionMarketModel;

/**
 * PredictionMarket DAO — prediction_markets 表查询封装
 */
class PredictionMarketDao extends Dao
{
    public function __construct()
    {
        $this->model = PredictionMarketModel::class;
    }

    /**
     * 按赛事查询市场
     *
     * @param string $eventId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByEvent(string $eventId)
    {
        return $this->fetchAll(['event_id' => $eventId]);
    }

    /**
     * 按幂等键查询（创建去重）
     *
     * @param string $idempotencyKey
     * @return PredictionMarketModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
