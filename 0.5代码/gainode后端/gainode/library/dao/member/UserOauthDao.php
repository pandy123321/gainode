<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\UserOauthModel;

class UserOauthDao extends Dao
{
    public function __construct()
    {
        $this->model = UserOauthModel::class;
    }
}
