<?php

declare(strict_types=1);

/**
 * S02-P04 Robot / Reward / Upgrade 契约测试（独立 CLI 脚本，无需 PHPUnit，不触数据库）。
 *
 * 覆盖纯逻辑：领域状态常量冻结、RobotRuleReader 键名/枚举、fail-closed 写路径、
 * V2 错误码 HTTP 映射。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\model\robot\RobotModel;
use library\model\robot\RobotRewardModel;
use library\model\robot\RobotUpgradeOrderModel;
use library\service\robot\RobotRewardService;
use library\service\robot\RobotRuleReader;
use library\service\robot\RobotService;
use library\service\robot\RobotUpgradeOrderService;
use support\exception\DomainException;

function expectDomainException(callable $fn, string $expectedCode, string $label): void
{
    try {
        $fn();
        check(false, $label);
    } catch (DomainException $e) {
        check($e->resultCode() === $expectedCode, "{$label}（resultCode={$e->resultCode()}）");
    } catch (\Throwable $e) {
        check(false, "{$label}（非 DomainException：{$e->getMessage()}）");
    }
}

echo "=====================================================\n";
echo "S02-P04 robot / reward / upgrade contract test\n";
echo "=====================================================\n\n";

// ======================= 1. 领域状态常量（MC1/MC2 冻结） =======================
echo "[1] 领域状态常量（MC1/MC2 冻结）\n";
check(RobotModel::STATUSES === ['inactive', 'active', 'cooling', 'review', 'restricted', 'paused'], 'Robot 六态冻结');
check(RobotRewardModel::STATES === ['candidate', 'held', 'pending_claim', 'claiming', 'claimed', 'expired_returned', 'review', 'reversed'], 'AI Reward 八态冻结');
check(RobotUpgradeOrderModel::STATUSES === ['pending', 'processing', 'completed', 'failed', 'cancelled'], 'RobotUpgradeOrder 五态冻结');
check(RobotModel::STATUS_ACTIVE === 'active', 'Robot STATUS_ACTIVE=active');
check(RobotRewardModel::STATE_CANDIDATE === 'candidate', 'Reward STATE_CANDIDATE=candidate');
check(RobotUpgradeOrderModel::STATUS_PENDING === 'pending', 'Upgrade STATUS_PENDING=pending');
echo "\n";

// ======================= 2. RobotRuleReader 键名 / 枚举 =======================
echo "[2] RobotRuleReader 键名 / 枚举（06 §4）\n";
check(RobotRuleReader::SOURCE_AVAILABLE === 'AVAILABLE', 'SOURCE_AVAILABLE');
check(RobotRuleReader::SOURCE_UNAVAILABLE === 'UNAVAILABLE', 'SOURCE_UNAVAILABLE');
check(RobotRuleReader::KEY_STANDARD_CAPACITY_RULE_VERSION === 'AI.standard_capacity_rule_version', 'KEY standard_capacity_rule_version');
check(RobotRuleReader::KEY_POWER_CAP_BY_ROBOT_LEVEL === 'AI.power_cap_by_robot_level', 'KEY power_cap_by_robot_level');
check(RobotRuleReader::KEY_UPGRADE_APT_REQUIREMENT === 'AI.upgrade_apt_requirement', 'KEY upgrade_apt_requirement');
check(RobotRuleReader::KEY_AI_REWARD_CLAIM_ENABLED === 'AI.ai_reward_claim_enabled', 'KEY ai_reward_claim_enabled');
check(RobotRuleReader::REASON_NO_ACTIVE_RELEASE === 'AI_RULE_NOT_ACTIVE', 'REASON_NO_ACTIVE_RELEASE');
echo "\n";

// ======================= 3. fail-closed 写路径 =======================
echo "[3] fail-closed 写路径（DEPENDENCY_UNAVAILABLE，不触 DB）\n";
$robotSvc = new RobotService();
expectDomainException(function () use ($robotSvc) {
    $robotSvc->start('R1', 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Robot start（R1）→ DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($robotSvc) {
    $robotSvc->stop('R1', 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Robot stop（R3）→ DEPENDENCY_UNAVAILABLE');

$rewardSvc = new RobotRewardService();
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->hold('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Reward hold（W1）→ DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->completeClaim('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Reward completeClaim（W4）→ DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->expire('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Reward expire（W5）→ DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->reverse('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Reward reverse（W9/W10）→ DEPENDENCY_UNAVAILABLE');

$upgradeSvc = new RobotUpgradeOrderService();
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->quote('R1', 2, 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Upgrade quote → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->submit('R1', 2, 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Upgrade submit → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 4. V2 错误码 HTTP 映射 =======================
echo "[4] V2 错误码 HTTP 映射（05 §7）\n";
check(ErrorDict::httpStatus(ErrorDict::DEPENDENCY_UNAVAILABLE) === 503, 'DEPENDENCY_UNAVAILABLE → 503');
check(ErrorDict::httpStatus(ErrorDict::OBJECT_VERSION_CONFLICT) === 409, 'OBJECT_VERSION_CONFLICT → 409');
check(ErrorDict::httpStatus(ErrorDict::QUOTE_EXPIRED) === 409, 'QUOTE_EXPIRED → 409');
check(ErrorDict::httpStatus(ErrorDict::INSUFFICIENT_POWER) === 422, 'INSUFFICIENT_POWER → 422');
check((new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE))->httpStatus() === 503, 'DomainException(DEPENDENCY_UNAVAILABLE).httpStatus() = 503');
echo "\n";

summary();
