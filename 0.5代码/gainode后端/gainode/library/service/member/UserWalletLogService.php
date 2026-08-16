<?php

namespace library\service\member;

use library\model\member\UserWalletLogModel;
use library\dao\member\UserWalletLogDao;
use support\extend\Service;

/**
 * Service
 * @method UserWalletLogModel create($data)
 * @method UserWalletLogModel updateOrCreate(array $params,array $data)
 * @method UserWalletLogModel update($id,array $data){
 * @method UserWalletLogModel get($id,string $field = null)
 * @method UserWalletLogModel find($id)
 * @method UserWalletLogModel findOrFail($id)
 * @method UserWalletLogModel firstOrCreate(array $params,array $data)
 * @method UserWalletLogModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class UserWalletLogService extends Service
{
    public function __construct()
    {
        $this->dao = UserWalletLogDao::class;
        parent::__construct();
    }
}
