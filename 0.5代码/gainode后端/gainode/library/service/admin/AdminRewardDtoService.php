<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\robot\RobotRewardDao;
use support\extend\Service;

/**
 * Admin V2 Reward / Claim 运营列表 DTO 服务（A-ROBOT-003）。
 *
 * 只读全量分页：robot_rewards 全量 + 状态筛选。
 * 字段口径：仅返回已确认列；金额为 string decimal；时间为 UTC。
 * 供 Admin 2.0 Reward/Claim 运营页经 /api/v1/admin/robot/rewards 对接。
 */
class AdminRewardDtoService extends Service
{
    public function __construct()
    {
        $this->dao = RobotRewardDao::class;
        parent::__construct();
    }

    /**
     * 分页 Reward 列表 DTO。
     *
     * @param int $page
     * @param int $size
     * @param string $state 状态筛选（可选）
     * @return array{rewards:array,total:int,page:int,size:int}
     */
    public function list(int $page, int $size, string $state = ''): array
    {
        $params = [];
        if ($state !== '') {
            $params['state'] = $state;
        }
        $params['page'] = $page;
        $params['size'] = $size;
        $paginator = (new RobotRewardDao())->paginate(
            $params,
            ['created_time' => 'desc'],
            ['reward_id', 'user_id', 'robot_id', 'period', 'standard_capacity', 'daily_reward_coefficient', 'quantity_apt', 'state', 'eligibility_snapshot_id', 'budget_snapshot_id', 'claim_id', 'ledger_entry_id', 'expires_at', 'rule_version', 'object_version', 'created_time']
        );

        $rewards = [];
        foreach ($paginator->items() as $r) {
            $rewards[] = [
                'reward_id'               => (string) $r->reward_id,
                'user_id'                 => (string) $r->user_id,
                'robot_id'                => (string) $r->robot_id,
                'period'                  => (string) $r->period,
                'standard_capacity'       => (string) $r->standard_capacity,
                'daily_reward_coefficient'=> (string) $r->daily_reward_coefficient,
                'quantity_apt'            => (string) $r->quantity_apt,
                'state'                   => (string) $r->state,
                'eligibility_snapshot_id' => (string) $r->eligibility_snapshot_id,
                'budget_snapshot_id'      => (string) $r->budget_snapshot_id,
                'claim_id'                => (string) $r->claim_id,
                'ledger_entry_id'         => (string) $r->ledger_entry_id,
                'expires_at'              => (int) $r->expires_at,
                'rule_version'            => (string) $r->rule_version,
                'object_version'          => (int) $r->object_version,
                'created_time'            => (int) $r->getRawOriginal('created_time'),
            ];
        }

        return [
            'rewards' => $rewards,
            'total'   => (int) $paginator->total(),
            'page'    => $page,
            'size'    => $size,
        ];
    }
}
