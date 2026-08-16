<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\CountryModel;

class CountryDao extends Dao
{
    public function __construct()
    {
        $this->model = CountryModel::class;
    }
}
