<?php

namespace library\service\sys;

use library\model\sys\MakeLogsModel;
use library\dao\sys\MakeLogsDao;
use support\extend\Service;

/**
 * Service
 * @method MakeLogsModel create($data)
 * @method MakeLogsModel updateOrCreate(array $params,array $data)
 * @method MakeLogsModel update($id,array $data){
 * @method MakeLogsModel get($id,string $field = null)
 * @method MakeLogsModel find($id)
 * @method MakeLogsModel findOrFail($id)
 * @method MakeLogsModel firstOrCreate(array $params,array $data)
 * @method MakeLogsModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class MakeLogsService extends Service
{
    public function __construct()
    {
        $this->dao = MakeLogsDao::class;
        parent::__construct();
    }
}
