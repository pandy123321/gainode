<?php

namespace library\service\sys;

use library\model\sys\Web3NetworkModel;
use library\model\sys\Web3NetworkTokenModel;
use library\dao\sys\Web3NetworkTokenDao;
use support\extend\Service;

/**
 * Service
 * @method Web3NetworkTokenModel create($data)
 * @method Web3NetworkTokenModel updateOrCreate(array $params,array $data)
 * @method Web3NetworkTokenModel update($id,array $data){
 * @method Web3NetworkTokenModel get($id,string $field = null)
 * @method Web3NetworkTokenModel find($id)
 * @method Web3NetworkTokenModel findOrFail($id)
 * @method Web3NetworkTokenModel firstOrCreate(array $params,array $data)
 * @method Web3NetworkTokenModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method Web3NetworkTokenModel getNetworkTokenObj($network_id,$token_code)
 */
class Web3NetworkTokenService extends Service
{
    public function __construct()
    {
        $this->dao = Web3NetworkTokenDao::class;
        parent::__construct();
    }

    public function getNetworkTokenList($network_id){
        $fields = ['network_code','symbol','name','standard'];
        return $this->fetchAll(['network_id'=>$network_id,'status'=>1],[],$fields);
    }

    public function getRechargeTokenList($network_id){
        $fields = ['network_code','symbol','name','standard'];
        return $this->fetchAll(['network_id'=>$network_id,'is_recharge'=>1,'status'=>1],[],$fields);
    }

    public function getWithdrawTokenList($network_id){
        $fields = ['network_code','symbol','name','standard'];
        return $this->fetchAll(['network_id'=>$network_id,'is_withdraw'=>1,'status'=>1],[],$fields);
    }

    public function getTransferTokenList($network_id){
        $fields = ['network_code','symbol','name','standard'];
        return $this->fetchAll(['network_id'=>$network_id,'is_transfer'=>1,'status'=>1],[],$fields);
    }
}
