<?php

declare(strict_types=1);

namespace library\service\prediction;

use library\dao\prediction\SettlementDao;
use library\model\prediction\SettlementModel;
use support\extend\Service;

/**
 * 结算单 Service — settlements 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer settlements
 *
 * 状态机说明（05 §4 V2.3 canonical）：
 *   queued → calculating → review → payable → paid（唯一"已结算"真值）
 *   旁路：failed（异常，可重试回 queued）
 *   - Result official ≠ Settlement paid；Result confirmer ≠ Settlement approver（SoD）
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method SettlementModel create($data)
 * @method SettlementModel get($id, string $field = null)
 * @method SettlementModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class SettlementService extends Service
{
    public function __construct()
    {
        $this->dao = SettlementDao::class;
        parent::__construct();
    }

    /**
     * 按市场查询结算单（只读透传）
     *
     * @param string $marketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByMarket(string $marketId)
    {
        return $this->getNewDao()->getByMarket($marketId);
    }
}
