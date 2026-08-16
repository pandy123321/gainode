<?php

namespace library\service\sys;

use library\model\sys\Web3NetworkModel;
use library\dao\sys\Web3NetworkDao;
use support\extend\Service;

/**
 * Service
 * @method Web3NetworkModel create($data)
 * @method Web3NetworkModel updateOrCreate(array $params,array $data)
 * @method Web3NetworkModel update($id,array $data){
 * @method Web3NetworkModel get($id,string $field = null)
 * @method Web3NetworkModel find($id)
 * @method Web3NetworkModel findOrFail($id)
 * @method Web3NetworkModel firstOrCreate(array $params,array $data)
 * @method Web3NetworkModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class Web3NetworkService extends Service
{
    public function __construct()
    {
        $this->dao = Web3NetworkDao::class;
        parent::__construct();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSelectNetwork(){
        return $this->fetchAll(['status'=>1],['sort'=>'asc'],['id','name','code','family','native_symbol','native_name','native_decimals']);
    }
}
