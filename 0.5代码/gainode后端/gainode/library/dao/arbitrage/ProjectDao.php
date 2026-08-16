<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\ProjectModel;

class ProjectDao extends Dao
{
    public function __construct()
    {
        $this->model = ProjectModel::class;
    }
}
