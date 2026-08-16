<?php

declare(strict_types=1);

namespace library\model\approval;

use support\extend\Model;

/**
 * approval_requests 表映射 — 审批请求（05 §3 ApprovalRequest + §4 Approval 状态机）
 *
 * 领域状态机（canonical enum，复制 05 §4 Approval，冻结，禁止自创）：
 *   draft / pending / changes_requested / approved / rejected / executing / executed / failed
 *   - 关键不变量：Approval 回滚不修改旧 Approval 状态，形成新执行对象 + 审计链
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $approval_id 审批ID(Snowflake，主键)
 * @property string $request_type 申请类型
 * @property string $request_object_type 申请对象类型
 * @property string $request_object_id 申请对象ID
 * @property string $status 审批状态(05 §4 canonical，8态)
 * @property string $submitted_by 申请人 user_id
 * @property string $submitter_role 申请人角色
 * @property string $assigned_to 审批人 user_id
 * @property string $decided_by 裁决人 user_id
 * @property int $decided_at 裁决时间(Unix秒)
 * @property string $reason_key 裁决理由 I18N key
 * @property string $changes_requested_reason 要求修改理由
 * @property string $execution_id 执行对象ID
 * @property string $case_id 关联案件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class ApprovalRequestModel extends Model
{
    // ---- 审批状态常量（05 §4 canonical）----
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CHANGES_REQUESTED = 'changes_requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXECUTING = 'executing';
    public const STATUS_EXECUTED = 'executed';
    public const STATUS_FAILED = 'failed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_CHANGES_REQUESTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_EXECUTING,
        self::STATUS_EXECUTED,
        self::STATUS_FAILED,
    ];

    public $table = 'approval_requests';
    public $primaryKey = 'approval_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'approval_id',
        'request_type',
        'request_object_type',
        'request_object_id',
        'status',
        'submitted_by',
        'submitter_role',
        'assigned_to',
        'decided_by',
        'decided_at',
        'reason_key',
        'changes_requested_reason',
        'execution_id',
        'case_id',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
