<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\CorrectionCaseDao;
use library\model\prediction\CorrectionCaseModel;
use support\extend\Service;

/**
 * 纠错案件 Service — correction_cases 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer correction_cases
 *
 * 状态机说明（05 §4 V2.3 canonical，Owner 2B1-ENUM-03）：
 *   pending → approved → executing → completed / pending → rejected / executing → failed（可重试回 executing）
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method CorrectionCaseModel create($data)
 * @method CorrectionCaseModel get($id, string $field = null)
 * @method CorrectionCaseModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class CorrectionCaseService extends Service
{
    public function __construct()
    {
        $this->dao = CorrectionCaseDao::class;
        parent::__construct();
    }

    /**
     * 按市场查询纠错案件（只读透传）
     *
     * @param string $marketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByMarket(string $marketId)
    {
        return $this->getNewDao()->getByMarket($marketId);
    }
}
