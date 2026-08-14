<?php

declare(strict_types=1);

namespace library\dao\robot;

use support\extend\Dao;
use library\model\robot\RobotRewardModel;

/**
 * RobotReward DAO — robot_rewards 表查询封装
 */
class RobotRewardDao extends Dao
{
    public function __construct()
    {
        $this->model = RobotRewardModel::class;
    }

    /**
     * 按 Robot 查询奖励记录
     *
     * @param string $robotId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRobot(string $robotId)
    {
        return $this->fetchAll(['robot_id' => $robotId]);
    }

    /**
     * 按周期 + Robot 查询（同周期唯一结算）
     *
     * @param string $robotId
     * @param string $period
     * @return RobotRewardModel|null
     */
    public function getByRobotAndPeriod(string $robotId, string $period)
    {
        return $this->fetch(['robot_id' => $robotId, 'period' => $period]);
    }

    /**
     * 按幂等键查询（生成去重）
     *
     * @param string $idempotencyKey
     * @return RobotRewardModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
