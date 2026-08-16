<?php

namespace library\service\member;

use library\model\member\UserLogsModel;
use library\dao\member\UserLogsDao;
use support\extend\Service;

/**
 * Service
 * @method UserLogsModel create($data)
 * @method UserLogsModel updateOrCreate(array $params,array $data)
 * @method UserLogsModel update($id,array $data){
 * @method UserLogsModel get($id,string $field = null)
 * @method UserLogsModel find($id)
 * @method UserLogsModel findOrFail($id)
 * @method UserLogsModel firstOrCreate(array $params,array $data)
 * @method UserLogsModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class UserLogsService extends Service
{
    public function __construct()
    {
        $this->dao = UserLogsDao::class;
        parent::__construct();
    }
}
