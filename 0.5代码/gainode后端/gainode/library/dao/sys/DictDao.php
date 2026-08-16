<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\DictModel;

class DictDao extends Dao
{
    public function __construct()
    {
        $this->model = DictModel::class;
    }

    /**
     * @param $type
     * @return mixed[]
     */
    public function getSelectList($type){
        return $this->fetchAll(['type'=>$type],['sort'=>'desc'],['id','name','code'])->toArray();
    }
}
