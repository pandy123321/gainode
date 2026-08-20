<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\approval\ApprovalRequestDao;
use library\dao\member\UserDao;
use library\dao\otc\OtcOrderDao;
use library\dao\prediction\PredictionMarketDao;
use library\dao\robot\RobotDao;
use support\extend\Service;

/**
 * Admin V2 工作台运营总览 DTO 服务（A-WORK-001）。
 *
 * 只读聚合：各域实体计数（用户/Robot/OTC 挂单/市场/待审批）。
 * 字段口径：仅返回已确认的计数指标；不涉及金额推导。
 * 计数使用 Dao::count 简单等值条件（不依赖复杂操作符语法，避免运行时风险）。
 * 供 Admin 2.0 运营总览 dashboard 经 /api/v1/admin/workbench/overview 对接。
 */
class AdminWorkbenchDtoService extends Service
{
    /**
     * 运营总览聚合指标。
     *
     * @return array{user_count:int,robot_count:int,otc_open_orders:int,market_count:int,pending_approvals:int}
     */
    public function overview(): array
    {
        return [
            'user_count'        => (int) (new UserDao())->count(['status' => 1]),
            'robot_count'       => (int) (new RobotDao())->count([]),
            'otc_open_orders'   => (int) (new OtcOrderDao())->count(['status' => 'review']),
            'market_count'      => (int) (new PredictionMarketDao())->count([]),
            'pending_approvals' => (int) (new ApprovalRequestDao())->count(['status' => 'pending']),
        ];
    }
}
