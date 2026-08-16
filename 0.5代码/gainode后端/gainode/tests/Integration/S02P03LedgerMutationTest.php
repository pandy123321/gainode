<?php

declare(strict_types=1);

/**
 * S02-P03 Ledger / AptAccount 经济事务集成测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * SQLite in-memory（命名 'mysql'），表结构对齐 MC1 冻结 DDL（apt_accounts /
 * apt_ledger_entries 含 object_version）+ MC2 audit_events + power_positions。
 * 覆盖统一 Economic Mutation Lock 的：
 *   1. L1 post + 守恒（balance == Σ signed_delta(posted)）
 *   2. exactly-once（idempotency_key）
 *   3. negative balance（INSUFFICIENT_APT）
 *   4. CAS 冲突（OBJECT_VERSION_CONFLICT）
 *   5. L2 cancel（pending→reversed，无经济 reversal）
 *   6. L3 reverse（posted→reversed，追加 LEDGER_REVERSAL + 余额归位）
 *   7. fail-closed（dispute / resolveDispute / Power consume）
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use library\model\ledger\AptAccountModel;
use library\model\ledger\AptLedgerEntryModel;
use library\service\ledger\AptAccountService;
use library\service\ledger\LedgerService;
use library\service\power\PowerPositionService;
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

if (!$schema->hasTable('apt_accounts')) {
    $schema->create('apt_accounts', function ($table) {
        $table->string('account_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('balance_apt_i', 64)->default('0');
        $table->string('balance_apt_c', 64)->default('0');
        $table->string('frozen_apt_i', 64)->default('0');
        $table->string('frozen_apt_c', 64)->default('0');
        $table->string('total_earned_apt', 64)->default('0');
        $table->string('total_spent_apt', 64)->default('0');
        $table->string('last_ledger_entry_id', 32)->default('0');
        $table->string('rule_version', 64)->default('');
        $table->string('snapshot_id', 32)->default('0');
        $table->integer('object_version')->default(0);
        $table->integer('created_time')->default(0);
        $table->integer('updated_time')->default(0);
    });
}

if (!$schema->hasTable('apt_ledger_entries')) {
    $schema->create('apt_ledger_entries', function ($table) {
        $table->string('ledger_entry_id', 32)->primary();
        $table->string('account_id', 32);
        $table->string('asset', 16);
        $table->string('quantity', 64);
        $table->integer('entry_direction');
        $table->string('entry_type', 64);
        $table->string('state', 16);
        $table->string('source_object_type', 64)->default('');
        $table->string('source_object_id', 32)->default('0');
        $table->string('journal_batch_id', 32)->default('0');
        $table->string('reversal_of', 32)->default('0');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('rule_version', 64)->default('');
        $table->string('snapshot_id', 32)->default('0');
        $table->string('audit_event_id', 32)->default('0');
        $table->integer('object_version')->default(0);
        $table->integer('created_time')->default(0);
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

if (!$schema->hasTable('power_positions')) {
    $schema->create('power_positions', function ($table) {
        $table->string('user_id', 32)->primary();
        $table->string('available', 32)->default('0');
        $table->string('frozen', 32)->default('0');
        $table->string('consumed_period', 32)->default('0');
        $table->string('released_period', 32)->default('0');
        $table->string('recovering', 32)->default('0');
        $table->string('limit', 32)->default('0');
        $table->integer('power_cap_source_robot_level')->default(0);
        $table->integer('last_restore_at')->default(0);
        $table->integer('next_restore_at')->default(0);
        $table->string('rule_version', 64)->default('');
        $table->string('parameter_release_id', 32)->default('0');
        $table->integer('object_version')->default(0);
        $table->integer('created_time')->default(0);
        $table->integer('updated_time')->default(0);
    });
}

/**
 * 断言闭包抛出 DomainException 且 resultCode 命中。
 */
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

/**
 * 金额断言（bcmath，忽略 decimal(36,18) 尾零）。
 */
function assertBal($actual, string $expected, string $label): void
{
    check(bccomp((string) $actual, $expected, 18) === 0, "{$label}（actual={$actual} expected={$expected}）");
}

echo "=====================================================\n";
echo "S02-P03 ledger / account economic mutation test\n";
echo "=====================================================\n\n";

$accountSvc = new AptAccountService();
$ledgerSvc = new LedgerService();

// 建账户
$accountSvc->create([
    'account_id'         => 'A1',
    'user_id'            => 'U1',
    'balance_apt_i'      => '0',
    'balance_apt_c'      => '0',
    'frozen_apt_i'       => '0',
    'frozen_apt_c'       => '0',
    'total_earned_apt'   => '0',
    'total_spent_apt'    => '0',
    'last_ledger_entry_id' => '0',
    'rule_version'       => '',
    'snapshot_id'        => '0',
    'object_version'     => 0,
    'created_time'       => time(),
    'updated_time'       => time(),
]);

// ======================= 1. append + post（L1）+ 守恒 =======================
echo "[1] append + post（L1）+ 守恒\n";
$e1 = $ledgerSvc->append([
    'account_id'      => 'A1',
    'quantity'        => '100',
    'entry_direction' => AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT,
    'entry_type'      => 'TEST_CREDIT',
    'idempotency_key' => 'IK-1',
]);
check((string) $e1->state === AptLedgerEntryModel::STATE_PENDING, 'append → pending');
check((string) $e1->quantity === '100', 'quantity 归一化为字符串');

$ledgerSvc->post((string) $e1->ledger_entry_id, 'sys', 'SYSTEM');
$posted = $ledgerSvc->get((string) $e1->ledger_entry_id);
check((string) $posted->state === AptLedgerEntryModel::STATE_POSTED, 'post → posted');
check((int) $posted->object_version === 1, 'ledger object_version → 1');
check((string) $posted->audit_event_id !== '0', 'audit_event_id 已回写');

$acc1 = $accountSvc->get('A1');
assertBal($acc1->balance_apt_i, '100', 'balance=100 后 CREDIT');
assertBal($acc1->total_earned_apt, '100', 'total_earned=100');
check((int) $acc1->object_version === 1, 'account object_version → 1');

// 守恒断言
$sum = '0';
foreach ($ledgerSvc->getByAccount('A1') as $en) {
    if ((string) $en->state === AptLedgerEntryModel::STATE_POSTED) {
        $sum = bcadd($sum, bcmul((string) $en->quantity, (string) $en->entry_direction, 18), 18);
    }
}
check(bccomp($sum, (string) $acc1->balance_apt_i, 18) === 0, '守恒：balance == Σ signed_delta(posted)');
echo "\n";

// ======================= 2. DEBIT + exactly-once =======================
echo "[2] DEBIT + exactly-once\n";
$e2 = $ledgerSvc->append([
    'account_id'      => 'A1',
    'quantity'        => '30',
    'entry_direction' => AptLedgerEntryModel::ENTRY_DIRECTION_DEBIT,
    'entry_type'      => 'TEST_DEBIT',
    'idempotency_key' => 'IK-2',
]);
$ledgerSvc->post((string) $e2->ledger_entry_id, 'sys', 'SYSTEM');
$acc1 = $accountSvc->get('A1');
assertBal($acc1->balance_apt_i, '70', 'balance=70 后 DEBIT 30');
assertBal($acc1->total_spent_apt, '30', 'total_spent=30');

expectDomainException(function () use ($ledgerSvc) {
    $ledgerSvc->append([
        'account_id'      => 'A1',
        'quantity'        => '5',
        'entry_direction' => AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT,
        'entry_type'      => 'TEST_CREDIT',
        'idempotency_key' => 'IK-2',
    ]);
}, ErrorDict::IDEMPOTENCY_CONFLICT, '同 idempotency_key 二次 append → IDEMPOTENCY_CONFLICT');
echo "\n";

// ======================= 3. negative balance =======================
echo "[3] negative balance（INSUFFICIENT_APT）\n";
$e3 = $ledgerSvc->append([
    'account_id'      => 'A1',
    'quantity'        => '200',
    'entry_direction' => AptLedgerEntryModel::ENTRY_DIRECTION_DEBIT,
    'entry_type'      => 'TEST_DEBIT',
    'idempotency_key' => 'IK-3',
]);
expectDomainException(function () use ($ledgerSvc, $e3) {
    $ledgerSvc->post((string) $e3->ledger_entry_id, 'sys', 'SYSTEM');
}, ErrorDict::INSUFFICIENT_APT, 'DEBIT 200 超余额 70 → INSUFFICIENT_APT');

$stillPending = $ledgerSvc->get((string) $e3->ledger_entry_id);
check((string) $stillPending->state === AptLedgerEntryModel::STATE_PENDING, '失败后分录仍 pending（事务回滚）');
$acc1 = $accountSvc->get('A1');
assertBal($acc1->balance_apt_i, '70', '余额未变（回滚）');
echo "\n";

// ======================= 4. CAS 冲突 =======================
echo "[4] CAS 冲突（OBJECT_VERSION_CONFLICT）\n";
expectDomainException(function () use ($accountSvc) {
    $accountSvc->applyEntryEffect('A1', '10', AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT, 999, 'E-X');
}, ErrorDict::OBJECT_VERSION_CONFLICT, '陈旧 object_version=999 → OBJECT_VERSION_CONFLICT');
echo "\n";

// ======================= 5. L2 cancel（pending→reversed）=======================
echo "[5] L2 cancel（pending→reversed，无经济 reversal）\n";
$e4 = $ledgerSvc->append([
    'account_id'      => 'A1',
    'quantity'        => '50',
    'entry_direction' => AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT,
    'entry_type'      => 'TEST_CREDIT',
    'idempotency_key' => 'IK-4',
]);
$ledgerSvc->cancel((string) $e4->ledger_entry_id, 'ops', 'OPS_OPERATOR');
check((string) $ledgerSvc->get((string) $e4->ledger_entry_id)->state === AptLedgerEntryModel::STATE_REVERSED, 'cancel → reversed');
$acc1 = $accountSvc->get('A1');
assertBal($acc1->balance_apt_i, '70', 'L2 取消无经济效果（余额仍 70）');
$reversalOf4 = $ledgerSvc->fetch(['reversal_of' => (string) $e4->ledger_entry_id]);
check(empty($reversalOf4), 'L2 不追加经济 reversal 分录（reversal_of 无引用）');
echo "\n";

// ======================= 6. L3 reverse（posted→reversed）=======================
echo "[6] L3 reverse（posted→reversed，追加 LEDGER_REVERSAL）\n";
$e5 = $ledgerSvc->append([
    'account_id'      => 'A1',
    'quantity'        => '50',
    'entry_direction' => AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT,
    'entry_type'      => 'TEST_CREDIT',
    'idempotency_key' => 'IK-5',
]);
$ledgerSvc->post((string) $e5->ledger_entry_id, 'sys', 'SYSTEM');
$acc1 = $accountSvc->get('A1');
assertBal($acc1->balance_apt_i, '120', 'post 后余额 120');

$reversal = $ledgerSvc->reverse((string) $e5->ledger_entry_id, 'ops', 'OPS_OPERATOR');
check((string) $ledgerSvc->get((string) $e5->ledger_entry_id)->state === AptLedgerEntryModel::STATE_REVERSED, '原分录 → reversed');
check((string) $reversal->entry_type === AptLedgerEntryModel::ENTRY_TYPE_LEDGER_REVERSAL, 'reversal entry_type=LEDGER_REVERSAL');
check((int) $reversal->entry_direction === AptLedgerEntryModel::ENTRY_DIRECTION_DEBIT, 'reversal direction=-原（CREDIT→DEBIT）');
check((string) $reversal->reversal_of === (string) $e5->ledger_entry_id, 'reversal_of 指向原分录');
check((string) $reversal->state === AptLedgerEntryModel::STATE_POSTED, 'reversal 分录为 posted（立即生效）');
$acc1 = $accountSvc->get('A1');
assertBal($acc1->balance_apt_i, '70', 'L3 冲正后余额归位 70');
echo "\n";

// ======================= 7. fail-closed =======================
echo "[7] fail-closed（dispute / resolveDispute / Power）\n";
expectDomainException(function () use ($ledgerSvc, $e2) {
    $ledgerSvc->dispute((string) $e2->ledger_entry_id, 'ops', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'dispute → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($ledgerSvc, $e2) {
    $ledgerSvc->resolveDispute((string) $e2->ledger_entry_id, 'ops', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'resolveDispute → DEPENDENCY_UNAVAILABLE');

$powerSvc = new PowerPositionService();
expectDomainException(function () use ($powerSvc) {
    $powerSvc->consume('U1', '10');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Power consume → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($powerSvc) {
    $powerSvc->recover('U1', '10');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Power recover → DEPENDENCY_UNAVAILABLE');
echo "\n";

summary();
