<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\ArticleModel;

class ArticleDao extends Dao
{
    public function __construct()
    {
        $this->model = ArticleModel::class;
    }
}
