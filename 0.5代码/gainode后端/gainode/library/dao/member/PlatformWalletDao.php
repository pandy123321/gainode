<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\PlatformWalletModel;

class PlatformWalletDao extends Dao
{
    public function __construct()
    {
        $this->model = PlatformWalletModel::class;
    }
}
