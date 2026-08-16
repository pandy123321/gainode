<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\LangValueModel;

class LangValueDao extends Dao
{
    public function __construct()
    {
        $this->model = LangValueModel::class;
    }
}
