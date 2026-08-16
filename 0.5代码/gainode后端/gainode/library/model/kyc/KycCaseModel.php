<?php

declare(strict_types=1);

namespace library\model\kyc;

use support\extend\Model;

/**
 * kyc_cases 表映射 — KYC 案件（05 §3 KycCase + §4 KYC 状态机）
 *
 * 领域状态机（canonical enum，复制 05 §4 KYC，冻结，禁止自创）：
 *   not_started / pending / needs_info / approved / rejected / review
 *   - needs_info：需补充材料；review：需人工复核；approved/rejected 为终态
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $case_id 案件ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $kyc_level KYC 等级
 * @property string $status KYC 状态(05 §4 canonical，6态)
 * @property int $submitted_at 提交时间(Unix秒)
 * @property int $reviewed_at 复核时间(Unix秒)
 * @property string $reviewed_by 复核人 user_id
 * @property string $reason_code 原因码
 * @property string $reason_text_key 原因文案 I18N key
 * @property string $next_action 下一步动作
 * @property string $policy_version 策略版本号
 * @property string $rule_version 规则版本号
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class KycCaseModel extends Model
{
    // ---- KYC 状态常量（05 §4 canonical）----
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_PENDING = 'pending';
    public const STATUS_NEEDS_INFO = 'needs_info';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REVIEW = 'review';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_PENDING,
        self::STATUS_NEEDS_INFO,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_REVIEW,
    ];

    public $table = 'kyc_cases';
    public $primaryKey = 'case_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'case_id',
        'user_id',
        'kyc_level',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'reason_code',
        'reason_text_key',
        'next_action',
        'policy_version',
        'rule_version',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
