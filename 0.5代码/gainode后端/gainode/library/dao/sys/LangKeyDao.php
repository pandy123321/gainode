<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\LangKeyModel;

class LangKeyDao extends Dao
{
    public function __construct()
    {
        $this->model = LangKeyModel::class;
    }
}
