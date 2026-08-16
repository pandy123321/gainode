<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\ResultDao;
use library\model\prediction\ResultModel;
use support\extend\Service;

/**
 * 预测结果 Service — results 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer results
 *
 * 状态机说明（05 §4 V2.3 canonical）：
 *   provisional → official → disputed → corrected（corrected 仅一次）
 *   - Result official ≠ Settlement paid
 *   - Result confirmer ≠ Settlement approver（SoD）
 *
 * 本骨架不实现状态转移（属 Machine Contract 第二批 State Machine gate）。
 * 转移矩阵 FROZEN 前，任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method ResultModel create($data)
 * @method ResultModel get($id, string $field = null)
 * @method ResultModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class ResultService extends Service
{
    public function __construct()
    {
        $this->dao = ResultDao::class;
        parent::__construct();
    }

    /**
     * 按市场查询结果（只读透传）
     *
     * @param string $marketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByMarket(string $marketId)
    {
        return $this->getNewDao()->getByMarket($marketId);
    }
}
