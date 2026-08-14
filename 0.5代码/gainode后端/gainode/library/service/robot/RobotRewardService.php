<?php

declare(strict_types=1);

namespace library\service\robot;

use library\dao\robot\RobotRewardDao;
use library\model\robot\RobotRewardModel;
use support\extend\Service;

/**
 * AI Reward 领域 Service — robot_rewards 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer robot_rewards
 *
 * 状态机说明（05 §4 canonical，MC1 冻结）：
 *   candidate → held → pending_claim → claiming → claimed
 *   expired_returned / review / reversed 为旁路状态。
 *   - candidate：奖励候选（预算内、待确认）
 *   - held：已记账持有（不可提）
 *   - pending_claim：进入领取窗口
 *   - claiming：领取处理中（防重）
 *   - expired_returned：过期退回预算池
 *   - review：风控冻结审计中
 *   - reversed：冲正（财务纠正，生成 reversal entry）
 *
 * 本骨架不实现状态转移矩阵（属 Machine Contract 第二批范畴）。转移矩阵 FROZEN 前，
 * 任何状态流转操作 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method RobotRewardModel create($data)
 * @method RobotRewardModel get($id, string $field = null)
 * @method RobotRewardModel find($id)
 * @method RobotRewardModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RobotRewardService extends Service
{
    public function __construct()
    {
        $this->dao = RobotRewardDao::class;
        parent::__construct();
    }

    /**
     * 按 Robot 查询奖励记录（只读透传）
     *
     * @param string $robotId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRobot(string $robotId)
    {
        return $this->getNewDao()->getByRobot($robotId);
    }
}
