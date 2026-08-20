<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\support\TicketMessageDao;
use support\extend\Service;

/**
 * Admin V2 工单消息列表 DTO 服务（A-SUPPORT-002 消息）。
 *
 * 只读分页：ticket_messages（append-only 值对象）按 ticket 筛选。
 * 字段口径：仅返回已确认列；时间为 UTC。
 * 供 Admin 2.0 工单详情页经 /api/v1/admin/support/tickets/{id}/messages 对接。
 */
class AdminTicketMessageDtoService extends Service
{
    public function __construct()
    {
        $this->dao = TicketMessageDao::class;
        parent::__construct();
    }

    /**
     * 分页工单消息 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $ticketId 工单 ID 筛选
     * @return array{messages:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $ticketId = ''): array
    {
        $params = [];
        if ($ticketId !== '') {
            $params['ticket_id'] = $ticketId;
        }
        $paginator = (new TicketMessageDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['message_id', 'ticket_id', 'sender_role', 'body_key', 'attachments', 'audit_event_id', 'created_time']
        );

        $messages = [];
        foreach ($paginator->items() as $m) {
            $messages[] = [
                'message_id'      => (string) $m->message_id,
                'ticket_id'       => (string) $m->ticket_id,
                'sender_role'     => (string) $m->sender_role,
                'body_key'        => (string) $m->body_key,
                'attachments'     => $m->attachments !== null ? (string) $m->attachments : null,
                'audit_event_id'  => (string) $m->audit_event_id,
                'created_time'    => (int) $m->getRawOriginal('created_time'),
            ];
        }

        return [
            'messages' => $messages,
            'total'    => (int) $paginator->total(),
            'page'     => $page,
            'size'     => $size,
        ];
    }
}
