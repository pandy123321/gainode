<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\CasbinRbacModel;

class CasbinRbacDao extends Dao
{
    public function __construct()
    {
        $this->model = CasbinRbacModel::class;
    }
}
