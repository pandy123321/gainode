<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\TransferOrderModel;

class TransferOrderDao extends Dao
{
    public function __construct()
    {
        $this->model = TransferOrderModel::class;
    }
}
