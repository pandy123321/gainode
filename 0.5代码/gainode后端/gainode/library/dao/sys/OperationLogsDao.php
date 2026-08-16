<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\OperationLogsModel;

class OperationLogsDao extends Dao
{
    public function __construct()
    {
        $this->model = OperationLogsModel::class;
    }
}
