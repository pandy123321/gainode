<?php

declare(strict_types=1);

namespace library\dao\parameter;

use support\extend\Dao;
use library\model\parameter\ParameterReleaseModel;

/**
 * ParameterRelease DAO — parameter_releases 表查询封装
 */
class ParameterReleaseDao extends Dao
{
    public function __construct()
    {
        $this->model = ParameterReleaseModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return ParameterReleaseModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 取当前 Active Release（status='active'，按 activated_at 倒序取最新）。
     *
     * 用于 56 级规则读取器（RobotRuleReader）：只有 Active Release 的 snapshot
     * 才能作为正式规则来源；无则下游能力 fail-closed / UNAVAILABLE。
     *
     * @return ParameterReleaseModel|null
     */
    public function getActive()
    {
        return $this->fetch(
            ['status' => ParameterReleaseModel::STATUS_ACTIVE],
            ['activated_at' => 'desc', 'created_time' => 'desc']
        );
    }
}
