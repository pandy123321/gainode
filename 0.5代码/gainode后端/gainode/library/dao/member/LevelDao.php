<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\LevelModel;

class LevelDao extends Dao
{
    public function __construct()
    {
        $this->model = LevelModel::class;
    }
}
