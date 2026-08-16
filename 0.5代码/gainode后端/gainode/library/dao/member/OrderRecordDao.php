<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\OrderRecordModel;

class OrderRecordDao extends Dao
{
    public function __construct()
    {
        $this->model = OrderRecordModel::class;
    }
}
