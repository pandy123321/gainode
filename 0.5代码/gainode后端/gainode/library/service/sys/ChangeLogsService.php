<?php

namespace library\service\sys;

use library\model\sys\ChangeLogsModel;
use library\dao\sys\ChangeLogsDao;
use support\extend\Service;

/**
 * Service
 * @method ChangeLogsModel create($data)
 * @method ChangeLogsModel updateOrCreate(array $params,array $data)
 * @method ChangeLogsModel update($id,array $data){
 * @method ChangeLogsModel get($id,string $field = null)
 * @method ChangeLogsModel find($id)
 * @method ChangeLogsModel findOrFail($id)
 * @method ChangeLogsModel firstOrCreate(array $params,array $data)
 * @method ChangeLogsModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class ChangeLogsService extends Service
{
    public function __construct()
    {
        $this->dao = ChangeLogsDao::class;
        parent::__construct();
    }
}
