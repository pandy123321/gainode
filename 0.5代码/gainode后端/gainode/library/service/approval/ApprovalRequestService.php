<?php

declare(strict_types=1);

namespace library\service\approval;

use library\dao\approval\ApprovalRequestDao;
use library\model\approval\ApprovalRequestModel;
use support\extend\Service;

/**
 * 审批请求 Service — approval_requests 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer approval_requests
 *
 * 状态机说明（05 §4 canonical Approval，复制冻结）：
 *   draft / pending / changes_requested / approved / rejected / executing / executed / failed
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method ApprovalRequestModel create($data)
 * @method ApprovalRequestModel get($id, string $field = null)
 * @method ApprovalRequestModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ApprovalRequestService extends Service
{
    public function __construct()
    {
        $this->dao = ApprovalRequestDao::class;
        parent::__construct();
    }

    /**
     * 按申请对象查询（只读透传）
     *
     * @param string $objectType
     * @param string $objectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByObject(string $objectType, string $objectId)
    {
        return $this->getNewDao()->getByObject($objectType, $objectId);
    }
}
