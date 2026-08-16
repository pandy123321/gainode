<?php

namespace library\service\sys;

use Illuminate\Database\Eloquent\Collection;
use library\model\sys\AdminLogsModel;
use library\dao\sys\AdminLogsDao;
use support\extend\Service;

/**
 * Service
 * @method AdminLogsModel create($data)
 * @method AdminLogsModel updateOrCreate(array $params,array $data)
 * @method AdminLogsModel update($id,array $data){
 * @method AdminLogsModel get($id,string $field = null)
 * @method AdminLogsModel find($id)
 * @method AdminLogsModel findOrFail($id)
 * @method AdminLogsModel firstOrCreate(array $params,array $data)
 * @method AdminLogsModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class AdminLogsService extends Service
{
    public function __construct()
    {
        $this->dao = AdminLogsDao::class;
        parent::__construct();
    }
}
