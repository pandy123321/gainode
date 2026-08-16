<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\AdminModel;
use support\utils\Data;

class AdminDao extends Dao
{
    public function __construct()
    {
        $this->model = AdminModel::class;
    }

    public function getSelectList(){
        return $this->fetchAll(['eid'=>getEid(),'status'=>1],[],['id as value','account as label'])->toArray();
    }

    /**
     * 根据用户名获取管理员用户对象
     * @param mixed $account
     * @return AdminModel
     */
    public function getUserByAccount(string $account){
        $eid = getEid();
        return $this->fetch(['eid'=>$eid,'account'=>$account,'status'=>['gt',-2]]);
    }

    /**
     * 获取指定账号列表
     * @param array $admin_ids
     * @param $fields
     * @return array
     */
    public function getAdminList(array $admin_ids,$fields=[]){
        $rows = $this->fetchAll(['id'=>['in',$admin_ids]],[],$fields);
        return Data::toKeyArray($rows,'id');
    }
}
