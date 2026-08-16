<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\Web3NetworkModel;

class Web3NetworkDao extends Dao
{
    public function __construct()
    {
        $this->model = Web3NetworkModel::class;
    }
}
