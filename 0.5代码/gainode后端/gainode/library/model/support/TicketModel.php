<?php

declare(strict_types=1);

namespace library\model\support;

use support\extend\Model;

/**
 * tickets 表映射 — 工单（05 §3 Ticket + §4 Ticket 状态机）
 *
 * 领域状态机（canonical enum，复制 05 §4 Ticket，冻结，禁止自创）：
 *   submitted / in_progress / waiting_user / under_review / resolved / closed
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $ticket_id 工单ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $category 工单分类
 * @property string $status 工单状态(05 §4 canonical，6态)
 * @property string $assigned_to 处理人 user_id
 * @property int $last_activity_at 最后活动时间(Unix秒)
 * @property string $resolution_type 解决类型
 * @property string $resolution_summary_key 解决摘要 I18N key
 * @property int $appeal_eligible 是否可申诉
 * @property string|null $ticket_message_ids 工单消息ID列表(JSON 数组)
 * @property string $case_id 关联案件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class TicketModel extends Model
{
    // ---- 工单状态常量（05 §4 canonical）----
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_USER = 'waiting_user';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_SUBMITTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_WAITING_USER,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public $table = 'tickets';
    public $primaryKey = 'ticket_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'ticket_id',
        'user_id',
        'category',
        'status',
        'assigned_to',
        'last_activity_at',
        'resolution_type',
        'resolution_summary_key',
        'appeal_eligible',
        'ticket_message_ids',
        'case_id',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
