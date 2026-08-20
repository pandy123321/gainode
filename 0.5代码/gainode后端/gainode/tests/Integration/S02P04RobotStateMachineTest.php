<?php

declare(strict_types=1);

/**
 * S02-P04 Robot / Reward / Upgrade 状态机 + 56 级规则读取器集成测试
 * （独立 CLI 脚本，无需 PHPUnit，SQLite in-memory）。
 *
 * 覆盖 07 §S02-P04 验证项：
 *   1. RobotRuleReader：无 Active Release → UNAVAILABLE；有 Release → 解析 56 级规则
 *   2. Robot 状态机：合法/非法转移 + CAS
 *   3. Reward 状态机：合法/非法转移
 *   4. Upgrade 状态机：pending→processing→completed/failed + cancelled
 *   5. fail-closed：start/stop/hold/completeClaim/expire/reverse/quote/submit
 *   6. Robot 只读投影：summary/detail/allowedActions（无 Release → allowed_actions=[]）
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use library\model\robot\RobotModel;
use library\model\robot\RobotRewardModel;
use library\model\robot\RobotUpgradeOrderModel;
use library\service\robot\RobotRewardService;
use library\service\robot\RobotRuleReader;
use library\service\robot\RobotService;
use library\service\robot\RobotUpgradeOrderService;
use support\exception\DomainException;

// ---- SQLite in-memory（命名 'mysql'，对齐 Model::$connection='mysql'）----
$capsule = new Capsule(Container::getInstance());
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'prefix'   => '',
], 'mysql');
$capsule->setAsGlobal();
$capsule->bootEloquent();

$schema = Capsule::connection('mysql')->getSchemaBuilder();

if (!$schema->hasTable('robots')) {
    $schema->create('robots', function ($table) {
        $table->string('robot_id', 32)->primary();
        $table->string('user_id', 32);
        $table->integer('level')->default(1);
        $table->string('status', 16);
        $table->string('standard_capacity', 64)->default('0');
        $table->text('capabilities')->nullable();
        $table->text('allowed_actions')->nullable();
        $table->string('idempotency_key', 64)->nullable();
        $table->string('rule_version', 64)->default('');
        $table->string('parameter_release_id', 32)->default('0');
        $table->string('snapshot_id', 32)->default('0');
        $table->integer('object_version')->default(0);
        $table->integer('created_time')->default(0);
        $table->integer('updated_time')->default(0);
    });
}

if (!$schema->hasTable('robot_rewards')) {
    $schema->create('robot_rewards', function ($table) {
        $table->string('reward_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('robot_id', 32);
        $table->string('period', 32)->default('');
        $table->string('standard_capacity', 64)->default('0');
        $table->string('daily_reward_coefficient', 64)->default('0');
        $table->string('quantity_apt', 64)->default('0');
        $table->string('state', 16);
        $table->string('eligibility_snapshot_id', 32)->default('0');
        $table->string('budget_snapshot_id', 32)->default('0');
        $table->string('claim_id', 32)->default('0');
        $table->string('ledger_entry_id', 32)->default('0');
        $table->integer('expires_at')->default(0);
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32)->default('0');
        $table->string('rule_version', 64)->default('');
        $table->integer('object_version')->default(0);
        $table->integer('created_time')->default(0);
        $table->integer('updated_time')->default(0);
    });
}

if (!$schema->hasTable('robot_upgrade_orders')) {
    $schema->create('robot_upgrade_orders', function ($table) {
        $table->string('upgrade_order_id', 32)->primary();
        $table->string('robot_id', 32);
        $table->string('user_id', 32);
        $table->integer('from_level')->default(1);
        $table->integer('to_level')->default(1);
        $table->string('apt_cost', 64)->default('0');
        $table->string('status', 16);
        $table->string('power_cap_after', 64)->default('0');
        $table->text('capacities_after')->nullable();
        $table->integer('cooling_end_at')->default(0);
        $table->string('review_case_id', 32)->default('0');
        $table->string('approval_id', 32)->default('0');
        $table->string('ledger_entry_id', 32)->default('0');
        $table->string('rule_version', 64)->default('');
        $table->string('parameter_release_id', 32)->default('0');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32)->default('0');
        $table->integer('object_version')->default(0);
        $table->integer('created_time')->default(0);
        $table->integer('updated_time')->default(0);
    });
}

if (!$schema->hasTable('audit_events')) {
    $schema->create('audit_events', function ($table) {
        $table->string('audit_event_id', 32)->primary();
        $table->string('event_code', 64);
        $table->string('actor_id', 32);
        $table->string('actor_role', 32);
        $table->string('target_object_type', 64);
        $table->string('target_object_id', 32);
        $table->string('before_snapshot_type', 32)->default('');
        $table->string('before_snapshot_id', 32)->default('0');
        $table->string('after_snapshot_type', 32)->default('');
        $table->string('after_snapshot_id', 32)->default('0');
        $table->string('outcome', 16);
        $table->string('reason_code', 64)->default('');
        $table->string('request_id', 64)->default('');
        $table->string('approval_id', 32)->default('0');
        $table->string('case_id', 32)->default('0');
        $table->integer('created_time')->default(0);
    });
}

if (!$schema->hasTable('parameter_releases')) {
    $schema->create('parameter_releases', function ($table) {
        $table->string('release_id', 32)->primary();
        $table->text('parameter_keys')->nullable();
        $table->string('status', 24);
        $table->string('draft_version', 64)->default('');
        $table->string('approved_by', 32)->default('0');
        $table->integer('scheduled_at')->default(0);
        $table->integer('activated_at')->default(0);
        $table->integer('paused_at')->default(0);
        $table->integer('rolled_back_at')->default(0);
        $table->integer('archived_at')->default(0);
        $table->string('monitoring_job_id', 32)->default('0');
        $table->string('snapshot_id', 32)->default('0');
        $table->string('case_id', 32)->default('0');
        $table->text('audit_event_ids')->nullable();
        $table->integer('object_version')->default(0);
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32)->default('0');
        $table->integer('created_time')->default(0);
        $table->integer('updated_time')->default(0);
    });
}

if (!$schema->hasTable('parameter_snapshots')) {
    $schema->create('parameter_snapshots', function ($table) {
        $table->string('snapshot_id', 32)->primary();
        $table->string('release_id', 32);
        $table->text('parameter_keys')->nullable();
        $table->text('parameter_values')->nullable();
        $table->string('version', 64)->default('');
        $table->string('created_by', 32)->default('0');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32)->default('0');
        $table->integer('created_time')->default(0);
    });
}

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
echo "S02-P04 robot / reward / upgrade state machine test\n";
echo "=====================================================\n\n";

$robotSvc = new RobotService();
$rewardSvc = new RobotRewardService();
$upgradeSvc = new RobotUpgradeOrderService();
$reader = new RobotRuleReader();

// ======================= 1. 无 Active Release → UNAVAILABLE =======================
echo "[1] RobotRuleReader：无 Active Release → UNAVAILABLE\n";
$snap = $reader->getRuleSnapshot();
check($snap['source_status'] === RobotRuleReader::SOURCE_UNAVAILABLE, 'source_status=UNAVAILABLE');
check($reader->isAvailable() === false, 'isAvailable()=false');
check($reader->getPowerCap(1) === null, 'getPowerCap(1)=null（无规则）');
check($reader->getClaimEnabled() === false, 'getClaimEnabled()=false（缺省）');
check($snap['reason_code'] === RobotRuleReader::REASON_NO_ACTIVE_RELEASE, 'reason_code=AI_RULE_NOT_ACTIVE');
echo "\n";

// ======================= 2. 有 Active Release → 解析 56 级规则 =======================
echo "[2] RobotRuleReader：有 Active Release → 解析\n";
Capsule::connection('mysql')->table('parameter_snapshots')->insert([
    'snapshot_id'      => 'S1',
    'release_id'       => 'REL1',
    'parameter_values' => json_encode([
        'AI.standard_capacity_rule_version'       => 'EM_V4.1.1',
        'AI.standard_capacity_by_level'           => ['1' => '50', '2' => '52', '3' => '55', '19' => '547', '20' => '675', '56' => '743720'],
        'AI.upgrade_apt_requirement'              => ['1' => '0', '2' => '200', '20' => '9200', '56' => '5200000'],
        'AI.upgrade_cooldown_by_range'            => [
            ['from' => 1, 'to' => 20, 'days' => 0],
            ['from' => 21, 'to' => 30, 'days' => 1],
            ['from' => 31, 'to' => 40, 'days' => 3],
            ['from' => 41, 'to' => 50, 'days' => 5],
            ['from' => 51, 'to' => 56, 'days' => 6],
        ],
        'AI.power_sell_consumption_ratio'         => '0.5',
        'AI.power_daily_restore_rate_by_range'    => [
            ['from' => 1, 'to' => 10, 'rate' => '0.10'],
            ['from' => 11, 'to' => 20, 'rate' => '0.12'],
            ['from' => 21, 'to' => 30, 'rate' => '0.14'],
            ['from' => 31, 'to' => 40, 'rate' => '0.16'],
            ['from' => 41, 'to' => 50, 'rate' => '0.18'],
            ['from' => 51, 'to' => 56, 'rate' => '0.20'],
        ],
        'AI.upgrade_p_discount_by_level'          => ['1' => '0.02', '2' => '0.04', '6' => '0.12'],
        'AI.ai_reward_claim_window_base_hours'    => '24',
        'AI.ai_reward_claim_window_per_level_hours' => '1',
        'AI.power_cap_by_robot_level'             => ['1' => '100', '2' => '200', '56' => '5600'],
        'AI.power_cap_factor'                     => '0.5',
        'AI.ai_reward_budget_cap'                 => '100000',
        'AI.ai_reward_claim_enabled'              => false,
        'AI.daily_yield_coefficient_source'       => 'server',
        'AI.daily_yield_coefficient_precision'    => '0',
    ]),
    'version'     => 'EM_V4.1.1',
    'created_time' => time(),
]);
Capsule::connection('mysql')->table('parameter_releases')->insert([
    'release_id'   => 'REL1',
    'status'       => 'active',
    'snapshot_id'  => 'S1',
    'activated_at' => time(),
    'created_time' => time(),
    'updated_time' => time(),
]);

$snap = $reader->getRuleSnapshot();
check($snap['source_status'] === RobotRuleReader::SOURCE_AVAILABLE, 'source_status=AVAILABLE');
check($snap['rule_version'] === 'EM_V4.1.1', 'rule_version=EM_V4.1.1');
check($snap['parameter_release_id'] === 'REL1', 'parameter_release_id=REL1');
check($snap['snapshot_id'] === 'S1', 'snapshot_id=S1');
check($snap['power_cap_by_level'][1] === '100', 'power_cap_by_level[1]=100');
check($snap['power_cap_by_level'][56] === '5600', 'power_cap_by_level[56]=5600（56 级边界）');
check($snap['ai_reward_budget_cap'] === '100000', 'ai_reward_budget_cap=100000');
check($snap['ai_reward_claim_enabled'] === false, 'ai_reward_claim_enabled=false（JSON false）');
check($snap['daily_yield_coefficient_precision'] === '0', 'daily_yield_coefficient_precision=0（0 合法）');
check($reader->getPowerCap(1) === '100', 'getPowerCap(1)=100（显式映射优先）');
check($reader->getPowerCap(3) === '27.5', 'getPowerCap(3)=27.5（派生：55×0.5，无显式映射）');
check($reader->getPowerCap(19) === '273.5', 'getPowerCap(19)=273.5（派生：547×0.5）');
check($reader->getPowerCap(56) === '5600', 'getPowerCap(56)=5600（显式映射优先）');
check($reader->getClaimEnabled() === false, 'getClaimEnabled()=false');

// 新增 56 级容量/升级成本/冷却/算力/优惠/领取有效期 getter
check($reader->getStandardCapacity(1) === '50', 'getStandardCapacity(1)=50');
check($reader->getStandardCapacity(20) === '675', 'getStandardCapacity(20)=675');
check($reader->getStandardCapacity(56) === '743720', 'getStandardCapacity(56)=743720');
check($reader->getUpgradeCost(1) === '0', 'getUpgradeCost(1)=0');
check($reader->getUpgradeCost(2) === '200', 'getUpgradeCost(2)=200');
check($reader->getUpgradeCost(20) === '9200', 'getUpgradeCost(20)=9200');
check($reader->getUpgradeCost(56) === '5200000', 'getUpgradeCost(56)=5200000');
check($reader->getUpgradeCooldownDays(10) === 0, 'getUpgradeCooldownDays(10)=0');
check($reader->getUpgradeCooldownDays(25) === 1, 'getUpgradeCooldownDays(25)=1');
check($reader->getUpgradeCooldownDays(35) === 3, 'getUpgradeCooldownDays(35)=3');
check($reader->getUpgradeCooldownDays(45) === 5, 'getUpgradeCooldownDays(45)=5');
check($reader->getUpgradeCooldownDays(56) === 6, 'getUpgradeCooldownDays(56)=6');
check($reader->getPowerSellConsumptionRatio() === '0.5', 'getPowerSellConsumptionRatio()=0.5');
check($reader->getPowerDailyRestoreRate(5) === '0.10', 'getPowerDailyRestoreRate(5)=0.10');
check($reader->getPowerDailyRestoreRate(18) === '0.12', 'getPowerDailyRestoreRate(18)=0.12');
check($reader->getPowerDailyRestoreRate(56) === '0.20', 'getPowerDailyRestoreRate(56)=0.20');
check($reader->getUpgradePDiscount(2) === '0.04', 'getUpgradePDiscount(2)=0.04');
check($reader->getUpgradePDiscount(6) === '0.12', 'getUpgradePDiscount(6)=0.12');
check($reader->getClaimWindowHours(1) === 24, 'getClaimWindowHours(1)=24');
check($reader->getClaimWindowHours(20) === 43, 'getClaimWindowHours(20)=43');
check($reader->getClaimWindowHours(56) === 79, 'getClaimWindowHours(56)=79');
echo "\n";

// ======================= 3. Robot 状态机 =======================
echo "[3] Robot 状态机（active↔cooling，审计 + CAS）\n";
$robotSvc->create([
    'robot_id'          => 'R1',
    'user_id'           => 'U1',
    'level'             => 1,
    'status'            => RobotModel::STATUS_ACTIVE,
    'standard_capacity' => '100',
    'object_version'    => 0,
]);
$robotSvc->enterCooling('R1', 'SYS', 'SYSTEM');
check((string) $robotSvc->get('R1')->status === RobotModel::STATUS_COOLING, 'enterCooling → cooling');
check((int) $robotSvc->get('R1')->object_version === 1, 'object_version → 1');

expectDomainException(function () use ($robotSvc) {
    $robotSvc->enterCooling('R1', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, '重复 enterCooling（cooling→cooling）→ OBJECT_VERSION_CONFLICT');

$robotSvc->exitCooling('R1', 'SYS', 'SYSTEM');
check((string) $robotSvc->get('R1')->status === RobotModel::STATUS_ACTIVE, 'exitCooling → active');

expectDomainException(function () use ($robotSvc) {
    $robotSvc->exitCooling('R1', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'active 态 exitCooling → OBJECT_VERSION_CONFLICT');

// R1 start / R3 stop：纯状态转移（Owner 决策 CR-20260818-003，不消耗/释放 Power）
$robotSvc->stop('R1', 'U1', 'END_USER');
check((string) $robotSvc->get('R1')->status === RobotModel::STATUS_INACTIVE, 'stop（active→inactive）纯状态转移');
$robotSvc->start('R1', 'U1', 'END_USER');
check((string) $robotSvc->get('R1')->status === RobotModel::STATUS_ACTIVE, 'start（inactive→active）纯状态转移');

// active 态 start 非法（start 仅 inactive→active）
expectDomainException(function () use ($robotSvc) {
    $robotSvc->start('R1', 'U1', 'END_USER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'active 态 start → OBJECT_VERSION_CONFLICT');

// inactive 态 stop 非法（stop 仅 active→inactive）
$robotSvc->stop('R1', 'U1', 'END_USER');
expectDomainException(function () use ($robotSvc) {
    $robotSvc->stop('R1', 'U1', 'END_USER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'inactive 态 stop → OBJECT_VERSION_CONFLICT');
$robotSvc->start('R1', 'U1', 'END_USER'); // 恢复 active，供后续测试
echo "\n";

// ======================= 4. Reward 状态机 =======================
echo "[4] Reward 状态机（held→pending_claim→claiming）\n";
$rewardSvc->create([
    'reward_id'               => 'RW1',
    'user_id'                 => 'U1',
    'robot_id'                => 'R1',
    'period'                  => '2026-08-16',
    'standard_capacity'       => '100',
    'daily_reward_coefficient' => '0',
    'quantity_apt'            => '0',
    'state'                   => RobotRewardModel::STATE_HELD,
    'object_version'          => 0,
]);
$rewardSvc->openClaimWindow('RW1', time() + 3600, 'SYS', 'SYSTEM');
check((string) $rewardSvc->get('RW1')->state === RobotRewardModel::STATE_PENDING_CLAIM, 'openClaimWindow → pending_claim');
check((int) $rewardSvc->get('RW1')->expires_at > time(), 'expires_at 已回写');

expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->openClaimWindow('RW1', time() + 3600, 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, '重复 openClaimWindow → OBJECT_VERSION_CONFLICT');

$rewardSvc->startClaim('RW1', 'SYS', 'SYSTEM');
check((string) $rewardSvc->get('RW1')->state === RobotRewardModel::STATE_CLAIMING, 'startClaim → claiming');

expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->startClaim('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'claiming 态 startClaim → OBJECT_VERSION_CONFLICT');

// 新 held reward，expires_at 过去 → VALIDATION_ERROR（在状态校验之前拦截）
$rewardSvc->create([
    'reward_id'                => 'RW2',
    'user_id'                  => 'U1',
    'robot_id'                 => 'R1',
    'period'                   => '2026-08-17',
    'standard_capacity'        => '100',
    'daily_reward_coefficient' => '0',
    'quantity_apt'             => '0',
    'state'                    => RobotRewardModel::STATE_HELD,
    'object_version'           => 0,
]);
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->openClaimWindow('RW2', time() - 10, 'SYS', 'SYSTEM');
}, ErrorDict::VALIDATION_ERROR, 'expires_at 过去 → VALIDATION_ERROR');
echo "\n";

// ======================= 5. Upgrade 状态机 =======================
echo "[5] Upgrade 状态机（pending→processing→completed / failed + cancelled）\n";
$upgradeSvc->create([
    'upgrade_order_id' => 'UO1',
    'robot_id'         => 'R1',
    'user_id'          => 'U1',
    'from_level'       => 1,
    'to_level'         => 2,
    'apt_cost'         => '0',
    'status'           => RobotUpgradeOrderModel::STATUS_PENDING,
    'object_version'   => 0,
]);
$upgradeSvc->process('UO1', 'SYS', 'SYSTEM');
check((string) $upgradeSvc->get('UO1')->status === RobotUpgradeOrderModel::STATUS_PROCESSING, 'process → processing');
$upgradeSvc->complete('UO1', 'SYS', 'SYSTEM');
check((string) $upgradeSvc->get('UO1')->status === RobotUpgradeOrderModel::STATUS_COMPLETED, 'complete → completed');
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->complete('UO1', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'completed 态 complete → OBJECT_VERSION_CONFLICT');

// 失败可重试：UO2 pending→processing→failed→processing
$upgradeSvc->create([
    'upgrade_order_id' => 'UO2',
    'robot_id'         => 'R1',
    'user_id'          => 'U1',
    'from_level'       => 1,
    'to_level'         => 2,
    'apt_cost'         => '0',
    'status'           => RobotUpgradeOrderModel::STATUS_PENDING,
    'object_version'   => 0,
]);
$upgradeSvc->process('UO2', 'SYS', 'SYSTEM');
$upgradeSvc->fail('UO2', 'SYS', 'SYSTEM');
check((string) $upgradeSvc->get('UO2')->status === RobotUpgradeOrderModel::STATUS_FAILED, 'fail → failed');
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->process('UO2', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'failed 态直接 process → OBJECT_VERSION_CONFLICT（需经 retry 语义）');

// 取消：UO3 pending→cancelled
$upgradeSvc->create([
    'upgrade_order_id' => 'UO3',
    'robot_id'         => 'R1',
    'user_id'          => 'U1',
    'from_level'       => 1,
    'to_level'         => 2,
    'apt_cost'         => '0',
    'status'           => RobotUpgradeOrderModel::STATUS_PENDING,
    'object_version'   => 0,
]);
$upgradeSvc->cancel('UO3', 'U1', 'END_USER');
check((string) $upgradeSvc->get('UO3')->status === RobotUpgradeOrderModel::STATUS_CANCELLED, 'cancel → cancelled');
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->cancel('UO3', 'U1', 'END_USER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'cancelled 态 cancel → OBJECT_VERSION_CONFLICT');
echo "\n";

// ======================= 6. fail-closed（经济写） =======================
echo "[6] fail-closed（经济写路径）\n";
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->hold('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'hold → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->completeClaim('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'completeClaim → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->expire('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'expire → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($rewardSvc) {
    $rewardSvc->reverse('RW1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'reverse → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->submit('R1', 2, 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'submit → DEPENDENCY_UNAVAILABLE');

// quote 已打开（依赖 56 级升级成本已批准）：R1 level=1 → 报价升级到 Lv.2
$quote = $upgradeSvc->quote('R1', 2, 'U1', 'END_USER');
check($quote['current_level'] === 1, 'quote.current_level=1');
check($quote['target_level'] === 2, 'quote.target_level=2');
check($quote['apt_cost'] === '200', 'quote.apt_cost=200（Lv.2 基准升级费用）');
check($quote['capability_diff']['standard_capacity']['from'] === '50', 'quote 产能 from=50');
check($quote['capability_diff']['standard_capacity']['to'] === '52', 'quote 产能 to=52');
check($quote['cooldown'] === 0, 'quote.cooldown=0（Lv.1-20 无冷却）');
check($quote['power_limit_diff']['from'] === '100', 'quote.power_limit_diff.from=100（显式映射）');
check($quote['power_limit_diff']['to'] === '200', 'quote.power_limit_diff.to=200（显式映射）');
check($quote['power_limit_diff']['diff'] === '100.000000000000000000', 'quote.power_limit_diff.diff=100');
check($quote['rule_version'] === 'EM_V4.1.1', 'quote.rule_version=EM_V4.1.1');
check($quote['parameter_release_id'] === 'REL1', 'quote.parameter_release_id=REL1');

// 越界/非法报价
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->quote('R1', 1, 'U1', 'END_USER');
}, ErrorDict::VALIDATION_ERROR, 'quote(to_level=1) → VALIDATION_ERROR（不高于当前级）');
expectDomainException(function () use ($upgradeSvc) {
    $upgradeSvc->quote('R1', 57, 'U1', 'END_USER');
}, ErrorDict::VALIDATION_ERROR, 'quote(to_level=57) → VALIDATION_ERROR（越界）');
echo "\n";

// ======================= 7. Robot 只读投影 =======================
echo "[7] Robot 只读投影（summary / detail / allowedActions）\n";
$summary = $robotSvc->summary('U1');
check(count($summary['robots']) === 1, 'summary.robots 数量=1');
check($summary['source_status'] === RobotRuleReader::SOURCE_AVAILABLE, 'summary.source_status=AVAILABLE');

$detail = $robotSvc->detail('R1');
check($detail['robot_id'] === 'R1', 'detail.robot_id=R1');
check((int) $detail['level'] === 1, 'detail.level=1');
check($detail['source_status'] === RobotRuleReader::SOURCE_AVAILABLE, 'detail.source_status=AVAILABLE');

$actions = $robotSvc->allowedActions('R1');
check($actions['allowed_actions'] === [], 'active 态 allowed_actions=[]（授权模型占位，恒空）');
check(in_array('stop', $actions['blocked_actions'], true), 'blocked_actions 含 stop');
check(in_array('upgrade', $actions['blocked_actions'], true), 'blocked_actions 含 upgrade');

expectDomainException(function () use ($robotSvc) {
    $robotSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'detail(不存在) → VALIDATION_ERROR');
echo "\n";

// ======================= 8. 无 Release 时投影 fail-closed =======================
echo "[8] 无 Active Release 时投影 fail-closed\n";
// 移除 active release
Capsule::connection('mysql')->table('parameter_releases')->where('release_id', 'REL1')->update(['status' => 'archived']);
$reader2 = new RobotRuleReader();
check($reader2->isAvailable() === false, '移除 active release 后 isAvailable()=false');
$actions2 = $robotSvc->allowedActions('R1');
check($actions2['source_status'] === RobotRuleReader::SOURCE_UNAVAILABLE, 'allowedActions.source_status=UNAVAILABLE');
check($actions2['allowed_actions'] === [], '无 Release 时 allowed_actions=[]');
check($actions2['reason_code'] === RobotRuleReader::REASON_NO_ACTIVE_RELEASE, 'reason_code=AI_RULE_NOT_ACTIVE');
echo "\n";

summary();
