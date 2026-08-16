<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\UserLogsModel;

class UserLogsDao extends Dao
{
    public function __construct()
    {
        $this->model = UserLogsModel::class;
    }
}
