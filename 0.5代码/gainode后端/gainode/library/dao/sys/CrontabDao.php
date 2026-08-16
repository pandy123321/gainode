<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\CrontabModel;

class CrontabDao extends Dao
{
    public function __construct()
    {
        $this->model = CrontabModel::class;
    }
}
