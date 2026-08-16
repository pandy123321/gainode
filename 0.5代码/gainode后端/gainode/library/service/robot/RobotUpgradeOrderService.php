<?php

declare(strict_types=1);

namespace library\service\robot;

use library\dao\robot\RobotUpgradeOrderDao;
use library\model\robot\RobotUpgradeOrderModel;
use support\extend\Service;

/**
 * Robot 升级订单 Service — robot_upgrade_orders 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer robot_upgrade_orders
 *
 * 状态机说明（05 §4 V2.3 canonical，Owner 2B1-ENUM-05）：
 *   pending → processing → completed / pending → cancelled / processing → failed（可重试回 processing）
 *   - 大额人工确认 = OPS_OPERATOR + RISK_APPROVER（MC2 Owner 裁决 #13）
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method RobotUpgradeOrderModel create($data)
 * @method RobotUpgradeOrderModel get($id, string $field = null)
 * @method RobotUpgradeOrderModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RobotUpgradeOrderService extends Service
{
    public function __construct()
    {
        $this->dao = RobotUpgradeOrderDao::class;
        parent::__construct();
    }

    /**
     * 按 Robot 查询升级订单（只读透传）
     *
     * @param string $robotId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRobot(string $robotId)
    {
        return $this->getNewDao()->getByRobot($robotId);
    }
}
