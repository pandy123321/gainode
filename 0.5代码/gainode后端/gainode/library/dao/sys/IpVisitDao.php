<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\IpVisitModel;

class IpVisitDao extends Dao
{
    public function __construct()
    {
        $this->model = IpVisitModel::class;
    }
}
