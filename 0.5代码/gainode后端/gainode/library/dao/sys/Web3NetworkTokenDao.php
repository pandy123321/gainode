<?php

namespace library\dao\sys;

use library\model\sys\Web3NetworkModel;
use support\extend\Dao;
use library\model\sys\Web3NetworkTokenModel;

class Web3NetworkTokenDao extends Dao
{
    public function __construct()
    {
        $this->model = Web3NetworkTokenModel::class;
    }

    /**
     * @param $network_id
     * @param $token_code
     * @return Web3NetworkTokenModel|null
     */
    public function getNetworkTokenObj($network_id,$token_code){
        return $this->fetch(['network_id'=>$network_id,'token_code'=>$token_code]);
    }
}
