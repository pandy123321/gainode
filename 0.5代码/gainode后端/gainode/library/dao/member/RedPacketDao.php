<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\RedPacketModel;

class RedPacketDao extends Dao
{
    public function __construct()
    {
        $this->model = RedPacketModel::class;
    }
}
