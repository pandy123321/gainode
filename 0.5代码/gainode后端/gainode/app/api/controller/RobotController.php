<?php

declare(strict_types=1);

namespace app\api\controller;

use library\service\robot\RobotRewardService;
use library\service\robot\RobotService;
use library\service\robot\RobotUpgradeOrderService;
use support\controller\ApiV2;
use support\Response;

/**
 * AI / Robot 只读 C 端控制器（05 §6；S02-P04 骨架）。
 *
 * 只读：用户汇总 / 机器人列表 / 详情 / 允许动作 / 升级订单 / 奖励列表。
 * 写路径（start/stop/upgrade submit/reward claim）依赖 TBC 经济规则或受控转移，
 * 本控制器不开放任何写方法（保持 fail-closed；路由不注册写操作）。
 */
class RobotController extends ApiV2
{
    /** GET /api/v1/ai/users/{id}/summary */
    public function userSummary(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new RobotService())->summary($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/ai/robots */
    public function list(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new RobotService())->summary($userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/ai/robots/{robot_id} */
    public function detail(string $robotId): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new RobotService())->detail($robotId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/ai/robots/{robot_id}/actions */
    public function actions(string $robotId): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new RobotService())->allowedActions($robotId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/ai/users/{id}/upgrade-orders */
    public function upgradeOrders(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $orders = (new RobotUpgradeOrderService())->getByRobot($id);
            $items = [];
            foreach ($orders as $o) {
                $items[] = [
                    'upgrade_order_id'   => (string) $o->upgrade_order_id,
                    'robot_id'           => (string) $o->robot_id,
                    'from_level'         => (int) $o->from_level,
                    'to_level'           => (int) $o->to_level,
                    'apt_cost'           => (string) $o->apt_cost,
                    'status'             => (string) $o->status,
                    'cooling_end_at'     => (int) $o->cooling_end_at,
                    'rule_version'       => (string) $o->rule_version,
                    'parameter_release_id'=> (string) $o->parameter_release_id,
                    'object_version'     => (int) $o->object_version,
                    'created_time'       => (int) $o->getRawOriginal('created_time'),
                ];
            }
            return $this->envelope(['orders' => $items]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/ai/users/{id}/rewards */
    public function rewards(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $rewards = (new RobotRewardService())->listByUser($id);
            $items = [];
            foreach ($rewards as $r) {
                $items[] = [
                    'reward_id'               => (string) $r->reward_id,
                    'user_id'                 => (string) $r->user_id,
                    'robot_id'                => (string) $r->robot_id,
                    'period'                  => (string) $r->period,
                    'standard_capacity'       => (string) $r->standard_capacity,
                    'daily_reward_coefficient'=> (string) $r->daily_reward_coefficient,
                    'quantity_apt'            => (string) $r->quantity_apt,
                    'state'                   => (string) $r->state,
                    'claim_id'                => (string) $r->claim_id,
                    'ledger_entry_id'         => (string) $r->ledger_entry_id,
                    'expires_at'              => (int) $r->expires_at,
                    'rule_version'            => (string) $r->rule_version,
                    'object_version'          => (int) $r->object_version,
                    'created_time'            => (int) $r->getRawOriginal('created_time'),
                ];
            }
            return $this->envelope(['rewards' => $items]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /**
     * 写路径（显式 fail-closed，契约 503 DependencyUnavailable）。
     * 升级/领取依赖 AI 经济规则与预算（TBC），服务层抛 DEPENDENCY_UNAVAILABLE → envelopeError 503。
     */

    /** POST /api/v1/ai/users/{id}/upgrade-orders */
    public function upgradeOrderCreate(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $data = $this->getPost();
            $toLevel = (int) ($data['to_level'] ?? 0);
            $result = (new RobotUpgradeOrderService())->submit($id, $toLevel, $userId, 'END_USER');
            return $this->envelope(['upgrade_order_id' => (string) $result]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/ai/users/{id}/reward-claims */
    public function rewardClaim(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $data = $this->getPost();
            $rewardId = (string) ($data['reward_id'] ?? '');
            (new RobotRewardService())->completeClaim($rewardId, $userId, 'END_USER');
            return $this->envelope([]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
