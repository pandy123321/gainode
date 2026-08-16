<?php

declare(strict_types=1);

namespace library\service\robot;

use library\dao\robot\RobotDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\robot\RobotModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * Robot 领域 Service — robots 表唯一 Authoritative Writer（S02-P04）
 *
 * @authoritative_writer robots
 *
 * 状态机（MC2 §3.2，R1–R12）：
 *   inactive / active / cooling / review / restricted / paused
 *
 * 实现策略（fail-closed）：
 *   - 纯状态转移（R2/R4/R5/R6/R7/R8/R9/R10/R11/R12）已完整实现（审计 + object_version CAS）。
 *   - R1 start / R3 stop 依赖 Power 消耗/释放规则（06 TBC）→ FAIL_CLOSED。
 *   - robots 表无 audit_event_id 列（MC1 DDL），审计经 audit_events.target_object_type 单向关联。
 *
 * @method RobotModel create($data)
 * @method RobotModel get($id, string $field = null)
 * @method RobotModel find($id)
 * @method RobotModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RobotService extends Service
{
    // Event Catalog（MC2 §5，ROBOT_*）
    public const EVENT_STARTED            = 'ROBOT_STARTED';
    public const EVENT_STOPPED            = 'ROBOT_STOPPED';
    public const EVENT_COOLING_ENTERED    = 'ROBOT_COOLING_ENTERED';
    public const EVENT_COOLING_EXITED     = 'ROBOT_COOLING_EXITED';
    public const EVENT_REVIEW_LOCKED      = 'ROBOT_REVIEW_LOCKED';
    public const EVENT_REVIEW_CLEARED     = 'ROBOT_REVIEW_CLEARED';
    public const EVENT_RESTRICTED         = 'ROBOT_RESTRICTED';
    public const EVENT_RESTRICTION_LIFTED = 'ROBOT_RESTRICTION_LIFTED';
    public const EVENT_PAUSED             = 'ROBOT_PAUSED';
    public const EVENT_RESUMED            = 'ROBOT_RESUMED';
    public const EVENT_DISABLED           = 'ROBOT_DISABLED';

    public function __construct()
    {
        $this->dao = RobotDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询 Robot 列表（只读透传）
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    // =========================================================================
    // 只读投影（summary / detail / allowed_actions）
    // =========================================================================

    /**
     * 用户 Robot 摘要投影。无 Active Release 时 source_status=UNAVAILABLE。
     *
     * @return array<string,mixed>
     */
    public function summary(string $userId): array
    {
        $rule = (new RobotRuleReader())->getRuleSnapshot();
        $list = [];
        foreach ($this->getByUser($userId) as $r) {
            $list[] = [
                'robot_id'          => (string) $r->robot_id,
                'level'             => (int) $r->level,
                'status'            => (string) $r->status,
                'standard_capacity' => (string) $r->standard_capacity,
                'rule_version'      => (string) $r->rule_version,
            ];
        }
        return [
            'robots'        => $list,
            'source_status' => $rule['source_status'],
            'reason_code'   => $rule['reason_code'],
        ];
    }

    /**
     * 单个 Robot 详情投影。
     *
     * @return array<string,mixed>
     */
    public function detail(string $robotId): array
    {
        $robot = $this->get($robotId);
        if (empty($robot)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'robot not found');
        }
        $rule = (new RobotRuleReader())->getRuleSnapshot();
        return [
            'robot_id'             => (string) $robot->robot_id,
            'user_id'              => (string) $robot->user_id,
            'level'                => (int) $robot->level,
            'status'               => (string) $robot->status,
            'standard_capacity'    => (string) $robot->standard_capacity,
            'capabilities'         => $robot->capabilities,
            'rule_version'         => (string) $robot->rule_version,
            'parameter_release_id' => (string) $robot->parameter_release_id,
            'source_status'        => $rule['source_status'],
            'reason_code'          => $rule['reason_code'],
        ];
    }

    /**
     * Robot 允许动作投影。S02-P04：start/stop/upgrade/reward-claim 依赖 TBC 规则
     * → allowed_actions 恒为空（fail-closed），候选动作进 blocked_actions。
     *
     * @return array<string,mixed>
     */
    public function allowedActions(string $robotId): array
    {
        $robot = $this->get($robotId);
        if (empty($robot)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'robot not found');
        }
        $rule = (new RobotRuleReader())->getRuleSnapshot();
        $candidates = self::candidateActions((string) $robot->status);

        return [
            'robot_id'         => $robotId,
            'status'           => (string) $robot->status,
            'allowed_actions'  => [],
            'blocked_actions'  => $candidates,
            'source_status'    => $rule['source_status'],
            'reason_code'      => $rule['reason_code'],
        ];
    }

    /**
     * 按状态返回理论上的 end_user 候选动作（仅供 blocked_actions 展示，不授予执行权）。
     *
     * @return string[]
     */
    private static function candidateActions(string $status): array
    {
        switch ($status) {
            case RobotModel::STATUS_INACTIVE:
                return ['start'];
            case RobotModel::STATUS_ACTIVE:
                return ['stop', 'upgrade'];
            case RobotModel::STATUS_PAUSED:
                return ['resume'];
            default:
                return [];
        }
    }

    // =========================================================================
    // 经济/依赖动作（FAIL_CLOSED）
    // =========================================================================

    /**
     * R1：inactive → active（启动，Power 消耗）。
     * Power 消耗规则（AI.power_*，06 TBC）未冻结 → FAIL_CLOSED。
     */
    public function start(string $robotId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'R1 start depends on Power consume rules (AI.power_*, 06 TBC) — not frozen'
        );
    }

    /**
     * R3：active → inactive（停止，Power 释放）。
     * Power 释放规则（06 TBC）未冻结 → FAIL_CLOSED。
     */
    public function stop(string $robotId, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'R3 stop depends on Power release rules (06 TBC) — not frozen'
        );
    }

    // =========================================================================
    // 纯状态转移（MC2 §3.2）
    // =========================================================================

    /**
     * R2：active → cooling
     */
    public function enterCooling(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_ACTIVE], RobotModel::STATUS_COOLING,
            self::EVENT_COOLING_ENTERED, $actorId, $actorRole
        );
    }

    /**
     * R6：cooling → active
     */
    public function exitCooling(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_COOLING], RobotModel::STATUS_ACTIVE,
            self::EVENT_COOLING_EXITED, $actorId, $actorRole
        );
    }

    /**
     * R4：active → review
     */
    public function lockReview(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_ACTIVE], RobotModel::STATUS_REVIEW,
            self::EVENT_REVIEW_LOCKED, $actorId, $actorRole
        );
    }

    /**
     * R5：review → active
     */
    public function clearReview(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_REVIEW], RobotModel::STATUS_ACTIVE,
            self::EVENT_REVIEW_CLEARED, $actorId, $actorRole
        );
    }

    /**
     * R7：active → restricted
     */
    public function restrict(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_ACTIVE], RobotModel::STATUS_RESTRICTED,
            self::EVENT_RESTRICTED, $actorId, $actorRole
        );
    }

    /**
     * R8：restricted → active
     */
    public function liftRestriction(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_RESTRICTED], RobotModel::STATUS_ACTIVE,
            self::EVENT_RESTRICTION_LIFTED, $actorId, $actorRole
        );
    }

    /**
     * R9/R12：active/cooling/review/restricted → paused
     */
    public function pause(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId,
            [
                RobotModel::STATUS_ACTIVE,
                RobotModel::STATUS_COOLING,
                RobotModel::STATUS_REVIEW,
                RobotModel::STATUS_RESTRICTED,
            ],
            RobotModel::STATUS_PAUSED,
            self::EVENT_PAUSED,
            $actorId,
            $actorRole
        );
    }

    /**
     * R10：paused → active
     */
    public function resume(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_PAUSED], RobotModel::STATUS_ACTIVE,
            self::EVENT_RESUMED, $actorId, $actorRole
        );
    }

    /**
     * R11：review → inactive
     */
    public function disable(string $robotId, string $actorId, string $actorRole): RobotModel
    {
        return $this->transition(
            $robotId, [RobotModel::STATUS_REVIEW], RobotModel::STATUS_INACTIVE,
            self::EVENT_DISABLED, $actorId, $actorRole
        );
    }

    // =========================================================================
    // 私有辅助
    // =========================================================================

    /**
     * 纯状态转移（审计 + object_version CAS，同事务原子）。
     */
    private function transition(
        string $robotId,
        array $fromStates,
        string $toState,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): RobotModel {
        return (new TransactionBoundary())->run(function () use (
            $robotId, $fromStates, $toState, $eventCode, $actorId, $actorRole
        ) {
            $robot = $this->get($robotId);
            if (empty($robot)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'robot not found');
            }
            $current = (string) $robot->status;
            if (!in_array($current, $fromStates, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid robot state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $robotId);

            $affected = Db::connection('mysql')
                ->table('robots')
                ->where('robot_id', $robotId)
                ->where('status', $current)
                ->where('object_version', (int) $robot->object_version)
                ->update([
                    'status'         => $toState,
                    'object_version' => (int) $robot->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'robot state transition CAS conflict');
            }

            return $this->get($robotId);
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
            'target_object_type'   => 'robots',
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
