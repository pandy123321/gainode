<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\NoticeCategoryModel;

class NoticeCategoryDao extends Dao
{
    public function __construct()
    {
        $this->model = NoticeCategoryModel::class;
    }
}
