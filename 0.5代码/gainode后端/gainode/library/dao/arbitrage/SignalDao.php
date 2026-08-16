<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\SignalModel;

class SignalDao extends Dao
{
    public function __construct()
    {
        $this->model = SignalModel::class;
    }
}
