<?php

declare(strict_types=1);

namespace library\service\power;

use library\dao\power\PowerPositionDao;
use library\model\power\PowerPositionModel;
use support\extend\Service;

/**
 * Power 持仓 Service — power_positions 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer power_positions
 *
 * Power 为可消耗、可恢复操作资源，无领域状态机（scalar fields）。
 * Power 精确消耗/恢复规则由 Active Rule/Parameter 决定，生产参数未批准（TBC）。
 *
 * 本骨架不实现任何 Power 变更（consume/recover/convert）逻辑。规则 FROZEN 前，
 * 任何 Power 变更操作 MUST FAIL_CLOSED。
 *
 * @method PowerPositionModel create($data)
 * @method PowerPositionModel get($id, string $field = null)
 * @method PowerPositionModel find($id)
 * @method PowerPositionModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class PowerPositionService extends Service
{
    public function __construct()
    {
        $this->dao = PowerPositionDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询持仓（只读透传）
     *
     * @param string $userId
     * @return PowerPositionModel|null
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
