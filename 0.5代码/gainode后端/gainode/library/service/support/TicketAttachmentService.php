<?php

declare(strict_types=1);

namespace library\service\support;

use library\dao\support\TicketAttachmentDao;
use library\dict\ErrorDict;
use library\model\support\TicketAttachmentModel;
use support\extend\Service;
use support\exception\DomainException;

/**
 * 工单附件 Service — ticket_attachments 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer ticket_attachments
 *
 * append-only 值对象（05 §3 TicketAttachment）：
 *   - 附件一经写入永不覆盖（无 updated_time、无 object_version），修订以新附件追加表达。
 *   - 机械强制见 TicketAttachmentModel / TicketAttachmentAppendOnlyBuilder / TicketAttachmentDao
 *     （save/delete 抛 RunException）。
 *
 * 实现策略（fail-closed，与 S02-P06 一致）：
 *   - 附件上传（create）依赖对象存储 presigned URL + 病毒扫描 + 文件类型/大小限制（全部 TBC）
 *     → FAIL_CLOSED。
 *   - 只读投影（getByTicket/detail/listByTicket）直接透传 append-only DAO。
 *
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
     * 上传附件。依赖对象存储 presigned URL + 病毒扫描 + 类型/大小限制（TBC）→ FAIL_CLOSED。
     *
     * @throws DomainException
     */
    public function create(array $data)
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'TicketAttachment upload depends on object storage presigned URL + virus scan + type/size limits (TBC) — not frozen'
        );
    }

    /** 按工单查询附件（只读透传） */
    public function getByTicket(string $ticketId)
    {
        return $this->getNewDao()->getByTicket($ticketId);
    }

    public function listByTicket(string $ticketId): array
    {
        $items = [];
        foreach ($this->getByTicket($ticketId) as $a) {
            $items[] = [
                'attachment_id' => (string) $a->attachment_id,
                'file_type'     => (string) $a->file_type,
                'uploaded_by'   => (string) $a->uploaded_by,
                'created_time'  => (int) $a->getRawOriginal('created_time'),
            ];
        }
        return ['attachments' => $items];
    }

    public function detail(string $attachmentId): array
    {
        $a = $this->get($attachmentId);
        if (empty($a)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'ticket attachment not found');
        }
        return [
            'attachment_id'    => (string) $a->attachment_id,
            'ticket_id'        => (string) $a->ticket_id,
            'ticket_message_id'=> (string) $a->ticket_message_id,
            'file_type'        => (string) $a->file_type,
            'file_url'         => (string) $a->file_url,
            'file_hash'        => (string) $a->file_hash,
            'uploaded_by'      => (string) $a->uploaded_by,
            'created_time'     => (int) $a->getRawOriginal('created_time'),
        ];
    }
}
