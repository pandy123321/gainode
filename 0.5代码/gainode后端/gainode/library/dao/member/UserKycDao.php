<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\UserKycModel;

class UserKycDao extends Dao
{
    public function __construct()
    {
        $this->model = UserKycModel::class;
    }
}
