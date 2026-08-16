<?php

namespace library\service\sys;

use library\model\sys\ArticleModel;
use library\dao\sys\ArticleDao;
use support\extend\Service;

/**
 * Service
 * @method ArticleModel create($data)
 * @method ArticleModel updateOrCreate(array $params,array $data)
 * @method ArticleModel update($id,array $data){
 * @method ArticleModel get($id,string $field = null)
 * @method ArticleModel find($id)
 * @method ArticleModel findOrFail($id)
 * @method ArticleModel firstOrCreate(array $params,array $data)
 * @method ArticleModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class ArticleService extends Service
{
    public function __construct()
    {
        $this->dao = ArticleDao::class;
        parent::__construct();
    }

    public function getHelpList($lang='en'){
        $list = $this->fetchAll(['status'=>1],[],['title','content','category_id']);
        return $list;
    }
}
