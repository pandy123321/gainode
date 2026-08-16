<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\UserWalletLogModel;

class UserWalletLogDao extends Dao
{
    public function __construct()
    {
        $this->model = UserWalletLogModel::class;
    }
}
