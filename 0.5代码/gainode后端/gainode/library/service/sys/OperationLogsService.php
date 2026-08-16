<?php

namespace library\service\sys;

use library\model\sys\OperationLogsModel;
use library\dao\sys\OperationLogsDao;
use support\extend\Service;

/**
 * Service
 * @method OperationLogsModel create($data)
 * @method OperationLogsModel updateOrCreate(array $params,array $data)
 * @method OperationLogsModel update($id,array $data){
 * @method OperationLogsModel get($id,string $field = null)
 * @method OperationLogsModel find($id)
 * @method OperationLogsModel findOrFail($id)
 * @method OperationLogsModel firstOrCreate(array $params,array $data)
 * @method OperationLogsModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class OperationLogsService extends Service
{
    public function __construct()
    {
        $this->dao = OperationLogsDao::class;
        parent::__construct();
    }
}
