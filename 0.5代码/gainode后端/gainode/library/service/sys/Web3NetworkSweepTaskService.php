<?php

namespace library\service\sys;

use library\model\sys\Web3NetworkSweepTaskModel;
use library\dao\sys\Web3NetworkSweepTaskDao;
use support\extend\Service;

/**
 * Service
 * @method Web3NetworkSweepTaskModel create($data)
 * @method Web3NetworkSweepTaskModel updateOrCreate(array $params,array $data)
 * @method Web3NetworkSweepTaskModel update($id,array $data){
 * @method Web3NetworkSweepTaskModel get($id,string $field = null)
 * @method Web3NetworkSweepTaskModel find($id)
 * @method Web3NetworkSweepTaskModel findOrFail($id)
 * @method Web3NetworkSweepTaskModel firstOrCreate(array $params,array $data)
 * @method Web3NetworkSweepTaskModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class Web3NetworkSweepTaskService extends Service
{
    public function __construct()
    {
        $this->dao = Web3NetworkSweepTaskDao::class;
        parent::__construct();
    }
}
