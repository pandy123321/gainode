<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\RoleModel;

class RoleDao extends Dao
{
    public function __construct()
    {
        $this->model = RoleModel::class;
    }

    public function getOptionList(){
        return $this->fetchAll(['eid'=>getEid(),'status'=>1],['sort'=>'asc'],['id as value','name as label'])->toArray();
    }
}
