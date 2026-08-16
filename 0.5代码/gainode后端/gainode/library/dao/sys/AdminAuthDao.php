<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\AdminAuthModel;

class AdminAuthDao extends Dao
{
    public function __construct()
    {
        $this->model = AdminAuthModel::class;
    }
}
