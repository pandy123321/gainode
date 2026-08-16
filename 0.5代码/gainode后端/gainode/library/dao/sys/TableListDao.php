<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\TableListModel;

class TableListDao extends Dao
{
    public function __construct()
    {
        $this->model = TableListModel::class;
    }

    /**
     * @param $code
     * @return TableListModel
     */
    public function getTableByCode($code){
        return $this->fetch(['tb_code'=>$code]);
    }

    /**
     * @param $name
     * @return TableListModel
     */
    public function getTableByName($name){
        return $this->fetch(['tb_name'=>$name]);
    }
}
