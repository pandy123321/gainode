<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\Web3NetworkSweepTaskModel;

class Web3NetworkSweepTaskDao extends Dao
{
    public function __construct()
    {
        $this->model = Web3NetworkSweepTaskModel::class;
    }
}
