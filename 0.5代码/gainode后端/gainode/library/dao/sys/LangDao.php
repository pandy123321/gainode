<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\LangModel;

class LangDao extends Dao
{
    public function __construct()
    {
        $this->model = LangModel::class;
    }
}
