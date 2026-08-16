<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\ProjectOrderModel;

class ProjectOrderDao extends Dao
{
    public function __construct()
    {
        $this->model = ProjectOrderModel::class;
    }
}
