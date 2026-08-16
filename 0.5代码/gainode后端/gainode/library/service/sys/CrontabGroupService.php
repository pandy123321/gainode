<?php

namespace library\service\sys;

use library\model\sys\CrontabGroupModel;
use library\dao\sys\CrontabGroupDao;
use support\extend\Service;

/**
 * Service
 * @method CrontabGroupModel create($data)
 * @method CrontabGroupModel updateOrCreate(array $params,array $data)
 * @method CrontabGroupModel update($id,array $data){
 * @method CrontabGroupModel get($id,string $field = null)
 * @method CrontabGroupModel find($id)
 * @method CrontabGroupModel findOrFail($id)
 * @method CrontabGroupModel firstOrCreate(array $params,array $data)
 * @method CrontabGroupModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class CrontabGroupService extends Service
{
    public function __construct()
    {
        $this->dao = CrontabGroupDao::class;
        parent::__construct();
    }
}
