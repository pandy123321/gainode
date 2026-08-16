<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\RedPacketItemModel;

class RedPacketItemDao extends Dao
{
    public function __construct()
    {
        $this->model = RedPacketItemModel::class;
    }
}
