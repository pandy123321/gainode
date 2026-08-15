<?php

declare(strict_types=1);

/**
 * Ledger append-only 回归测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 覆盖 apt_ledger_entries 的 append-only 机械强制的三类证据：
 *   1. Builder injection：AptLedgerEntryModel::query() 必须返回 AptLedgerEntryAppendOnlyBuilder。
 *   2. Mutation matrix：INSERT / read 允许；Model 实例、Eloquent Builder（显式覆写 + __call 兜底）、
 *      DAO 三层的全部 destructive mutation 一律 fail-closed 抛 RunException，且拒绝后数据不变。
 *   3. Dependency mutation-surface contract：以当前锁定 illuminate/database 版本为输入，
 *      枚举 Query\Builder 公开 mutation method 与 disposition 表（DENY / ALLOW_APPEND）对照，
 *      出现未 disposition 的新 write method 即 FAIL（要求人工复核），不假设升级自动安全。
 *
 * 运行：php tests/ledger/LedgerAppendOnlyMutationMatrixTest.php
 *
 * 说明：测试使用 SQLite in-memory 连接（命名为 'mysql'，匹配 AptLedgerEntryModel::$connection='mysql'），
 * 不接触真实 MySQL / .env，不修改 MC1 Frozen DDL。
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use library\dao\ledger\AptLedgerEntryDao;
use library\model\ledger\AptLedgerEntryAppendOnlyBuilder;
use library\model\ledger\AptLedgerEntryModel;
use support\exception\RunException;

// ---------------------------------------------------------------------------
// Disposition 表（deny set 的「当前锁定 v10.38.1 已审核」单一事实来源，供 contract 测试共用）
// ---------------------------------------------------------------------------
// DENY：destructive mutation（必须被 fail-closed 阻断）。小写方法名。
const LEDGER_DENY = [
    // Eloquent Builder 层
    'update',
    'upsert',
    'touch',
    'increment',
    'decrement',
    'delete',
    'forcedelete',
    // Query Builder 层（经 Eloquent Builder __call 转发）
    'updateorinsert',
    'truncate',
    'incrementeach',
    'decrementeach',
    'updatefrom', // PostgreSQL UPDATE ... FROM ...；当前 MySQL 不可执行，语义完整仍 deny
];

// ALLOW_APPEND：insert-only（追加）方法，不得出现在 deny set 中。
const LEDGER_ALLOW_APPEND = [
    'insert',
    'insertgetid',
    'insertorignore',
    'insertusing',
];

// Query\Builder mutation-surface contract 的 write 前缀（用于检测未来升级新增的 write method）。
const QUERY_WRITE_PREFIXES = ['insert', 'update', 'upsert', 'delete', 'increment', 'decrement', 'truncate'];

// ---------------------------------------------------------------------------
// 最小引导：SQLite in-memory（命名 'mysql'）
// ---------------------------------------------------------------------------
$capsule = new Capsule(Container::getInstance());
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
], 'mysql');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// 建表（列名/类型与 MC1 Frozen DDL 对齐；SQLite 对类型宽松）
Capsule::connection('mysql')->getSchemaBuilder()->create('apt_ledger_entries', function ($table) {
    $table->string('ledger_entry_id', 32)->primary();
    $table->string('account_id', 32);
    $table->string('asset', 16);
    $table->string('quantity', 64);
    $table->integer('entry_direction');
    $table->string('entry_type', 64);
    $table->string('state', 16);
    $table->string('source_object_type', 64)->nullable();
    $table->string('source_object_id', 32)->nullable();
    $table->string('journal_batch_id', 32)->nullable();
    $table->string('reversal_of', 32)->nullable();
    $table->string('idempotency_key', 128)->nullable();
    $table->string('rule_version', 16)->nullable();
    $table->string('snapshot_id', 32)->nullable();
    $table->string('audit_event_id', 32)->nullable();
    $table->integer('created_time');
});

// ---------------------------------------------------------------------------
// 测试脚手架
// ---------------------------------------------------------------------------
$pass = 0;
$fail = 0;

function check(bool $cond, string $label): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "PASS: {$label}\n";
    } else {
        $fail++;
        echo "FAIL: {$label}\n";
    }
}

function expectReject(string $label, callable $fn): void
{
    global $pass, $fail;
    try {
        $fn();
        $fail++;
        echo "FAIL: {$label} — 期望抛 RunException，实际未抛\n";
    } catch (RunException $e) {
        $pass++;
        echo "PASS: {$label} — RunException: " . substr($e->getMessage(), 0, 70) . "\n";
    } catch (\Throwable $e) {
        $fail++;
        echo "FAIL: {$label} — 抛出了非 RunException： " . get_class($e) . ": " . substr($e->getMessage(), 0, 80) . "\n";
    }
}

function snapshot(): array
{
    return AptLedgerEntryModel::query()
        ->orderBy('ledger_entry_id')
        ->get(['ledger_entry_id', 'quantity', 'entry_direction', 'state'])
        ->toArray();
}

function assertUnchanged(array $before, string $label): void
{
    global $pass, $fail;
    $after = snapshot();
    if ($before === $after) {
        $pass++;
        echo "PASS: {$label} — 数据未变（ROW_COUNT_DELTA=0，经济字段不变）\n";
    } else {
        $fail++;
        echo "FAIL: {$label} — 数据被修改！\n";
        echo "before: " . json_encode($before, JSON_UNESCAPED_UNICODE) . "\n";
        echo "after : " . json_encode($after, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

/** 生成一条合法的分录数据（除主键外其余字段可被覆盖） */
function makeData(string $id, array $overrides = []): array
{
    $base = [
        'ledger_entry_id' => $id,
        'account_id' => 'ACC0001',
        'asset' => 'APT-I',
        'quantity' => '100.000000000000000000',
        'entry_direction' => 1,
        'entry_type' => 'robot_reward',
        'state' => 'posted',
        'source_object_type' => 'robot_reward',
        'source_object_id' => 'RW0001',
        'journal_batch_id' => 'JB0001',
        'reversal_of' => '0',
        'idempotency_key' => 'IK_' . $id,
        'rule_version' => 'v1',
        'snapshot_id' => 'SN0001',
        'audit_event_id' => 'AE0001',
        'created_time' => 1700000000,
    ];
    return array_merge($base, $overrides);
}

echo "=====================================================\n";
echo "Ledger append-only mutation matrix regression test\n";
echo "=====================================================\n\n";

// ---------------------------------------------------------------------------
// 1. 类加载
// ---------------------------------------------------------------------------
echo "[1] 类加载\n";
check(class_exists(AptLedgerEntryModel::class), 'class_exists AptLedgerEntryModel');
check(class_exists(AptLedgerEntryAppendOnlyBuilder::class), 'class_exists AptLedgerEntryAppendOnlyBuilder');
check(class_exists(AptLedgerEntryDao::class), 'class_exists AptLedgerEntryDao');
check(class_exists(RunException::class), 'class_exists RunException');
echo "\n";

// ---------------------------------------------------------------------------
// 2. Builder injection
// ---------------------------------------------------------------------------
echo "[2] Builder injection\n";
$q = AptLedgerEntryModel::query();
check(get_class($q) === AptLedgerEntryAppendOnlyBuilder::class, 'Model::query() 返回 AptLedgerEntryAppendOnlyBuilder');
check(AptLedgerEntryModel::where('account_id', 'X') instanceof AptLedgerEntryAppendOnlyBuilder, 'Model::where() 返回 AptLedgerEntryAppendOnlyBuilder');
$nb = (new AptLedgerEntryModel())->newEloquentBuilder(AptLedgerEntryModel::query()->getQuery());
check($nb instanceof AptLedgerEntryAppendOnlyBuilder, 'newEloquentBuilder() 注入 AptLedgerEntryAppendOnlyBuilder');
echo "\n";

// ---------------------------------------------------------------------------
// 3. deny set 与 disposition 契约（静态）
// ---------------------------------------------------------------------------
echo "[3] deny set 与 disposition 契约\n";
$denySet = array_map('strtolower', AptLedgerEntryAppendOnlyBuilder::DESTRUCTIVE_METHODS);
sort($denySet);
$denyExpect = LEDGER_DENY;
sort($denyExpect);
check($denySet === $denyExpect, 'DESTRUCTIVE_METHODS 与 LEDGER_DENY disposition 完全一致（含 updatefrom）');

// 每个 DENY 方法必须映射到真实框架 public 方法（防拼写错误）
$eloquentMethods = [];
foreach ((new ReflectionClass(EloquentBuilder::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
    $eloquentMethods[strtolower($m->getName())] = true;
}
$queryMethods = [];
foreach ((new ReflectionClass(QueryBuilder::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
    $queryMethods[strtolower($m->getName())] = true;
}
foreach (LEDGER_DENY as $deny) {
    check(
        isset($eloquentMethods[$deny]) || isset($queryMethods[$deny]),
        "DENY `{$deny}` 映射到真实框架方法"
    );
}

// ALLOW_APPEND 不得出现在 deny set
foreach (LEDGER_ALLOW_APPEND as $allow) {
    check(!in_array($allow, $denySet, true), "ALLOW_APPEND `{$allow}` 未被 deny");
}
echo "\n";

// ---------------------------------------------------------------------------
// 4. dependency mutation-surface contract（Query\Builder 前缀枚举 vs disposition）
// ---------------------------------------------------------------------------
echo "[4] dependency mutation-surface contract（未来升级护栏）\n";
$disposition = array_fill_keys(LEDGER_DENY, 'DENY') + array_fill_keys(LEDGER_ALLOW_APPEND, 'ALLOW_APPEND');
$undispositioned = [];
foreach ((new ReflectionClass(QueryBuilder::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
    if ($m->getDeclaringClass()->getName() !== QueryBuilder::class) {
        continue;
    }
    $name = strtolower($m->getName());
    foreach (QUERY_WRITE_PREFIXES as $prefix) {
        if (strpos($name, $prefix) === 0 && !isset($disposition[$name])) {
            $undispositioned[] = $m->getName();
            break;
        }
    }
}
check(
    $undispositioned === [],
    'Query\Builder 公开 mutation method 已全部 disposition' . ($undispositioned ? ' — 未 disposition: ' . implode(', ', $undispositioned) : '')
);
echo "\n";

// ---------------------------------------------------------------------------
// 5. mutation matrix
// ---------------------------------------------------------------------------
echo "[5] mutation matrix\n";

// 5.1 允许：new Model + save（INSERT）
$id1 = '9000000000000000001';
$m = new AptLedgerEntryModel(makeData($id1));
$ok = $m->save();
check($ok === true, 'INSERT 允许：new Model->save()');

// 5.2 允许：Builder insert（追加）
$id2 = '9000000000000000002';
$ok = AptLedgerEntryModel::query()->insert([makeData($id2)]);
check($ok === true, 'INSERT 允许：Builder->insert()');

// 5.3 允许：只读查询
$found = AptLedgerEntryModel::query()->where('ledger_entry_id', $id1)->first();
check($found instanceof AptLedgerEntryModel, 'READ 允许：Builder->where()->first()');
check(AptLedgerEntryModel::query()->count() === 2, 'READ 允许：Builder->count() == 2');

// ---- destructive mutation（全部应 REJECT 且数据不变）----
$baseline = snapshot();

$rejections = [
    // Model 实例层
    'existing Model->save()（UPDATE 路径）' => function () use ($id1) {
        $row = AptLedgerEntryModel::query()->find($id1);
        $row->quantity = '999';
        $row->save();
    },
    'Model->delete()' => function () use ($id1) {
        AptLedgerEntryModel::query()->find($id1)->delete();
    },
    // Eloquent Builder 显式覆写
    'Builder->update()' => fn () => AptLedgerEntryModel::query()->update(['quantity' => '999']),
    'Builder->upsert()' => fn () => AptLedgerEntryModel::query()->upsert([makeData('9000000000000000003')], ['ledger_entry_id'], ['quantity' => '999']),
    'Builder->increment()' => fn () => AptLedgerEntryModel::query()->increment('entry_direction'),
    'Builder->decrement()' => fn () => AptLedgerEntryModel::query()->decrement('entry_direction'),
    'Builder->touch()' => fn () => AptLedgerEntryModel::query()->touch(),
    'Builder->delete()' => fn () => AptLedgerEntryModel::query()->delete(),
    'Builder->forceDelete()' => fn () => AptLedgerEntryModel::query()->forceDelete(),
    // Query Builder 层（显式覆写 + __call 兜底）
    'Builder->updateOrInsert()' => fn () => AptLedgerEntryModel::query()->updateOrInsert(['ledger_entry_id' => $id1], ['quantity' => '999']),
    'Builder->incrementEach()' => fn () => AptLedgerEntryModel::query()->incrementEach(['entry_direction' => 1]),
    'Builder->decrementEach()' => fn () => AptLedgerEntryModel::query()->decrementEach(['entry_direction' => 1]),
    'Builder->truncate()' => fn () => AptLedgerEntryModel::query()->truncate(),
    'Builder->updateFrom()' => fn () => AptLedgerEntryModel::query()->updateFrom(['quantity' => '999']),
    // DAO 层覆写
    'DAO->update()' => function () use ($id1) {
        (new AptLedgerEntryDao())->update($id1, ['quantity' => '999']);
    },
    'DAO->updateAll()' => function () {
        (new AptLedgerEntryDao())->updateAll(['account_id' => 'ACC0001'], ['quantity' => '999']);
    },
    'DAO->updateOrCreate()' => function () {
        (new AptLedgerEntryDao())->updateOrCreate(['account_id' => 'ACC0001'], ['quantity' => '999']);
    },
    'DAO->delete()' => function () use ($id1) {
        (new AptLedgerEntryDao())->delete($id1);
    },
    'DAO->deleteAll()' => function () {
        (new AptLedgerEntryDao())->deleteAll(['account_id' => 'ACC0001']);
    },
];

foreach ($rejections as $label => $fn) {
    $before = snapshot();
    expectReject("REJECT：{$label}", $fn);
    assertUnchanged($before, "  完整性：{$label}");
}
echo "\n";

// ---------------------------------------------------------------------------
// 6. 汇总
// ---------------------------------------------------------------------------
echo "=====================================================\n";
echo "RESULT: pass={$pass} fail={$fail}\n";
echo "=====================================================\n";

if ($fail > 0) {
    echo "TEST FAILED\n";
    exit(1);
}
echo "ALL PASS\n";
exit(0);
