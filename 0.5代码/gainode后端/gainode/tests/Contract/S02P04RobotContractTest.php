<?php

declare(strict_types=1);

/**
 * S02-P04 Robot / Reward / Upgrade 契约测试（独立 CLI 脚本，无需 PHPUnit，不触数据库）。
 *
 * 覆盖纯逻辑：领域状态常量（MC1/2B-1 对齐）、Event Catalog 常量、
 * fail-closed 经济动作（start/stop/hold/completeClaim/expire/reverse/create/complete
 * 在依赖未冻结时 MUST DEPENDENCY_UNAVAILABLE）。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\model\robot\RobotModel;
use library\model\robot\RobotRewardModel;
use library\model\robot\RobotUpgradeOrderModel;
use library\service\robot\RobotRewardService;
use library\service\robot\RobotService;
use library\service\robot\RobotUpgradeOrderService;
use support\exception\DomainException;

function expectDependencyUnavailable(callable $fn, string $label): void
{
    try {
        $fn();
        check(false, $label);
    } catch (DomainException $e) {
        check($e->resultCode() === ErrorDict::DEPENDENCY_UNAVAILABLE, "{$label}（resultCode={$e->resultCode()}）");
    } catch (\Throwable $e) {
        check(false, "{$label}（非 DomainException：{$e->getMessage()}）");
    }
}

echo "=====================================================\n";
echo "S02-P04 robot/reward/upgrade contract test\n";
echo "=====================================================\n\n";

// ======================= 1. 领域状态常量 =======================
echo "[1] 领域状态常量（MC1/2B-1 对齐）\n";
check(RobotModel::STATUSES === ['inactive', 'active', 'cooling', 'review', 'restricted', 'paused'], 'Robot 6 态冻结');
check(RobotRewardModel::STATES === ['candidate', 'held', 'pending_claim', 'claiming', 'claimed', 'expired_returned', 'review', 'reversed'], 'Reward 8 态冻结');
check(RobotUpgradeOrderModel::STATUSES === ['pending', 'processing', 'completed', 'failed', 'cancelled'], 'UpgradeOrder 5 态冻结');
check(in_array('object_version', (new RobotModel())->fields, true), 'Robot.object_version 在 $fields');
check(in_array('audit_event_id', (new RobotRewardModel())->fields, true), 'Reward.audit_event_id 在 $fields');
check(in_array('audit_event_id', (new RobotUpgradeOrderModel())->fields, true), 'UpgradeOrder.audit_event_id 在 $fields');
check(!in_array('audit_event_id', (new RobotModel())->fields, true), 'Robot 无 audit_event_id 列（MC1 DDL）');
echo "\n";

// ======================= 2. Event Catalog 常量 =======================
echo "[2] Event Catalog 常量（MC2 §5）\n";
check(RobotService::EVENT_COOLING_ENTERED === 'ROBOT_COOLING_ENTERED', 'ROBOT_COOLING_ENTERED');
check(RobotService::EVENT_PAUSED === 'ROBOT_PAUSED', 'ROBOT_PAUSED');
check(RobotService::EVENT_DISABLED === 'ROBOT_DISABLED', 'ROBOT_DISABLED');
check(RobotRewardService::EVENT_HELD === 'REWARD_HELD', 'REWARD_HELD');
check(RobotRewardService::EVENT_CLAIMING === 'REWARD_CLAIMING', 'REWARD_CLAIMING');
check(RobotRewardService::EVENT_REVERSED === 'REWARD_REVERSED', 'REWARD_REVERSED');
check(RobotUpgradeOrderService::EVENT_CANCELLED === 'ROBOT_UPGRADE_ORDER_CANCELLED', 'ROBOT_UPGRADE_ORDER_CANCELLED');
echo "\n";

// ======================= 3. fail-closed 经济动作 =======================
echo "[3] fail-closed 经济/依赖动作（DEPENDENCY_UNAVAILABLE）\n";
$robotSvc = new RobotService();
$rewardSvc = new RobotRewardService();
$upgradeSvc = new RobotUpgradeOrderService();

expectDependencyUnavailable(function () use ($robotSvc) {
    $robotSvc->start('R1', 'U1', 'END_USER');
}, 'Robot.start → DEPENDENCY_UNAVAILABLE');
expectDependencyUnavailable(function () use ($robotSvc) {
    $robotSvc->stop('R1', 'U1', 'END_USER');
}, 'Robot.stop → DEPENDENCY_UNAVAILABLE');

expectDependencyUnavailable(function () use ($rewardSvc) {
    $rewardSvc->hold('W1', 'U1', 'SYSTEM');
}, 'Reward.hold → DEPENDENCY_UNAVAILABLE');
expectDependencyUnavailable(function () use ($rewardSvc) {
    $rewardSvc->completeClaim('W1', 'U1', 'END_USER');
}, 'Reward.completeClaim → DEPENDENCY_UNAVAILABLE');
expectDependencyUnavailable(function () use ($rewardSvc) {
    $rewardSvc->expire('W1', 'U1', 'SYSTEM');
}, 'Reward.expire → DEPENDENCY_UNAVAILABLE');
expectDependencyUnavailable(function () use ($rewardSvc) {
    $rewardSvc->reverse('W1', 'U1', 'OPS_OPERATOR');
}, 'Reward.reverse → DEPENDENCY_UNAVAILABLE');

expectDependencyUnavailable(function () use ($upgradeSvc) {
    $upgradeSvc->placeOrder(['robot_id' => 'R1', 'to_level' => 2]);
}, 'Upgrade.placeOrder → DEPENDENCY_UNAVAILABLE');
expectDependencyUnavailable(function () use ($upgradeSvc) {
    $upgradeSvc->complete('O1', 'U1', 'SYSTEM');
}, 'Upgrade.complete → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 4. 错误码映射 =======================
echo "[4] 错误码映射（05 §7）\n";
check(ErrorDict::httpStatus(ErrorDict::DEPENDENCY_UNAVAILABLE) === 503, 'DEPENDENCY_UNAVAILABLE → 503');
check(ErrorDict::httpStatus(ErrorDict::OBJECT_VERSION_CONFLICT) === 409, 'OBJECT_VERSION_CONFLICT → 409');
echo "\n";

summary();
