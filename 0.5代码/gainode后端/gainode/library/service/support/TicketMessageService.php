<?php

declare(strict_types=1);

namespace library\service\support;

use library\dao\support\TicketMessageDao;
use library\dict\ErrorDict;
use library\model\support\TicketMessageModel;
use support\extend\Service;
use support\exception\DomainException;

/**
 * 工单消息 Service — ticket_messages 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer ticket_messages
 *
 * append-only 值对象（05 §3 TicketMessage）：
 *   - 消息一经写入永不覆盖（无 updated_time、无 object_version），修订以新消息追加表达。
 *   - 机械强制见 TicketMessageModel / TicketMessageAppendOnlyBuilder / TicketMessageDao
 *     （save/delete 抛 RunException）。
 *
 * 消息追加为纯数据库写入（无外部依赖），create 透传 append-only DAO；sender_role 为字段
 * （END_USER / SUPPORT_AGENT），写入即固化。
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

    /** 按工单查询消息（只读透传） */
    public function getByTicket(string $ticketId)
    {
        return $this->getNewDao()->getByTicket($ticketId);
    }

    public function listByTicket(string $ticketId): array
    {
        $items = [];
        foreach ($this->getByTicket($ticketId) as $m) {
            $items[] = [
                'message_id'   => (string) $m->message_id,
                'sender_role'  => (string) $m->sender_role,
                'body_key'     => (string) $m->body_key,
                'created_time' => (int) $m->getRawOriginal('created_time'),
            ];
        }
        return ['messages' => $items];
    }

    public function detail(string $messageId): array
    {
        $m = $this->get($messageId);
        if (empty($m)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'ticket message not found');
        }
        return [
            'message_id'   => (string) $m->message_id,
            'ticket_id'    => (string) $m->ticket_id,
            'sender_role'  => (string) $m->sender_role,
            'body_key'     => (string) $m->body_key,
            'attachments'  => (string) $m->attachments,
            'created_time' => (int) $m->getRawOriginal('created_time'),
        ];
    }
}
