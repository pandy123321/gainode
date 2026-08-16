<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\RechargeOrderModel;

class RechargeOrderDao extends Dao
{
    public function __construct()
    {
        $this->model = RechargeOrderModel::class;
    }
}
