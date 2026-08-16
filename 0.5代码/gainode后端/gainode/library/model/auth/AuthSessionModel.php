<?php

declare(strict_types=1);

namespace library\model\auth;

use support\extend\Model;

/**
 * auth_sessions 表映射 — 会话（05 §3 AuthSession + §2.2 Session 状态机）
 *
 * 领域状态机（canonical enum，复制 05 §2.2 Session，冻结，禁止自创）：
 *   active / mfa_required / restricted / expired / revoked
 *   - mfa_required：登录已通过但 MFA 未验证，仅限 MFA 相关接口
 *   - restricted：风控限制；expired：到期；revoked：登出/安全吊销
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $session_id 会话ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $token_hash token 哈希
 * @property string $status 会话状态(05 §2.2 canonical，5态)
 * @property string|null $device_info 设备信息(JSON)
 * @property string $ip_address IP 地址
 * @property int $mfa_verified MFA 是否已验证
 * @property int $expires_at 过期时间(Unix秒)
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class AuthSessionModel extends Model
{
    // ---- 会话状态常量（05 §2.2 canonical）----
    public const STATUS_ACTIVE = 'active';
    public const STATUS_MFA_REQUIRED = 'mfa_required';
    public const STATUS_RESTRICTED = 'restricted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_MFA_REQUIRED,
        self::STATUS_RESTRICTED,
        self::STATUS_EXPIRED,
        self::STATUS_REVOKED,
    ];

    public $table = 'auth_sessions';
    public $primaryKey = 'session_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'session_id',
        'user_id',
        'token_hash',
        'status',
        'device_info',
        'ip_address',
        'mfa_verified',
        'expires_at',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
