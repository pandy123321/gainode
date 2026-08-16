<?php

declare(strict_types=1);

namespace library\model\risk;

use support\extend\Model;

/**
 * risk_cases 表映射 — 风控案件（05 §3 RiskCase + §4 V2.4）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B2-ENUM-03，冻结于 05 §4 V2.4，禁止自创）：
 *   open / investigating / under_review / resolved / closed
 *   - open=检测到风险；investigating=RISK_ANALYST 分析；
 *   - under_review=RISK_APPROVER 审批处置（RISK_ANALYST != RISK_APPROVER）；
 *   - resolved=处置措施已执行（申诉窗口内保持此态）；closed=案件归档终态。
 *   - appeal_eligible 为字段非状态。
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $case_id 案件ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $risk_type 风险类型
 * @property string $severity 严重等级
 * @property string $status 风控状态(05 §4 V2.4 canonical，5态)
 * @property int $detected_at 检测时间(Unix秒)
 * @property string $detected_by 检测人 user_id
 * @property string $reviewed_by 处置审批人 user_id
 * @property string $disposition 处置结论
 * @property string $disposition_reason_key 处置理由 I18N key
 * @property string|null $restrictions 限制措施(JSON 数组)
 * @property int $appeal_eligible 是否可申诉
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class RiskCaseModel extends Model
{
    // ---- 风控状态常量（05 §4 V2.4 canonical）----
    public const STATUS_OPEN = 'open';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_INVESTIGATING,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public $table = 'risk_cases';
    public $primaryKey = 'case_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'case_id',
        'user_id',
        'risk_type',
        'severity',
        'status',
        'detected_at',
        'detected_by',
        'reviewed_by',
        'disposition',
        'disposition_reason_key',
        'restrictions',
        'appeal_eligible',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
