<?php

namespace library\service\module;

use library\model\module\TemplateModel;
use library\dao\module\TemplateDao;
use support\extend\Service;

/**
 * Service
 * @method TemplateModel create($data)
 * @method TemplateModel updateOrCreate(array $params,array $data)
 * @method TemplateModel update($id,array $data){
 * @method TemplateModel get($id,string $field = null)
 * @method TemplateModel find($id)
 * @method TemplateModel findOrFail($id)
 * @method TemplateModel firstOrCreate(array $params,array $data)
 * @method TemplateModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class TemplateService extends Service
{
    public function __construct()
    {
        $this->dao = TemplateDao::class;
        parent::__construct();
    }
}
