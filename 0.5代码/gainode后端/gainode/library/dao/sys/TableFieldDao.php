<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\TableFieldModel;

class TableFieldDao extends Dao
{
    public function __construct()
    {
        $this->model = TableFieldModel::class;
    }
}
