<?php

declare(strict_types=1);

namespace library\dao\power;

use support\extend\Dao;
use library\model\power\PowerPositionModel;

/**
 * PowerPosition DAO — power_positions 表查询封装
 */
class PowerPositionDao extends Dao
{
    public function __construct()
    {
        $this->model = PowerPositionModel::class;
    }

    /**
     * 按用户查询持仓（一用户一持仓，主键查询）
     *
     * @param string $userId
     * @return PowerPositionModel|null
     */
    public function getByUser(string $userId)
    {
        return $this->find($userId);
    }
}
