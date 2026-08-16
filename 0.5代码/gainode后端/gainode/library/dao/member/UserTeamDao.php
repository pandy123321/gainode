<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\UserTeamModel;

class UserTeamDao extends Dao
{
    public function __construct()
    {
        $this->model = UserTeamModel::class;
    }

    public function getUserTeamByCode(string $code)
    {
        return $this->fetch(['invite_code'=>$code]);
    }
}
