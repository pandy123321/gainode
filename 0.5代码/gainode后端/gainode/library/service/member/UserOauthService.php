<?php

namespace library\service\member;

use library\model\member\UserOauthModel;
use library\dao\member\UserOauthDao;
use support\extend\Service;

/**
 * Service
 * @method UserOauthModel create($data)
 * @method UserOauthModel updateOrCreate(array $params,array $data)
 * @method UserOauthModel update($id,array $data){
 * @method UserOauthModel get($id,string $field = null)
 * @method UserOauthModel find($id)
 * @method UserOauthModel findOrFail($id)
 * @method UserOauthModel firstOrCreate(array $params,array $data)
 * @method UserOauthModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class UserOauthService extends Service
{
    public function __construct()
    {
        $this->dao = UserOauthDao::class;
        parent::__construct();
    }
}
