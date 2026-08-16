<?php

namespace library\service\member;

use library\model\member\TransferOrderModel;
use library\dao\member\TransferOrderDao;
use support\extend\Service;

/**
 * Service
 * @method TransferOrderModel create($data)
 * @method TransferOrderModel updateOrCreate(array $params,array $data)
 * @method TransferOrderModel update($id,array $data){
 * @method TransferOrderModel get($id,string $field = null)
 * @method TransferOrderModel find($id)
 * @method TransferOrderModel findOrFail($id)
 * @method TransferOrderModel firstOrCreate(array $params,array $data)
 * @method TransferOrderModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class TransferOrderService extends Service
{
    public function __construct()
    {
        $this->dao = TransferOrderDao::class;
        parent::__construct();
    }
}
