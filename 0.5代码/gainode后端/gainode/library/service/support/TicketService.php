<?php

declare(strict_types=1);

namespace library\service\support;

use library\dao\support\TicketDao;
use library\model\support\TicketModel;
use support\extend\Service;

/**
 * 工单 Service — tickets 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer tickets
 *
 * 状态机说明（05 §4 canonical Ticket，复制冻结）：
 *   submitted / in_progress / waiting_user / under_review / resolved / closed
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method TicketModel create($data)
 * @method TicketModel get($id, string $field = null)
 * @method TicketModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class TicketService extends Service
{
    public function __construct()
    {
        $this->dao = TicketDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询工单（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
