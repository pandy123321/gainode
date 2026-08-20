<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\robot\RobotUpgradeOrderDao;
use support\extend\Service;

/**
 * Admin V2 Robot 升级订单列表 DTO 服务（A-ROBOT-001 补充）。
 *
 * 只读全量分页：robot_upgrade_orders 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Robot 详情页经 /api/v1/admin/robot/upgrade-orders 对接。
 */
class AdminUpgradeOrderDtoService extends Service
{
    public function __construct()
    {
        $this->dao = RobotUpgradeOrderDao::class;
        parent::__construct();
    }

    /**
     * 分页 Robot 升级订单 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status 状态筛选（可选）
     * @return array{orders:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new RobotUpgradeOrderDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['upgrade_order_id', 'robot_id', 'user_id', 'from_level', 'to_level', 'apt_cost', 'status', 'power_cap_after', 'cooling_end_at', 'approval_id', 'ledger_entry_id', 'rule_version', 'parameter_release_id', 'object_version', 'created_time']
        );

        $orders = [];
        foreach ($paginator->items() as $o) {
            $orders[] = [
                'upgrade_order_id'   => (string) $o->upgrade_order_id,
                'robot_id'           => (string) $o->robot_id,
                'user_id'            => (string) $o->user_id,
                'from_level'         => (int) $o->from_level,
                'to_level'           => (int) $o->to_level,
                'apt_cost'           => (string) $o->apt_cost,
                'status'             => (string) $o->status,
                'power_cap_after'    => (string) $o->power_cap_after,
                'cooling_end_at'     => (int) $o->cooling_end_at,
                'approval_id'        => (string) $o->approval_id,
                'ledger_entry_id'    => (string) $o->ledger_entry_id,
                'rule_version'       => (string) $o->rule_version,
                'parameter_release_id' => (string) $o->parameter_release_id,
                'object_version'     => (int) $o->object_version,
                'created_time'       => (int) $o->getRawOriginal('created_time'),
            ];
        }

        return [
            'orders' => $orders,
            'total'  => (int) $paginator->total(),
            'page'   => $page,
            'size'   => $size,
        ];
    }
}
