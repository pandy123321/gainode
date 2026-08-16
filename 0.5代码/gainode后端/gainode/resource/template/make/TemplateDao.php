<?php

namespace library\dao\module;

use support\extend\Dao;
use library\model\module\TemplateModel;

class TemplateDao extends Dao
{
    public function __construct()
    {
        $this->model = TemplateModel::class;
    }
}
