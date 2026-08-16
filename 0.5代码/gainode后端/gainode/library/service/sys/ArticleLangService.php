<?php

namespace library\service\sys;

use library\model\sys\ArticleLangModel;
use library\dao\sys\ArticleLangDao;
use support\extend\Service;

/**
 * Service
 * @method ArticleLangModel create($data)
 * @method ArticleLangModel updateOrCreate(array $params,array $data)
 * @method ArticleLangModel update($id,array $data){
 * @method ArticleLangModel get($id,string $field = null)
 * @method ArticleLangModel find($id)
 * @method ArticleLangModel findOrFail($id)
 * @method ArticleLangModel firstOrCreate(array $params,array $data)
 * @method ArticleLangModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class ArticleLangService extends Service
{
    public function __construct()
    {
        $this->dao = ArticleLangDao::class;
        parent::__construct();
    }
}
