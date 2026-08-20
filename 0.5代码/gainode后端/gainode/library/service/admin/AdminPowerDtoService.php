<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\power\PowerPositionDao;
use support\extend\Service;

/**
 * Admin V2 Power 账户列表 DTO 服务（A-POWER-001）。
 *
 * 只读全量分页：power_positions 全量。
 * 字段口径：仅返回已确认列；数量为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Power 账户与流水页经 /api/v1/admin/power/accounts 对接。
 */
class AdminPowerDtoService extends Service
{
    public function __construct()
    {
        $this->dao = PowerPositionDao::class;
        parent::__construct();
    }

    /**
     * 分页 Power 账户列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @return array{accounts:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size): array
    {
        $paginator = (new PowerPositionDao())->paginate(
            [],
            ['created_time' => 'desc'],
            ['user_id', 'available', 'frozen', 'consumed_period', 'released_period', 'recovering', 'limit', 'power_cap_source_robot_level', 'last_restore_at', 'next_restore_at', 'rule_version', 'object_version', 'created_time']
        );

        $accounts = [];
        foreach ($paginator->items() as $p) {
            $accounts[] = [
                'user_id'                  => (string) $p->user_id,
                'available'                => (string) $p->available,
                'frozen'                   => (string) $p->frozen,
                'consumed_period'          => (string) $p->consumed_period,
                'released_period'          => (string) $p->released_period,
                'recovering'               => (string) $p->recovering,
                'limit'                    => (string) $p->limit,
                'power_cap_source_robot_level' => (int) $p->power_cap_source_robot_level,
                'last_restore_at'          => (int) $p->last_restore_at,
                'next_restore_at'          => (int) $p->next_restore_at,
                'rule_version'             => (string) $p->rule_version,
                'object_version'           => (int) $p->object_version,
                'created_time'             => (int) $p->getRawOriginal('created_time'),
            ];
        }

        return [
            'accounts' => $accounts,
            'total'    => (int) $paginator->total(),
            'page'     => $page,
            'size'     => $size,
        ];
    }
}
