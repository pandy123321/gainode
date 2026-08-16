<?php

declare(strict_types=1);

namespace library\model\support;

use support\extend\Model;
use support\exception\RunException;

/**
 * ticket_attachments 表映射 — 工单附件（05 §3 TicketAttachment，append-only 值对象）
 *
 * 值对象：附件一经写入永不覆盖（无 updated_time、无 object_version），
 * 修订以新附件追加表达。禁止 UPDATE / DELETE。
 *
 * @property string $attachment_id 附件ID(Snowflake，主键)
 * @property string $ticket_id 工单ID(tickets.ticket_id)
 * @property string $ticket_message_id 工单消息ID(ticket_messages.message_id)
 * @property string $file_type 文件类型
 * @property string $file_url 文件URL
 * @property string $file_hash 文件哈希
 * @property string $uploaded_by 上传人 user_id
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 */
class TicketAttachmentModel extends Model
{
    public $table = 'ticket_attachments';
    public $primaryKey = 'attachment_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // append-only：无 updated_time
    public $timestamps = false;
    public const UPDATED_AT = null;

    public $delete_field = '';

    public $fields = [
        'attachment_id',
        'ticket_id',
        'ticket_message_id',
        'file_type',
        'file_url',
        'file_hash',
        'uploaded_by',
        'idempotency_key',
        'audit_event_id',
        'created_time',
    ];

    /**
     * 关联工单（同模块 FK）
     */
    public function ticket()
    {
        return $this->belongsTo(TicketModel::class, 'ticket_id', 'ticket_id');
    }

    /**
     * 关联工单消息（同模块 FK）
     */
    public function message()
    {
        return $this->belongsTo(TicketMessageModel::class, 'ticket_message_id', 'message_id');
    }

    /**
     * 注入 append-only Builder，封堵 ORM 正常路径的 destructive mutation。
     */
    public function newEloquentBuilder($query)
    {
        return new TicketAttachmentAppendOnlyBuilder($query);
    }

    /**
     * append-only 兜底：已落盘对象禁止 save（UPDATE）。
     *
     * @throws RunException
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new RunException('ticket_attachments 为 append-only 值对象：禁止 UPDATE');
        }
        return parent::save($options);
    }

    /**
     * append-only 兜底：禁止 delete。
     *
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 DELETE');
    }
}
