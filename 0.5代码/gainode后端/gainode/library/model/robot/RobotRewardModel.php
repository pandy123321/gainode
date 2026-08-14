<?php

declare(strict_types=1);

namespace library\model\robot;

use support\extend\Model;

/**
 * robot_rewards 表映射 — AI Reward 记录（05 §3 AIReward + §4 AI Reward 状态机）
 *
 * 领域状态机（canonical enum，冻结于 MC1 Canonical State Freeze，禁止自创）：
 *   candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed
 *
 * @property string $reward_id Reward ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $robot_id Robot ID(robots.robot_id)
 * @property string $period 结算周期标识(日期/周期键)
 * @property string $standard_capacity 当期分配容量快照
 * @property string $daily_reward_coefficient 当天服务端系数(可为0)
 * @property string $quantity_apt 待领取/已发放 APT 数量
 * @property string $state Reward 状态(05 §4 canonical，8态)
 * @property string $eligibility_snapshot_id 资格快照ID
 * @property string $budget_snapshot_id 预算快照ID
 * @property string $claim_id 领取记录ID(claimed 后回填)
 * @property string $ledger_entry_id 关联账本分录ID(held/posted 后回填)
 * @property int $expires_at 领取窗口过期时间(Unix秒)
 * @property string|null $idempotency_key 幂等键(生成去重)
 * @property string $audit_event_id 关联审计事件ID
 * @property string $rule_version 生效规则版本号
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class RobotRewardModel extends Model
{
    // ---- 领域状态常量（05 §4 canonical，与 MC1 冻结一致）----
    public const STATE_CANDIDATE = 'candidate';
    public const STATE_HELD = 'held';
    public const STATE_PENDING_CLAIM = 'pending_claim';
    public const STATE_CLAIMING = 'claiming';
    public const STATE_CLAIMED = 'claimed';
    public const STATE_EXPIRED_RETURNED = 'expired_returned';
    public const STATE_REVIEW = 'review';
    public const STATE_REVERSED = 'reversed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATES = [
        self::STATE_CANDIDATE,
        self::STATE_HELD,
        self::STATE_PENDING_CLAIM,
        self::STATE_CLAIMING,
        self::STATE_CLAIMED,
        self::STATE_EXPIRED_RETURNED,
        self::STATE_REVIEW,
        self::STATE_REVERSED,
    ];

    public $table = 'robot_rewards';
    public $primaryKey = 'reward_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'reward_id',
        'user_id',
        'robot_id',
        'period',
        'standard_capacity',
        'daily_reward_coefficient',
        'quantity_apt',
        'state',
        'eligibility_snapshot_id',
        'budget_snapshot_id',
        'claim_id',
        'ledger_entry_id',
        'expires_at',
        'idempotency_key',
        'audit_event_id',
        'rule_version',
        'object_version',
        'created_time',
        'updated_time',
    ];

    /**
     * Robot 归属（同模块 FK）
     */
    public function robot()
    {
        return $this->belongsTo(RobotModel::class, 'robot_id', 'robot_id');
    }
}
