<?php

declare(strict_types=1);

namespace library\model\robot;

use support\extend\Model;

/**
 * robots 表映射 — Robot 56 级 AI 代理（05 §3 Robot + §4 Robot 状态机）
 *
 * 领域状态机（canonical enum，冻结于 MC1 Canonical State Freeze，禁止自创）：
 *   inactive / active / cooling / review / restricted / paused
 *
 * @property string $robot_id Robot ID(Snowflake，主键)
 * @property string $user_id 用户ID(引用 member_user.id)
 * @property int $level Robot 等级(1-56)
 * @property string $status Robot 状态(05 §4 canonical)
 * @property string $standard_capacity 等级对应分配容量(权重，APT 数量维度)
 * @property string|null $capabilities 能力列表(JSON 数组，服务端下发)
 * @property string|null $allowed_actions 允许动作列表(JSON 数组，服务端下发，前端只读)
 * @property string|null $idempotency_key 幂等键(创建去重)
 * @property string $rule_version 生效规则版本号
 * @property string $parameter_release_id 参数发布版本ID
 * @property string $snapshot_id 关联参数快照ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class RobotModel extends Model
{
    // ---- 领域状态常量（05 §4 canonical，与 MC1 冻结一致）----
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COOLING = 'cooling';
    public const STATUS_REVIEW = 'review';
    public const STATUS_RESTRICTED = 'restricted';
    public const STATUS_PAUSED = 'paused';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_INACTIVE,
        self::STATUS_ACTIVE,
        self::STATUS_COOLING,
        self::STATUS_REVIEW,
        self::STATUS_RESTRICTED,
        self::STATUS_PAUSED,
    ];

    public $table = 'robots';
    public $primaryKey = 'robot_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // V2.0 核心实体以 ENUM 冻结领域状态，无 V1.x 的 status 软删字段
    public $delete_field = '';

    public $fields = [
        'robot_id',
        'user_id',
        'level',
        'status',
        'standard_capacity',
        'capabilities',
        'allowed_actions',
        'idempotency_key',
        'rule_version',
        'parameter_release_id',
        'snapshot_id',
        'object_version',
        'created_time',
        'updated_time',
    ];
}
