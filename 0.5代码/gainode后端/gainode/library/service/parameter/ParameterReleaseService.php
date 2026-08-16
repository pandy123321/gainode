<?php

declare(strict_types=1);

namespace library\service\parameter;

use library\dao\parameter\ParameterReleaseDao;
use library\model\parameter\ParameterReleaseModel;
use support\extend\Service;

/**
 * 参数发布 Service — parameter_releases 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer parameter_releases
 *
 * 状态机说明（05 §4 canonical Parameter Release，复制冻结）：
 *   draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived
 *   - 关键不变量：approved != active（批准后可排期延迟生效）；历史对象使用 snapshot
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method ParameterReleaseModel create($data)
 * @method ParameterReleaseModel get($id, string $field = null)
 * @method ParameterReleaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ParameterReleaseService extends Service
{
    public function __construct()
    {
        $this->dao = ParameterReleaseDao::class;
        parent::__construct();
    }
}
