<?php

declare(strict_types=1);

namespace library\model\support;

use support\extend\Model;
use support\exception\RunException;

/**
 * ticket_messages 表映射 — 工单消息（05 §3 TicketMessage，append-only 值对象）
 *
 * 值对象：消息一经写入永不覆盖（无 updated_time、无 object_version），
 * 修订以新消息追加表达。禁止 UPDATE / DELETE。
 *
 * @property string $message_id 消息ID(Snowflake，主键)
 * @property string $ticket_id 工单ID(tickets.ticket_id)
 * @property string $sender_role 发送方角色
 * @property string $body_key 正文 I18N key
 * @property string|null $attachments 附件列表(JSON 数组)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 */
class TicketMessageModel extends Model
{
    public $table = 'ticket_messages';
    public $primaryKey = 'message_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // append-only：无 updated_time
    public $timestamps = false;
    public const UPDATED_AT = null;

    public $delete_field = '';

    public $fields = [
        'message_id',
        'ticket_id',
        'sender_role',
        'body_key',
        'attachments',
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
     * 注入 append-only Builder，封堵 ORM 正常路径的 destructive mutation。
     */
    public function newEloquentBuilder($query)
    {
        return new TicketMessageAppendOnlyBuilder($query);
    }

    /**
     * append-only 兜底：已落盘对象禁止 save（UPDATE）。
     *
     * @throws RunException
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new RunException('ticket_messages 为 append-only 值对象：禁止 UPDATE');
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
        throw new RunException('ticket_messages 为 append-only 值对象：禁止 DELETE');
    }
}
