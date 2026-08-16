<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\CasbinRestfulModel;

class CasbinRestfulDao extends Dao
{
    public function __construct()
    {
        $this->model = CasbinRestfulModel::class;
    }
}
