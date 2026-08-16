<?php

declare(strict_types=1);

/**
 * S01-P06 投影测试公共引导（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 1. 加载 autoload；
 * 2. 建立 SQLite in-memory 连接（命名 'mysql'，匹配各 Model::$connection='mysql'）；
 * 3. 创建 4 张 source 表（auth_sessions / mfa_enrollments / power_positions / kyc_cases），
 *    列名/类型与 S01-P05 DDL 对齐（SQLite 对类型宽松）；
 * 4. 提供 check() 断言脚手架。
 *
 * 投影服务只读聚合，不接触真实 MySQL / .env，不新增任何 DDL（NOT_PERSISTED）。
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;

// 最小引导：SQLite in-memory（命名 'mysql'）
$capsule = new Capsule(Container::getInstance());
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
], 'mysql');
$capsule->setAsGlobal();
$capsule->bootEloquent();

$schema = Capsule::connection('mysql')->getSchemaBuilder();

// ---- auth_sessions（S01-P05 DDL 对齐）----
if (!$schema->hasTable('auth_sessions')) {
    $schema->create('auth_sessions', function ($table) {
        $table->string('session_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('token_hash', 128);
        $table->string('status', 16);
        $table->text('device_info')->nullable();
        $table->string('ip_address', 64);
        $table->integer('mfa_verified');
        $table->integer('expires_at');
        $table->integer('object_version');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32);
        $table->integer('created_time');
        $table->integer('updated_time');
    });
}

// ---- mfa_enrollments（S01-P05 DDL 对齐）----
if (!$schema->hasTable('mfa_enrollments')) {
    $schema->create('mfa_enrollments', function ($table) {
        $table->string('enrollment_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('method_type', 32);
        $table->string('status', 16);
        $table->integer('enrolled_at');
        $table->integer('last_verified_at');
        $table->integer('backup_codes_active');
        $table->text('device_info')->nullable();
        $table->integer('object_version');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32);
        $table->integer('created_time');
        $table->integer('updated_time');
    });
}

// ---- power_positions（MC1 DDL 对齐）----
if (!$schema->hasTable('power_positions')) {
    $schema->create('power_positions', function ($table) {
        $table->string('user_id', 32)->primary();
        $table->string('available', 64);
        $table->string('frozen', 64);
        $table->string('consumed_period', 64);
        $table->string('released_period', 64);
        $table->string('recovering', 64);
        $table->string('limit', 64);
        $table->integer('power_cap_source_robot_level');
        $table->integer('last_restore_at');
        $table->integer('next_restore_at');
        $table->string('rule_version', 16);
        $table->string('parameter_release_id', 32);
        $table->integer('object_version');
        $table->integer('created_time');
        $table->integer('updated_time');
    });
}

// ---- kyc_cases（S01-P05 DDL 对齐）----
if (!$schema->hasTable('kyc_cases')) {
    $schema->create('kyc_cases', function ($table) {
        $table->string('case_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('kyc_level', 16);
        $table->string('status', 16);
        $table->integer('submitted_at');
        $table->integer('reviewed_at');
        $table->string('reviewed_by', 32);
        $table->string('reason_code', 64);
        $table->string('reason_text_key', 64);
        $table->string('next_action', 64);
        $table->string('policy_version', 16);
        $table->string('rule_version', 16);
        $table->integer('object_version');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32);
        $table->integer('created_time');
        $table->integer('updated_time');
    });
}

// ---- 断言脚手架 ----
$GLOBALS['__proj_pass'] = 0;
$GLOBALS['__proj_fail'] = 0;

function check(bool $cond, string $label): void
{
    if ($cond) {
        $GLOBALS['__proj_pass']++;
        echo "PASS: {$label}\n";
    } else {
        $GLOBALS['__proj_fail']++;
        echo "FAIL: {$label}\n";
    }
}

function summary(): void
{
    $pass = $GLOBALS['__proj_pass'];
    $fail = $GLOBALS['__proj_fail'];
    echo "=====================================================\n";
    echo "RESULT: pass={$pass} fail={$fail}\n";
    echo "=====================================================\n";
    if ($fail > 0) {
        echo "TEST FAILED\n";
        exit(1);
    }
    echo "ALL PASS\n";
    exit(0);
}
