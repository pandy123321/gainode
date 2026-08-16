<?php

declare(strict_types=1);

namespace library\service\robot;

use library\dao\robot\RobotRewardDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\robot\RobotRewardModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * AI Reward 领域 Service — robot_rewards 表唯一 Authoritative Writer（S02-P04）
 *
 * @authoritative_writer robot_rewards
 *
 * 状态机（MC2 §3.3，W1–W10）：
 *   candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed
 *
 * 实现策略（fail-closed）：
 *   - 纯状态转移（W2/W3/W7/W8）已完整实现（审计 + object_version CAS）。
 *   - W1 hold / W4 completeClaim / W5 expire / W9,W10 reverse 依赖预算/资格/观察期/
 *     过期窗口/系数等快照参数（TBC）→ FAIL_CLOSED，不产生账本分录。
 *
 * @method RobotRewardModel create($data)
 * @method RobotRewardModel get($id, string $field = null)
 * @method RobotRewardModel find($id)
 * @method RobotRewardModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RobotRewardService extends Service
{
    // Event Catalog（MC2 §5，REWARD_*）
    public const EVENT_HELD                = 'REWARD_HELD';
    public const EVENT_CLAIM_WINDOW_OPENED = 'REWARD_CLAIM_WINDOW_OPENED';
    public const EVENT_CLAIMING            = 'REWARD_CLAIMING';
    public const EVENT_CLAIMED             = 'REWARD_CLAIMED';
    public const EVENT_EXPIRED_RETURNED    = 'REWARD_EXPIRED_RETURNED';
    public const EVENT_REVIEW_LOCKED       = 'REWARD_REVIEW_LOCKED';
    public const EVENT_REVIEW_CLEARED      = 'REWARD_REVIEW_CLEARED';
    public const EVENT_REVERSED            = 'REWARD_REVERSED';

    public function __construct()
    {
        $this->dao = RobotRewardDao::class;
        parent::__construct();
    }

    /**
     * 按 Robot 查询奖励记录（只读透传）
     */
    public function getByRobot(string $robotId)
    {
        return $this->getNewDao()->getByRobot($robotId);
    }

    /**
     * 按用户查询奖励记录（只读投影，对齐 05 §6 GET /ai/users/{id}/rewards）
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    // =========================================================================
    // 经济动作（FAIL_CLOSED）
    // =========================================================================

    /**
     * W1：candidate → held（记账持有，REWARD_ACCRUAL CREDIT）。
     * 系数/预算/资格快照未冻结 → FAIL_CLOSED。
     */
    public function hold(string $rewardId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'W1 hold depends on reward coefficient/budget snapshot (TBC) — not frozen'
        );
    }

    /**
     * W4：claiming → claimed（发放，REWARD_CLAIM DEBIT）。
     * 领取记账 / claim_id 回填依赖领取语义（TBC）→ FAIL_CLOSED。
     */
    public function completeClaim(string $rewardId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'W4 completeClaim depends on reward claim semantics (TBC) — not frozen'
        );
    }

    /**
     * W5：pending_claim → expired_returned（过期退回预算池，REWARD_EXPIRY_RETURN DEBIT）。
     * 预算池退回规则未冻结 → FAIL_CLOSED。
     */
    public function expire(string $rewardId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'W5 expire depends on budget return rules (TBC) — not frozen'
        );
    }

    /**
     * W9/W10：held/review/claimed → reversed（冲正，REWARD_REVERSAL DEBIT）。
     * 冲正分录规则未冻结 → FAIL_CLOSED。
     */
    public function reverse(string $rewardId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'W9/W10 reverse depends on reward reversal rules (TBC) — not frozen'
        );
    }

    // =========================================================================
    // 纯状态转移（MC2 §3.3）
    // =========================================================================

    /**
     * W2：held → pending_claim（进入领取窗口，expires_at 由调用方传入）。
     *
     * @param int $expiresAt 领取窗口过期时间（Unix 秒）
     */
    public function openClaimWindow(string $rewardId, int $expiresAt, string $actorId, string $actorRole): RobotRewardModel
    {
        if ($expiresAt <= time()) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'expires_at must be in the future');
        }
        return $this->transition(
            $rewardId, [RobotRewardModel::STATE_HELD], RobotRewardModel::STATE_PENDING_CLAIM,
            self::EVENT_CLAIM_WINDOW_OPENED, $actorId, $actorRole, ['expires_at' => $expiresAt]
        );
    }

    /**
     * W3：pending_claim → claiming（领取处理中，防重）
     */
    public function startClaim(string $rewardId, string $actorId, string $actorRole): RobotRewardModel
    {
        return $this->transition(
            $rewardId, [RobotRewardModel::STATE_PENDING_CLAIM], RobotRewardModel::STATE_CLAIMING,
            self::EVENT_CLAIMING, $actorId, $actorRole
        );
    }

    /**
     * W7：candidate → review（风控冻结审计中）
     */
    public function lockReview(string $rewardId, string $actorId, string $actorRole): RobotRewardModel
    {
        return $this->transition(
            $rewardId, [RobotRewardModel::STATE_CANDIDATE], RobotRewardModel::STATE_REVIEW,
            self::EVENT_REVIEW_LOCKED, $actorId, $actorRole
        );
    }

    /**
     * W8：review → held（解除风控冻结，回到已持有）
     */
    public function clearReview(string $rewardId, string $actorId, string $actorRole): RobotRewardModel
    {
        return $this->transition(
            $rewardId, [RobotRewardModel::STATE_REVIEW], RobotRewardModel::STATE_HELD,
            self::EVENT_REVIEW_CLEARED, $actorId, $actorRole
        );
    }

    // =========================================================================
    // 私有辅助
    // =========================================================================

    /**
     * 纯状态转移（审计 + object_version CAS，同事务原子）。
     *
     * @param array $extraData 额外要更新的字段（如 expires_at）
     */
    private function transition(
        string $rewardId,
        array $fromStates,
        string $toState,
        string $eventCode,
        string $actorId,
        string $actorRole,
        array $extraData = []
    ): RobotRewardModel {
        return (new TransactionBoundary())->run(function () use (
            $rewardId, $fromStates, $toState, $eventCode, $actorId, $actorRole, $extraData
        ) {
            $reward = $this->get($rewardId);
            if (empty($reward)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'reward not found');
            }
            $current = (string) $reward->state;
            if (!in_array($current, $fromStates, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid reward state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $rewardId);

            $update = array_merge([
                'state'          => $toState,
                'audit_event_id' => $auditId,
                'object_version' => (int) $reward->object_version + 1,
                'updated_time'   => time(),
            ], $extraData);

            $affected = Db::connection('mysql')
                ->table('robot_rewards')
                ->where('reward_id', $rewardId)
                ->where('state', $current)
                ->where('object_version', (int) $reward->object_version)
                ->update($update);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'reward state transition CAS conflict');
            }

            return $this->get($rewardId);
        });
    }

    /**
     * 追加 append-only 审计事件，返回 audit_event_id。
     */
    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId
    ): string {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'       => $auditId,
            'event_code'           => $eventCode,
            'actor_id'             => $actorId,
            'actor_role'           => $actorRole,
            'target_object_type'   => 'robot_rewards',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => '',
            'approval_id'          => '0',
            'case_id'              => '0',
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
