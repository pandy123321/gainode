<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\WithdrawOrderModel;

class WithdrawOrderDao extends Dao
{
    public function __construct()
    {
        $this->model = WithdrawOrderModel::class;
    }
}
