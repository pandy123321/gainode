<?php

declare(strict_types=1);

namespace library\service\support;

use library\dao\support\TicketAttachmentDao;
use library\model\support\TicketAttachmentModel;
use support\extend\Service;

/**
 * 工单附件 Service — ticket_attachments 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer ticket_attachments
 *
 * append-only 值对象（05 §3 TicketAttachment）：
 *   - 附件一经写入永不覆盖，修订以新附件追加表达。
 *   - 机械强制见 TicketAttachmentModel / TicketAttachmentAppendOnlyBuilder / TicketAttachmentDao。
 *
 * 本骨架不实现附件上传业务（属 State Machine gate）。写入流转 FROZEN 前，
 * 任何写入必须符合 append-only 约束，不得自创覆盖/删除路径。
 *
 * @method TicketAttachmentModel create($data)
 * @method TicketAttachmentModel get($id, string $field = null)
 * @method TicketAttachmentModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class TicketAttachmentService extends Service
{
    public function __construct()
    {
        $this->dao = TicketAttachmentDao::class;
        parent::__construct();
    }

    /**
     * 按工单查询附件（只读透传）
     *
     * @param string $ticketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByTicket(string $ticketId)
    {
        return $this->getNewDao()->getByTicket($ticketId);
    }
}
