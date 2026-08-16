<?php

namespace library\dao\member;

use support\extend\Dao;
use library\model\member\UserModel;
use support\utils\Data;

class UserDao extends Dao
{
    public function __construct()
    {
        $this->model = UserModel::class;
    }

    public function getUserByAccount($account){
        $eid = getEid();
        return $this->fetch(['eid'=>$eid,'account'=>$account,'status'=>['gt',-2]]);
    }

    /**
     * 获取指定账号列表
     * @param array $user_ids
     * @param $fields
     * @return array
     */
    public function getUserList(array $user_ids,$fields=[]){
        $rows = $this->fetchAll(['id'=>['in',$user_ids]],[],$fields);
        return Data::toKeyArray($rows,'id');
    }
}
