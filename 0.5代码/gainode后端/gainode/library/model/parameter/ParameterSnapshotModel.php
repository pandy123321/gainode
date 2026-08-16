<?php

declare(strict_types=1);

namespace library\model\parameter;

use support\extend\Model;
use support\exception\RunException;

/**
 * parameter_snapshots 表映射 — 参数快照（05 §3 ParameterSnapshot，append-only 只读聚合）
 *
 * 只读聚合/Projection：快照一经写入永不覆盖（无 updated_time、无 object_version），
 * 参数演进由新 snapshot + version 表达。禁止 UPDATE / DELETE。
 *
 * @property string $snapshot_id 快照ID(Snowflake，主键)
 * @property string $release_id 发布ID(parameter_releases.release_id)
 * @property string|null $parameter_keys 参数键列表(JSON 数组)
 * @property string|null $parameter_values 参数值(JSON 键值对)
 * @property string $version 快照版本号
 * @property string $created_by 创建人 user_id
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 */
class ParameterSnapshotModel extends Model
{
    public $table = 'parameter_snapshots';
    public $primaryKey = 'snapshot_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // append-only：无 updated_time
    public $timestamps = false;
    public const UPDATED_AT = null;

    public $delete_field = '';

    public $fields = [
        'snapshot_id',
        'release_id',
        'parameter_keys',
        'parameter_values',
        'version',
        'created_by',
        'idempotency_key',
        'audit_event_id',
        'created_time',
    ];

    /**
     * 关联参数发布（同模块 FK）
     */
    public function release()
    {
        return $this->belongsTo(ParameterReleaseModel::class, 'release_id', 'release_id');
    }

    /**
     * 注入 append-only Builder，封堵 ORM 正常路径的 destructive mutation。
     */
    public function newEloquentBuilder($query)
    {
        return new ParameterSnapshotAppendOnlyBuilder($query);
    }

    /**
     * append-only 兜底：已落盘对象禁止 save（UPDATE）。
     *
     * @throws RunException
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new RunException('parameter_snapshots 为 append-only 只读聚合：禁止 UPDATE');
        }
        return parent::save($options);
    }

    /**
     * append-only 兜底：禁止 delete。
     *
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('parameter_snapshots 为 append-only 只读聚合：禁止 DELETE');
    }
}
