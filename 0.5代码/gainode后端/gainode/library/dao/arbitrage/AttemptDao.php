<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\AttemptModel;

class AttemptDao extends Dao
{
    public function __construct()
    {
        $this->model = AttemptModel::class;
    }
}
