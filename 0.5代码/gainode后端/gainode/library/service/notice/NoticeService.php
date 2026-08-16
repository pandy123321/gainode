<?php

declare(strict_types=1);

namespace library\service\notice;

use library\dao\notice\NoticeDao;
use library\model\notice\NoticeModel;
use support\extend\Service;

/**
 * 通知 Service — notices 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer notices
 *
 * read_state 说明（05 §3 Notice）：
 *   unread / read（已读状态为通知可变字段）
 *
 * 本骨架不实现已读流转（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method NoticeModel create($data)
 * @method NoticeModel get($id, string $field = null)
 * @method NoticeModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class NoticeService extends Service
{
    public function __construct()
    {
        $this->dao = NoticeDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询通知（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
