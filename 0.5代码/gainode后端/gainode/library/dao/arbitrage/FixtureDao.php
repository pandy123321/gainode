<?php

namespace library\dao\arbitrage;

use support\extend\Dao;
use library\model\arbitrage\FixtureModel;

class FixtureDao extends Dao
{
    public function __construct()
    {
        $this->model = FixtureModel::class;
    }
}
