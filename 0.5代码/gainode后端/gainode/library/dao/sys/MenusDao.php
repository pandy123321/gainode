<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\MenusModel;

class MenusDao extends Dao
{
    public function __construct()
    {
        $this->model = MenusModel::class;
    }
}
