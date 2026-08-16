<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\UserAuthModel;

class UserAuthDao extends Dao
{
    public function __construct()
    {
        $this->model = UserAuthModel::class;
    }
}
