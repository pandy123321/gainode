<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\ProjectOrderLogsModel;

class ProjectOrderLogsDao extends Dao
{
    public function __construct()
    {
        $this->model = ProjectOrderLogsModel::class;
    }
}
