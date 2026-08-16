<?php

declare(strict_types=1);

namespace library\dao\approval;

use support\extend\Dao;
use library\model\approval\ApprovalRequestModel;

/**
 * ApprovalRequest DAO — approval_requests 表查询封装
 */
class ApprovalRequestDao extends Dao
{
    public function __construct()
    {
        $this->model = ApprovalRequestModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return ApprovalRequestModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按申请对象查询（类型 + ID）
     *
     * @param string $objectType
     * @param string $objectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByObject(string $objectType, string $objectId)
    {
        return $this->fetchAll([
            'request_object_type' => $objectType,
            'request_object_id' => $objectId,
        ]);
    }
}
