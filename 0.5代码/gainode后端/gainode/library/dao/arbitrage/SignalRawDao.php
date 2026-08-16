<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\SignalRawModel;

class SignalRawDao extends Dao
{
    public function __construct()
    {
        $this->model = SignalRawModel::class;
    }
}
