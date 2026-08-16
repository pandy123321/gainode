<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\ConfigModel;

class ConfigDao extends Dao
{
    public function __construct()
    {
        $this->model = ConfigModel::class;
    }
}
