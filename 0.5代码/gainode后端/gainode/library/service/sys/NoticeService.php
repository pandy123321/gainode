<?php

namespace library\service\sys;

use library\model\sys\NoticeModel;
use library\dao\sys\NoticeDao;
use support\extend\Service;

/**
 * Service
 * @method NoticeModel create($data)
 * @method NoticeModel updateOrCreate(array $params,array $data)
 * @method NoticeModel update($id,array $data){
 * @method NoticeModel get($id,string $field = null)
 * @method NoticeModel find($id)
 * @method NoticeModel findOrFail($id)
 * @method NoticeModel firstOrCreate(array $params,array $data)
 * @method NoticeModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class NoticeService extends Service
{
    public function __construct()
    {
        $this->dao = NoticeDao::class;
        parent::__construct();
    }
}
