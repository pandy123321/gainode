<?php

namespace library\service\member;

use library\model\member\PlatformWalletModel;
use library\dao\member\PlatformWalletDao;
use support\extend\Service;

/**
 * Service
 * @method PlatformWalletModel create($data)
 * @method PlatformWalletModel updateOrCreate(array $params,array $data)
 * @method PlatformWalletModel update($id,array $data){
 * @method PlatformWalletModel get($id,string $field = null)
 * @method PlatformWalletModel find($id)
 * @method PlatformWalletModel findOrFail($id)
 * @method PlatformWalletModel firstOrCreate(array $params,array $data)
 * @method PlatformWalletModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class PlatformWalletService extends Service
{
    public function __construct()
    {
        $this->dao = PlatformWalletDao::class;
        parent::__construct();
    }
}
