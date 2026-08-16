<?php

declare(strict_types=1);

namespace library\model\notice;

use support\extend\Model;

/**
 * notification_deliveries 表映射 — 通知投递（05 §3 NotificationDelivery + §4 V2.4）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B2-ENUM-01，冻结于 05 §4 V2.4，禁止自创）：
 *   pending / delivered / failed / cancelled
 *   - pending=待投递；delivered=成功；failed=失败待重试（attempt_count/next_retry_at 驱动，不新增 processing 态）；
 *   - cancelled=业务对象失效/用户已读不再投递。投递失败不回滚业务（05 §4 Notice 设计原则 1）。
 *
 * 幂等：dedupe_key（去重 key），不额外设 idempotency_key。
 * 本骨架不实现状态转移，任何流转 MUST FAIL_CLOSED。
 *
 * @property string $delivery_id 投递ID(Snowflake，主键)
 * @property string $notice_id 通知ID(notices.notice_id)
 * @property string $channel 投递渠道(PUSH/EMAIL/SMS/IN_APP)
 * @property string $delivery_status 投递状态(05 §4 V2.4 canonical，4态)
 * @property string $dedupe_key 去重 key(幂等)
 * @property int $attempt_count 尝试次数
 * @property int $last_attempt_at 最后尝试时间(Unix秒)
 * @property int $next_retry_at 下次重试时间(Unix秒)
 * @property int $delivered_at 投递成功时间(Unix秒)
 * @property string $failure_reason_code 失败原因码
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class NotificationDeliveryModel extends Model
{
    // ---- 投递状态常量（05 §4 V2.4 canonical）----
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DELIVERED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    // ---- 投递渠道常量（05 §3 NotificationDelivery）----
    public const CHANNEL_PUSH = 'PUSH';
    public const CHANNEL_EMAIL = 'EMAIL';
    public const CHANNEL_SMS = 'SMS';
    public const CHANNEL_IN_APP = 'IN_APP';

    /** @var string[] 冻结的渠道全集 */
    public const CHANNELS = [
        self::CHANNEL_PUSH,
        self::CHANNEL_EMAIL,
        self::CHANNEL_SMS,
        self::CHANNEL_IN_APP,
    ];

    public $table = 'notification_deliveries';
    public $primaryKey = 'delivery_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'delivery_id',
        'notice_id',
        'channel',
        'delivery_status',
        'dedupe_key',
        'attempt_count',
        'last_attempt_at',
        'next_retry_at',
        'delivered_at',
        'failure_reason_code',
        'object_version',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];

    /**
     * 关联通知（同模块 FK）
     */
    public function notice()
    {
        return $this->belongsTo(NoticeModel::class, 'notice_id', 'notice_id');
    }
}
