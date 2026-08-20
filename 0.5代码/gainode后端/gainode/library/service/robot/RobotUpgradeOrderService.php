<?php

declare(strict_types=1);

namespace library\service\robot;

use library\dao\robot\RobotUpgradeOrderDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\robot\RobotUpgradeOrderModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\utils\Random;

/**
 * Robot 升级订单 Service — robot_upgrade_orders 表唯一 Authoritative Writer（S02-P04）
 *
 * @authoritative_writer robot_upgrade_orders
 *
 * 状态机（05 §4 V2.3 canonical，Owner 2B1-ENUM-05）：
 *   pending → processing → completed / pending → cancelled / processing → failed（可重试回 processing）
 *   - 大额人工确认 = OPS_OPERATOR + RISK_APPROVER（MC2 Owner 裁决 #13）
 *
 * 实现策略（fail-closed）：
 *   - 纯状态转移（process/complete/fail/cancel）完整实现（审计 + object_version CAS）。
 *   - quote / submit 依赖升级成本（AI.upgrade_apt_requirement）与资金去向
 *     （AI.upgrade_allocation_profile，06 TBC）→ FAIL_CLOSED。
 *
 * @method RobotUpgradeOrderModel create($data)
 * @method RobotUpgradeOrderModel get($id, string $field = null)
 * @method RobotUpgradeOrderModel find($id)
 * @method RobotUpgradeOrderModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RobotUpgradeOrderService extends Service
{
    // Event Catalog（MC2 §5，UPGRADE_*）
    public const EVENT_PROCESSING = 'UPGRADE_PROCESSING';
    public const EVENT_COMPLETED  = 'UPGRADE_COMPLETED';
    public const EVENT_FAILED     = 'UPGRADE_FAILED';
    public const EVENT_CANCELLED  = 'UPGRADE_CANCELLED';

    public function __construct()
    {
        $this->dao = RobotUpgradeOrderDao::class;
        parent::__construct();
    }

    /**
     * 按 Robot 查询升级订单（只读透传）
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByRobot(string $robotId)
    {
        return $this->getNewDao()->getByRobot($robotId);
    }

    // =========================================================================
    // 经济动作（FAIL_CLOSED）
    // =========================================================================

    /**
     * 升级报价 quote：读取 Active Release 的 56 级升级成本/产能/冷却规则，返回报价投影。
     *
     * 依赖已批准参数（CR-20260818-002）：
     *   - AI.upgrade_apt_requirement（基准升级费用，56 级）
     *   - AI.standard_capacity_by_level（标准产能，56 级）
     *   - AI.upgrade_cooldown_by_range（升级冷却）
     *   - AI.upgrade_p_discount_by_level（P 等级升级优惠）
     * 未批准仍 fail-closed：
     *   - AI.power_cap_by_robot_level 完整 56 级映射（附录仅 Lv.18=8,600 散点）→ power_limit_diff=null
     *   - AI.upgrade_allocation_profile（升级 APT 去向结构）→ 不影响报价，仅影响 submit 资金分录
     *
     * @return array<string,mixed> 报价投影（对齐 02 §6 字段）
     */
    public function quote(string $robotId, int $toLevel, string $actorId, string $actorRole): array
    {
        $robot = (new RobotService())->get($robotId);
        if (empty($robot)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'robot not found');
        }

        $fromLevel = (int) $robot->level;
        if ($toLevel <= $fromLevel) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'to_level must be greater than current level');
        }
        if ($toLevel < 1 || $toLevel > 56) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'to_level out of range 1-56');
        }

        $reader = new RobotRuleReader();
        $snap = $reader->getRuleSnapshot();
        if ($snap['source_status'] !== RobotRuleReader::SOURCE_AVAILABLE) {
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'upgrade rule not active');
        }

        $aptCost = $reader->getUpgradeCost($toLevel);
        if ($aptCost === null) {
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'upgrade cost not defined for level');
        }

        $fromCapacity = $reader->getStandardCapacity($fromLevel);
        $toCapacity = $reader->getStandardCapacity($toLevel);
        if ($fromCapacity === null || $toCapacity === null) {
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'standard capacity not defined');
        }

        // P 等级优惠：actor 的 P 等级由调用方提供（这里按 actorRole 无法推导），默认无优惠。
        // 实际优惠应在 Controller 层解析用户 P 等级后传入；quote 仅返回基准价 + 优惠比例表。
        $discount = null; // 由 Controller 解析后叠加，报价返回基准 apt_cost

        // Power cap 派生（standard_capacity × factor，bcmath）。无 factor → null（fail-closed）。
        $fromPowerCap = $reader->getPowerCap($fromLevel);
        $toPowerCap = $reader->getPowerCap($toLevel);
        $powerLimitDiff = null;
        if ($fromPowerCap !== null && $toPowerCap !== null) {
            $diff = bcsub($toPowerCap, $fromPowerCap, 18);
            $powerLimitDiff = [
                'from' => $fromPowerCap,
                'to'   => $toPowerCap,
                'diff' => $diff,
            ];
        }

        return [
            'current_level'           => $fromLevel,
            'target_level'            => $toLevel,
            'apt_cost'                => $aptCost,
            'capability_diff'         => [
                'standard_capacity'   => [
                    'from' => $fromCapacity,
                    'to'   => $toCapacity,
                ],
            ],
            'power_limit_diff'        => $powerLimitDiff,
            'cooldown'                => $reader->getUpgradeCooldownDays($toLevel),
            'eligibility'             => [], // 关键升级节点（直推/KYC）校验在 submit 阶段执行
            'quote_expires_at'        => time() + 600,
            'rule_version'            => $snap['rule_version'],
            'parameter_release_id'    => $snap['parameter_release_id'],
        ];
    }

    /**
     * 提交升级订单 submit：依赖升级成本/资金去向（06 TBC）→ FAIL_CLOSED。
     */
    public function submit(string $robotId, int $toLevel, string $actorId, string $actorRole): void
    {
        throw new DomainException(
            ErrorDict::DEPENDENCY_UNAVAILABLE,
            'Upgrade submit depends on AI.upgrade_apt_requirement/allocation_profile (TBC) — not frozen'
        );
    }

    // =========================================================================
    // 纯状态转移（05 §4 V2.3）
    // =========================================================================

    /**
     * pending → processing
     */
    public function process(string $upgradeOrderId, string $actorId, string $actorRole): RobotUpgradeOrderModel
    {
        return $this->transition(
            $upgradeOrderId, [RobotUpgradeOrderModel::STATUS_PENDING], RobotUpgradeOrderModel::STATUS_PROCESSING,
            self::EVENT_PROCESSING, $actorId, $actorRole
        );
    }

    /**
     * processing → completed
     */
    public function complete(string $upgradeOrderId, string $actorId, string $actorRole): RobotUpgradeOrderModel
    {
        return $this->transition(
            $upgradeOrderId, [RobotUpgradeOrderModel::STATUS_PROCESSING], RobotUpgradeOrderModel::STATUS_COMPLETED,
            self::EVENT_COMPLETED, $actorId, $actorRole
        );
    }

    /**
     * processing → failed（可重试回 processing）
     */
    public function fail(string $upgradeOrderId, string $actorId, string $actorRole): RobotUpgradeOrderModel
    {
        return $this->transition(
            $upgradeOrderId, [RobotUpgradeOrderModel::STATUS_PROCESSING], RobotUpgradeOrderModel::STATUS_FAILED,
            self::EVENT_FAILED, $actorId, $actorRole
        );
    }

    /**
     * pending → cancelled（END_USER 主动取消）
     */
    public function cancel(string $upgradeOrderId, string $actorId, string $actorRole): RobotUpgradeOrderModel
    {
        return $this->transition(
            $upgradeOrderId, [RobotUpgradeOrderModel::STATUS_PENDING], RobotUpgradeOrderModel::STATUS_CANCELLED,
            self::EVENT_CANCELLED, $actorId, $actorRole
        );
    }

    // =========================================================================
    // 私有辅助
    // =========================================================================

    /**
     * 纯状态转移（审计 + object_version CAS，同事务原子）。
     */
    private function transition(
        string $upgradeOrderId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole
    ): RobotUpgradeOrderModel {
        return (new TransactionBoundary())->run(function () use (
            $upgradeOrderId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole
        ) {
            $order = $this->get($upgradeOrderId);
            if (empty($order)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'upgrade order not found');
            }
            $current = (string) $order->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid upgrade order state transition');
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $upgradeOrderId);

            $affected = Db::connection('mysql')
                ->table('robot_upgrade_orders')
                ->where('upgrade_order_id', $upgradeOrderId)
                ->where('status', $current)
                ->where('object_version', (int) $order->object_version)
                ->update([
                    'status'         => $toStatus,
                    'audit_event_id' => $auditId,
                    'object_version' => (int) $order->object_version + 1,
                    'updated_time'   => time(),
                ]);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'upgrade order state transition CAS conflict');
            }

            return $this->get($upgradeOrderId);
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
            'target_object_type'   => 'robot_upgrade_orders',
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
