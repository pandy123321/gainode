<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\ProjectOrderDayModel;

class ProjectOrderDayDao extends Dao
{
    public function __construct()
    {
        $this->model = ProjectOrderDayModel::class;
    }
}
