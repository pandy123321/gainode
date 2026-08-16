<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\RouteModel;

class RouteDao extends Dao
{
    public function __construct()
    {
        $this->model = RouteModel::class;
    }
}
