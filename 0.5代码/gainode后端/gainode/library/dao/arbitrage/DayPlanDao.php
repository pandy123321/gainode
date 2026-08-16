<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\DayPlanModel;

class DayPlanDao extends Dao
{
    public function __construct()
    {
        $this->model = DayPlanModel::class;
    }
}
