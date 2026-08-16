<?php

namespace library\service\sys;

use library\model\sys\CasbinRestfulModel;
use library\dao\sys\CasbinRestfulDao;
use support\extend\Service;

/**
 * Service
 * @method CasbinRestfulModel create($data)
 * @method CasbinRestfulModel updateOrCreate(array $params,array $data)
 * @method CasbinRestfulModel update($id,array $data){
 * @method CasbinRestfulModel get($id,string $field = null)
 * @method CasbinRestfulModel find($id)
 * @method CasbinRestfulModel findOrFail($id)
 * @method CasbinRestfulModel firstOrCreate(array $params,array $data)
 * @method CasbinRestfulModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class CasbinRestfulService extends Service
{
    public function __construct()
    {
        $this->dao = CasbinRestfulDao::class;
        parent::__construct();
    }
}
