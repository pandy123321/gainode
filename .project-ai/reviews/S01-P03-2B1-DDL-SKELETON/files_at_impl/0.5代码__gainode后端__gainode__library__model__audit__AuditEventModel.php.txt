<?php

declare(strict_types=1);

namespace library\model\audit;

use support\extend\Model;
use support\exception\RunException;

/**
 * audit_events 表映射 — 审计事件（05 §3 AuditLog，append-only，复用 MC2 DDL）
 *
 * 本表复用 MC2 `20260815_machine_contract_batch2_audit_events.sql`，本 Model 仅映射该表，
 * 不新增字段、不修改 append-only 约束。
 *
 * append-only 约束（MC2 Freeze §6）：
 *   - 一事件一行，顺序可重建；无 UPDATE/DELETE。
 *   - 本表无 updated_time 列；$timestamps=false，且 UPDATED_AT=null 以杜绝任何 ORM/Dao 误写。
 *   - before/after_snapshot 采用 snapshot_type + snapshot_id 类型化引用（IR 629 P1-6）。
 *
 * 机械强制（fail-closed，代码级，非仅注释约定）：
 *   - save() 在已落盘实例（$this->exists）上直接抛 RunException，杜绝实例级 UPDATE 覆盖。
 *   - delete() 直接抛 RunException，杜绝实例级物理删除。
 *   - newEloquentBuilder() 注入 AuditEventAppendOnlyBuilder，阻断 Eloquent Builder 层
 *     destructive mutation，并经其 __call() 兜底阻断 Query Builder 层转发。
 *   - 配合 AuditEventDao 对 delete/deleteAll/update/updateAll/updateOrCreate 的覆写。
 *
 * Protection boundary：覆盖「ORM 正常路径」（Model 实例 + Eloquent Builder + DAO）；
 * 底层 Query Builder / DB::table / PDO raw SQL 属数据库直连层，需 DB 级硬约束另走 Change Request。
 *
 * @property string $audit_event_id 审计事件ID(Snowflake，主键)
 * @property string $event_code 事件码(对齐 Event Catalog)
 * @property string $actor_id 操作者ID(user_id 或系统=0)
 * @property string $actor_role 操作者角色(05 §8 RBAC)
 * @property string $target_object_type 目标对象类型(如 apt_ledger_entries)
 * @property string $target_object_id 目标对象ID
 * @property string $before_snapshot_type 变更前快照类型(typed reference)
 * @property string $before_snapshot_id 变更前快照ID
 * @property string $after_snapshot_type 变更后快照类型(typed reference)
 * @property string $after_snapshot_id 变更后快照ID
 * @property string $outcome 结果(SUCCESS/FAILED/REJECTED)
 * @property string $reason_code 原因码
 * @property string $request_id 请求ID
 * @property string $approval_id 关联审批ID
 * @property string $case_id 关联案件ID
 * @property int $created_time 创建时间(Unix秒)
 */
class AuditEventModel extends Model
{
    // ---- 结果常量（append-only 审计事件 outcome）----
    public const OUTCOME_SUCCESS = 'SUCCESS';
    public const OUTCOME_FAILED = 'FAILED';
    public const OUTCOME_REJECTED = 'REJECTED';

    public $table = 'audit_events';
    public $primaryKey = 'audit_event_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // append-only：无 updated_time 列，禁止任何自动时间戳写入
    public $timestamps = false;

    // 禁止 Dao/ORM 写入 updated_time（本表不存在该列）
    public const UPDATED_AT = null;

    public $delete_field = '';

    public $fields = [
        'audit_event_id',
        'event_code',
        'actor_id',
        'actor_role',
        'target_object_type',
        'target_object_id',
        'before_snapshot_type',
        'before_snapshot_id',
        'after_snapshot_type',
        'after_snapshot_id',
        'outcome',
        'reason_code',
        'request_id',
        'approval_id',
        'case_id',
        'created_time',
    ];

    /**
     * 注入 append-only Eloquent Builder，阻断 Query Builder 层 destructive mutation。
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return AuditEventAppendOnlyBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new AuditEventAppendOnlyBuilder($query);
    }

    /**
     * append-only 审计事件：禁止 UPDATE。
     *
     * @throws RunException
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new RunException(
                'audit_events 为 append-only 审计事件：禁止 UPDATE 已落盘事件'
            );
        }
        return parent::save($options);
    }

    /**
     * append-only 审计事件：禁止物理删除。
     *
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止物理删除事件');
    }
}
