<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\AdminLogsModel;

class AdminLogsDao extends Dao
{
    public function __construct()
    {
        $this->model = AdminLogsModel::class;
    }
}
