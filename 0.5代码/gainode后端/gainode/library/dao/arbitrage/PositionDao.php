<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\PositionModel;

class PositionDao extends Dao
{
    public function __construct()
    {
        $this->model = PositionModel::class;
    }
}
