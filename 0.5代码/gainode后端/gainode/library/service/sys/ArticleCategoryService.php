<?php

namespace library\service\sys;

use library\model\sys\ArticleCategoryModel;
use library\dao\sys\ArticleCategoryDao;
use support\extend\Service;

/**
 * Service
 * @method ArticleCategoryModel create($data)
 * @method ArticleCategoryModel updateOrCreate(array $params,array $data)
 * @method ArticleCategoryModel update($id,array $data){
 * @method ArticleCategoryModel get($id,string $field = null)
 * @method ArticleCategoryModel find($id)
 * @method ArticleCategoryModel findOrFail($id)
 * @method ArticleCategoryModel firstOrCreate(array $params,array $data)
 * @method ArticleCategoryModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class ArticleCategoryService extends Service
{
    public function __construct()
    {
        $this->dao = ArticleCategoryDao::class;
        parent::__construct();
    }

    public function getSelectList(){
        return $this->getNewDao()->fetchAll(['status'=>1],[],['id','name','pid']);
    }
}
