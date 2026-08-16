<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\CrontabGroupModel;

class CrontabGroupDao extends Dao
{
    public function __construct()
    {
        $this->model = CrontabGroupModel::class;
    }

    /**
     * 获取可选的任务分类
     * @return array
     */
    public function getGroupList(){
        $rows = $this->fetchAll([],['sort'=>'desc'],['id','name'])->toArray();
        $data = [];
        foreach($rows as $v){
            $data[$v['id']] = $v;
        }
        return $data;
    }
}
