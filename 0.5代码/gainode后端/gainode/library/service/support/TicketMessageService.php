<?php

declare(strict_types=1);

namespace library\service\support;

use library\dao\support\TicketMessageDao;
use library\model\support\TicketMessageModel;
use support\extend\Service;

/**
 * 工单消息 Service — ticket_messages 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer ticket_messages
 *
 * append-only 值对象（05 §3 TicketMessage）：
 *   - 消息一经写入永不覆盖，修订以新消息追加表达。
 *   - 机械强制见 TicketMessageModel / TicketMessageAppendOnlyBuilder / TicketMessageDao。
 *
 * 本骨架不实现消息追加业务（属 State Machine gate）。写入流转 FROZEN 前，
 * 任何写入必须符合 append-only 约束，不得自创覆盖/删除路径。
 *
 * @method TicketMessageModel create($data)
 * @method TicketMessageModel get($id, string $field = null)
 * @method TicketMessageModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class TicketMessageService extends Service
{
    public function __construct()
    {
        $this->dao = TicketMessageDao::class;
        parent::__construct();
    }

    /**
     * 按工单查询消息（只读透传）
     *
     * @param string $ticketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByTicket(string $ticketId)
    {
        return $this->getNewDao()->getByTicket($ticketId);
    }
}
