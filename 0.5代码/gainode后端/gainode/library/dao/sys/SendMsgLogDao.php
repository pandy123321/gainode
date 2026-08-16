<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\SendMsgLogModel;

class SendMsgLogDao extends Dao
{
    public function __construct()
    {
        $this->model = SendMsgLogModel::class;
    }
}
