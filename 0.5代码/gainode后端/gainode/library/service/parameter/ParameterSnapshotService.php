<?php

declare(strict_types=1);

namespace library\service\parameter;

use library\dao\parameter\ParameterSnapshotDao;
use library\dict\ErrorDict;
use library\model\parameter\ParameterSnapshotModel;
use support\extend\Service;
use support\exception\DomainException;

/**
 * 参数快照 Service — parameter_snapshots 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer parameter_snapshots
 *
 * append-only 只读聚合（05 §3 ParameterSnapshot）：
 *   - 快照一经写入永不覆盖，参数演进由新 snapshot + version 表达。
 *   - 机械强制见 ParameterSnapshotModel / ParameterSnapshotAppendOnlyBuilder / ParameterSnapshotDao
 *     （无 updated_time、无 object_version、save/delete 抛 RunException）。
 *
 * 快照生成业务（PR6/PR7/PR10 的账本效果）依赖参数值内容（TBC），由参数冻结后由
 * ParameterReleaseService 附加；本 Service 仅提供 append-only 写入与只读投影。
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

    /** 按发布查询快照（只读透传） */
    public function getByRelease(string $releaseId)
    {
        return $this->getNewDao()->getByRelease($releaseId);
    }

    public function listByRelease(string $releaseId): array
    {
        $items = [];
        foreach ($this->getByRelease($releaseId) as $s) {
            $items[] = [
                'snapshot_id'   => (string) $s->snapshot_id,
                'version'       => (string) $s->version,
                'created_by'    => (string) $s->created_by,
                'created_time'  => (int) $s->getRawOriginal('created_time'),
            ];
        }
        return ['snapshots' => $items];
    }

    public function detail(string $snapshotId): array
    {
        $s = $this->get($snapshotId);
        if (empty($s)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'parameter snapshot not found');
        }
        return [
            'snapshot_id'      => (string) $s->snapshot_id,
            'release_id'       => (string) $s->release_id,
            'parameter_keys'   => (string) $s->parameter_keys,
            'parameter_values' => (string) $s->parameter_values,
            'version'          => (string) $s->version,
            'created_by'       => (string) $s->created_by,
            'created_time'     => (int) $s->getRawOriginal('created_time'),
        ];
    }
}
