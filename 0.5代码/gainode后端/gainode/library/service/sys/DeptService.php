<?php

namespace library\service\sys;

use library\model\sys\DeptModel;
use library\dao\sys\DeptDao;
use support\extend\Service;

/**
 * Service
 * @method DeptModel create($data)
 * @method DeptModel updateOrCreate(array $params,array $data)
 * @method DeptModel update($id,array $data){
 * @method DeptModel get($id,string $field = null)
 * @method DeptModel find($id)
 * @method DeptModel findOrFail($id)
 * @method DeptModel firstOrCreate(array $params,array $data)
 * @method DeptModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class DeptService extends Service
{
    public function __construct()
    {
        $this->dao = DeptDao::class;
        parent::__construct();
    }

    public function getSelectList(){
        return $this->getNewDao()->fetchAll(['status'=>1],[],['id','name','pid']);
    }
}
