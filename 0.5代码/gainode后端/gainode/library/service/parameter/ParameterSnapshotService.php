<?php

declare(strict_types=1);

namespace library\service\parameter;

use library\dao\parameter\ParameterSnapshotDao;
use library\model\parameter\ParameterSnapshotModel;
use support\extend\Service;

/**
 * 参数快照 Service — parameter_snapshots 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer parameter_snapshots
 *
 * append-only 只读聚合（05 §3 ParameterSnapshot）：
 *   - 快照一经写入永不覆盖，参数演进由新 snapshot + version 表达。
 *   - 机械强制见 ParameterSnapshotModel / ParameterSnapshotAppendOnlyBuilder / ParameterSnapshotDao。
 *
 * 本骨架不实现快照生成业务（属 State Machine gate）。生成/写入流转 FROZEN 前，
 * 任何写入必须符合 append-only 约束，不得自创覆盖/删除路径。
 *
 * @method ParameterSnapshotModel create($data)
 * @method ParameterSnapshotModel get($id, string $field = null)
 * @method ParameterSnapshotModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ParameterSnapshotService extends Service
{
    public function __construct()
    {
        $this->dao = ParameterSnapshotDao::class;
        parent::__construct();
    }

    /**
     * 按发布查询快照（只读透传）
     *
     * @param string $releaseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRelease(string $releaseId)
    {
        return $this->getNewDao()->getByRelease($releaseId);
    }
}
