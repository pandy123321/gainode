<?php

namespace library\service\sys;

use library\model\sys\LangValueModel;
use library\dao\sys\LangValueDao;
use support\extend\Service;

/**
 * Service
 * @method LangValueModel create($data)
 * @method LangValueModel updateOrCreate(array $params,array $data)
 * @method LangValueModel update($id,array $data){
 * @method LangValueModel get($id,string $field = null)
 * @method LangValueModel find($id)
 * @method LangValueModel findOrFail($id)
 * @method LangValueModel firstOrCreate(array $params,array $data)
 * @method LangValueModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class LangValueService extends Service
{
    public function __construct()
    {
        $this->dao = LangValueDao::class;
        parent::__construct();
    }
}
