<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\FlowNumbersModel;

class FlowNumbersDao extends Dao
{
    public function __construct()
    {
        $this->model = FlowNumbersModel::class;
    }
}
