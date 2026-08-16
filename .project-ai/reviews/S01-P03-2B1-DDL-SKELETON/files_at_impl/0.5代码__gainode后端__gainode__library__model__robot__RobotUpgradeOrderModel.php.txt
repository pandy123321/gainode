<?php

declare(strict_types=1);

namespace library\model\robot;

use support\extend\Model;

/**
 * robot_upgrade_orders 表映射 — Robot 升级订单（05 §3 RobotUpgradeOrder + §4 V2.3）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B1-ENUM-05，冻结于 05 §4 V2.3，禁止自创）：
 *   pending / processing / completed / failed / cancelled
 *   - failed：执行失败，可重试回 processing
 *   - cancelled：取消（END_USER 主动取消）
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $upgrade_order_id 升级订单ID(Snowflake，主键)
 * @property string $robot_id Robot ID(robots.robot_id)
 * @property string $user_id 用户ID
 * @property int $from_level 原等级
 * @property int $to_level 目标等级
 * @property string $apt_cost 升级花费 APT
 * @property string $status 升级状态(05 §4 V2.3，5态)
 * @property string $power_cap_after 升级后 Power 上限
 * @property string|null $capacities_after 升级后能力列表(JSON)
 * @property int $cooling_end_at 冷却结束时间(Unix秒)
 * @property string $review_case_id 复核案件ID
 * @property string $approval_id 关联审批ID
 * @property string $ledger_entry_id 关联账本分录ID
 * @property string $rule_version 生效规则版本号
 * @property string $parameter_release_id 参数发布版本ID
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class RobotUpgradeOrderModel extends Model
{
    // ---- 升级状态常量（05 §4 V2.3 canonical）----
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    public $table = 'robot_upgrade_orders';
    public $primaryKey = 'upgrade_order_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'upgrade_order_id',
        'robot_id',
        'user_id',
        'from_level',
        'to_level',
        'apt_cost',
        'status',
        'power_cap_after',
        'capacities_after',
        'cooling_end_at',
        'review_case_id',
        'approval_id',
        'ledger_entry_id',
        'rule_version',
        'parameter_release_id',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];

    /**
     * Robot 归属（同模块 FK）
     */
    public function robot()
    {
        return $this->belongsTo(RobotModel::class, 'robot_id', 'robot_id');
    }
}
