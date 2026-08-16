<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\ArticleLangModel;

class ArticleLangDao extends Dao
{
    public function __construct()
    {
        $this->model = ArticleLangModel::class;
    }
}
