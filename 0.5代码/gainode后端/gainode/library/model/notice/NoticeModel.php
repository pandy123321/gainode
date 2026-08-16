<?php

declare(strict_types=1);

namespace library\model\notice;

use support\extend\Model;

/**
 * notices 表映射 — 通知（05 §3 Notice，只读聚合，read_state 可变）
 *
 * read_state（unread/read）为通知可变字段，已读状态流转在 State Machine gate 冻结后实现。
 * 本骨架不实现状态转移，任何流转 MUST FAIL_CLOSED。
 *
 * @property string $notice_id 通知ID(Snowflake，主键)
 * @property string $user_id 目标用户ID
 * @property string $notice_type 通知事件类型
 * @property string $title_key I18N 标题 key
 * @property string $body_key I18N 正文 key
 * @property string $priority 优先级(INFO/WARNING/CRITICAL)
 * @property string $related_object_type 关联对象类型
 * @property string $related_object_id 关联对象ID
 * @property string $read_state 已读状态(unread/read)
 * @property string $content_version 文案版本号
 * @property string $locale 生成时 locale
 * @property int $expires_at 过期时间(Unix秒)
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class NoticeModel extends Model
{
    // ---- 优先级常量（05 §3 Notice）----
    public const PRIORITY_INFO = 'INFO';
    public const PRIORITY_WARNING = 'WARNING';
    public const PRIORITY_CRITICAL = 'CRITICAL';

    /** @var string[] 冻结的优先级全集 */
    public const PRIORITIES = [
        self::PRIORITY_INFO,
        self::PRIORITY_WARNING,
        self::PRIORITY_CRITICAL,
    ];

    // ---- 已读状态常量（05 §3 Notice）----
    public const READ_UNREAD = 'unread';
    public const READ_READ = 'read';

    /** @var string[] 冻结的已读状态全集 */
    public const READ_STATES = [
        self::READ_UNREAD,
        self::READ_READ,
    ];

    public $table = 'notices';
    public $primaryKey = 'notice_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'notice_id',
        'user_id',
        'notice_type',
        'title_key',
        'body_key',
        'priority',
        'related_object_type',
        'related_object_id',
        'read_state',
        'content_version',
        'locale',
        'expires_at',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
