<?php

declare(strict_types=1);

namespace library\dao\risk;

use support\extend\Dao;
use library\model\risk\RiskCaseModel;

/**
 * RiskCase DAO — risk_cases 表查询封装
 */
class RiskCaseDao extends Dao
{
    public function __construct()
    {
        $this->model = RiskCaseModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return RiskCaseModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按用户查询风控案件
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }
}
