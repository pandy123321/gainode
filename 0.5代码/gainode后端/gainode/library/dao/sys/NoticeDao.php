<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\NoticeModel;

class NoticeDao extends Dao
{
    public function __construct()
    {
        $this->model = NoticeModel::class;
    }
}
