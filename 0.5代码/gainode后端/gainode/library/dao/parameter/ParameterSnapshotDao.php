<?php

declare(strict_types=1);

namespace library\dao\parameter;

use support\extend\Dao;
use support\exception\RunException;
use library\model\parameter\ParameterSnapshotModel;

/**
 * ParameterSnapshot DAO — parameter_snapshots 表查询封装（append-only）
 *
 * 注意：append-only 只读聚合禁止物理删除/覆盖。本 DAO 对继承的 delete/deleteAll/update/
 * updateAll/updateOrCreate 全部 fail-closed 覆写，从代码层面机械阻断 DAO 层的删除/覆盖路径。
 * 仅保留只读查询与追加（create/insert）。
 */
class ParameterSnapshotDao extends Dao
{
    public function __construct()
    {
        $this->model = ParameterSnapshotModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return ParameterSnapshotModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按发布查询快照
     *
     * @param string $releaseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRelease(string $releaseId)
    {
        return $this->fetchAll(['release_id' => $releaseId]);
    }

    /**
     * append-only 只读聚合：禁止删除单条。
     *
     * @throws RunException
     */
    public function delete($id, bool $force = false)
    {
        throw new RunException('parameter_snapshots 为 append-only 只读聚合：禁止删除快照');
    }

    /**
     * append-only 只读聚合：禁止批量删除。
     *
     * @throws RunException
     */
    public function deleteAll(array $params, bool $force = false)
    {
        throw new RunException('parameter_snapshots 为 append-only 只读聚合：禁止批量删除快照');
    }

    /**
     * append-only 只读聚合：禁止 UPDATE。
     *
     * @throws RunException
     */
    public function update($id, array $data)
    {
        throw new RunException('parameter_snapshots 为 append-only 只读聚合：禁止 UPDATE 快照');
    }

    /**
     * append-only 只读聚合：禁止批量 UPDATE。
     *
     * @throws RunException
     */
    public function updateAll(array $params, array $data)
    {
        throw new RunException('parameter_snapshots 为 append-only 只读聚合：禁止批量 UPDATE 快照');
    }

    /**
     * append-only 只读聚合：禁止 updateOrCreate。
     *
     * @throws RunException
     */
    public function updateOrCreate(array $params, array $data)
    {
        throw new RunException('parameter_snapshots 为 append-only 只读聚合：禁止 updateOrCreate');
    }
}
