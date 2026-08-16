<?php

declare(strict_types=1);

namespace library\model\auth;

use support\extend\Model;

/**
 * mfa_enrollments 表映射 — MFA 注册（05 §3 MfaEnrollment + §4 V2.4）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B2-ENUM-02，冻结于 05 §4 V2.4，禁止自创）：
 *   pending / active / revoked
 *   - pending=已发起注册、尚未验证（enrolled_at 与 last_verified_at 分离表达）；
 *   - active=已验证生效；revoked=用户移除/安全吊销。
 *   - backup_codes_active 为字段非状态。
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $enrollment_id 注册ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $method_type MFA 方法类型
 * @property string $status 注册状态(05 §4 V2.4 canonical，3态)
 * @property int $enrolled_at 注册发起时间(Unix秒)
 * @property int $last_verified_at 最后验证时间(Unix秒)
 * @property int $backup_codes_active 备份码是否激活
 * @property string|null $device_info 设备信息(JSON)
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class MfaEnrollmentModel extends Model
{
    // ---- 注册状态常量（05 §4 V2.4 canonical）----
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_REVOKED,
    ];

    public $table = 'mfa_enrollments';
    public $primaryKey = 'enrollment_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'enrollment_id',
        'user_id',
        'method_type',
        'status',
        'enrolled_at',
        'last_verified_at',
        'backup_codes_active',
        'device_info',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
