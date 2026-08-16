<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\MakeLogsModel;

class MakeLogsDao extends Dao
{
    public function __construct()
    {
        $this->model = MakeLogsModel::class;
    }
}
