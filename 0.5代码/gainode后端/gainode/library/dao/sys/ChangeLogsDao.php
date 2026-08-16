<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\ChangeLogsModel;

class ChangeLogsDao extends Dao
{
    public function __construct()
    {
        $this->model = ChangeLogsModel::class;
    }
}
