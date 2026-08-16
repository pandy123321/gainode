<?php

declare(strict_types=1);

namespace library\dao\robot;

use support\extend\Dao;
use library\model\robot\RobotUpgradeOrderModel;

/**
 * RobotUpgradeOrder DAO — robot_upgrade_orders 表查询封装
 */
class RobotUpgradeOrderDao extends Dao
{
    public function __construct()
    {
        $this->model = RobotUpgradeOrderModel::class;
    }

    /**
     * 按 Robot 查询升级订单
     *
     * @param string $robotId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRobot(string $robotId)
    {
        return $this->fetchAll(['robot_id' => $robotId]);
    }

    /**
     * 按用户查询升级订单
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return RobotUpgradeOrderModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
