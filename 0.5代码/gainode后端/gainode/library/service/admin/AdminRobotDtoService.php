<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\robot\RobotDao;
use support\extend\Service;

/**
 * Admin V2 Robot 列表 DTO 服务（A-ROBOT-001）。
 *
 * 只读全量分页：robots 全量 + 状态/等级筛选。
 * 字段口径：仅返回已确认列；容量为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Robot 列表页经 /api/v1/admin/robot/list 对接。
 */
class AdminRobotDtoService extends Service
{
    public function __construct()
    {
        $this->dao = RobotDao::class;
        parent::__construct();
    }

    /**
     * 分页 Robot 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $status Robot 状态筛选（可选）
     * @return array{robots:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $status = ''): array
    {
        $params = [];
        if ($status !== '') {
            $params['status'] = $status;
        }
        $paginator = (new RobotDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['robot_id', 'user_id', 'level', 'status', 'standard_capacity', 'rule_version', 'parameter_release_id', 'object_version', 'created_time']
        );

        $robots = [];
        foreach ($paginator->items() as $r) {
            $robots[] = [
                'robot_id'             => (string) $r->robot_id,
                'user_id'              => (string) $r->user_id,
                'level'                => (int) $r->level,
                'status'               => (string) $r->status,
                'standard_capacity'    => (string) $r->standard_capacity,
                'rule_version'         => (string) $r->rule_version,
                'parameter_release_id' => (string) $r->parameter_release_id,
                'object_version'       => (int) $r->object_version,
                'created_time'         => (int) $r->getRawOriginal('created_time'),
            ];
        }

        return [
            'robots' => $robots,
            'total'  => (int) $paginator->total(),
            'page'   => $page,
            'size'   => $size,
        ];
    }
}
