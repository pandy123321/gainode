<?php

declare(strict_types=1);

namespace library\model\parameter;

use support\extend\Model;

/**
 * parameter_releases 表映射 — 参数发布（05 §3 ParameterRelease + §4 Parameter Release 状态机）
 *
 * 领域状态机（canonical enum，复制 05 §4 Parameter Release，冻结，禁止自创）：
 *   draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived
 *   - 关键不变量：approved ≠ active（批准后可排期延迟生效）；历史对象使用 snapshot
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $release_id 发布ID(Snowflake，主键)
 * @property string|null $parameter_keys 参数键列表(JSON 数组)
 * @property string $status 发布状态(05 §4 canonical，8态)
 * @property string $draft_version 草稿版本号
 * @property string $approved_by 批准人 user_id
 * @property int $scheduled_at 排期激活时间(Unix秒)
 * @property int $activated_at 实际激活时间(Unix秒)
 * @property int $paused_at 暂停时间(Unix秒)
 * @property int $rolled_back_at 回滚时间(Unix秒)
 * @property int $archived_at 归档时间(Unix秒)
 * @property string $monitoring_job_id 监控任务ID
 * @property string $snapshot_id 关联参数快照ID
 * @property string $case_id 关联案件ID
 * @property string|null $audit_event_ids 审计事件ID列表(JSON 数组)
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class ParameterReleaseModel extends Model
{
    // ---- 发布状态常量（05 §4 canonical）----
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ROLLED_BACK = 'rolled_back';
    public const STATUS_ARCHIVED = 'archived';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_SCHEDULED,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_ROLLED_BACK,
        self::STATUS_ARCHIVED,
    ];

    public $table = 'parameter_releases';
    public $primaryKey = 'release_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'release_id',
        'parameter_keys',
        'status',
        'draft_version',
        'approved_by',
        'scheduled_at',
        'activated_at',
        'paused_at',
        'rolled_back_at',
        'archived_at',
        'monitoring_job_id',
        'snapshot_id',
        'case_id',
        'audit_event_ids',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];

    /**
     * 关联参数快照（同模块 FK）
     */
    public function snapshot()
    {
        return $this->belongsTo(ParameterSnapshotModel::class, 'snapshot_id', 'snapshot_id');
    }
}
