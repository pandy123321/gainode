<?php

namespace library\service\sys;

use library\model\sys\NoticeCategoryModel;
use library\dao\sys\NoticeCategoryDao;
use support\extend\Service;

/**
 * Service
 * @method NoticeCategoryModel create($data)
 * @method NoticeCategoryModel updateOrCreate(array $params,array $data)
 * @method NoticeCategoryModel update($id,array $data){
 * @method NoticeCategoryModel get($id,string $field = null)
 * @method NoticeCategoryModel find($id)
 * @method NoticeCategoryModel findOrFail($id)
 * @method NoticeCategoryModel firstOrCreate(array $params,array $data)
 * @method NoticeCategoryModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class NoticeCategoryService extends Service
{
    public function __construct()
    {
        $this->dao = NoticeCategoryDao::class;
        parent::__construct();
    }

    public function getSelectList(){
        return $this->getNewDao()->fetchAll(['status'=>1],[],['id','name']);
    }
}
