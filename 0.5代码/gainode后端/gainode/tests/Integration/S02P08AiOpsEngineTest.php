<?php

declare(strict_types=1);

/**
 * S02-P08 内部 AI 经济引擎 集成测试（独立 CLI 脚本，无需 PHPUnit，纯逻辑流水线，不触数据库）。
 *
 * 覆盖 07 §S02-P08 验证项：
 *   1. confirmed_profit<=0 完整短路流水线（四字段确定性可重放）
 *   2. confirmed_profit>0 正向全链路（smoothing + price + multiplier + caps）
 *   3. 各步骤 fail-closed（无 smoothing / 缺 price / 缺 cap）
 *   4. persist 持久化 fail-closed（预算对象未冻结）
 *   5. generateFromActiveRelease fail-closed（参数未激活）
 *   6. 脱敏边界（forExternal 不含内部字段）
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\service\aiops\AiBudgetEngine;
use library\service\aiops\AiBudgetParameterReader;
use library\service\aiops\BudgetDecision;
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

// Mock reader（不触 DB）
$unavailableReader = new class extends AiBudgetParameterReader {
    public function getBudgetParameterSnapshot(): array {
        return [
            'source_status' => AiBudgetParameterReader::SOURCE_UNAVAILABLE,
            'reason_code'   => AiBudgetParameterReader::REASON_NO_ACTIVE_RELEASE,
        ];
    }
};

function confirmedInput(string $profit): array
{
    return [
        'confirmed'          => true,
        'source_object_type' => 'simulation_run',
        'source_object_id'   => 'RUN-1',
        'source_hash'        => 'hash-RUN-1',
        'currency'           => 'USDT',
        'confirmed_at'       => 1700000000,
        'profit_amount'      => $profit,
    ];
}

echo "=====================================================\n";
echo "S02-P08 AI Economic Engine integration test\n";
echo "=====================================================\n\n";

$engine = new AiBudgetEngine();

// ======================= 1. confirmed_profit<=0 完整短路流水线 =======================
echo "[1] confirmed_profit<=0 完整短路（四字段确定性可重放）\n";
$d0 = $engine->compute(confirmedInput('0'), [
    'caps' => [
        'stage_expected_budget' => '80',
        'stage_hard_cap'        => '120',
        'cash_support_cap'      => '90',
        'human_approved_cap'    => '70',
    ],
    'parameter_release_id' => 'R1',
    'snapshot_id'          => 'S1',
    'business_date'        => '2026-08-16',
]);
$i0 = $d0->forInternal();
check($i0['confirmed_profit'] === '0', 'confirmed_profit=0');
check($i0['reference_profit'] === '0', 'reference_profit=0（短路）');
check($i0['mapped_apt_budget'] === '0', 'mapped_apt_budget=0（短路）');
check($i0['daily_ai_budget'] === '0', 'daily_ai_budget=0');
check($i0['algorithm'] === 'ZERO_SHORTCUT', 'algorithm=ZERO_SHORTCUT');
check($i0['source_hash'] === 'hash-RUN-1', 'source_hash 记录');

// 同输入重放 → 幂等键一致（可重放）
$d0b = $engine->compute(confirmedInput('0'), [
    'caps' => [
        'stage_expected_budget' => '80',
        'stage_hard_cap'        => '120',
        'cash_support_cap'      => '90',
        'human_approved_cap'    => '70',
    ],
    'parameter_release_id' => 'R1',
    'snapshot_id'          => 'S1',
    'business_date'        => '2026-08-16',
]);
check($d0b->forInternal()['idempotency_key'] === $i0['idempotency_key'], '同输入重放幂等键一致');
echo "\n";

// ======================= 2. confirmed_profit>0 正向全链路 =======================
echo "[2] confirmed_profit>0 正向全链路（smoothing + price + multiplier + caps）\n";
$d1 = $engine->compute(confirmedInput('100'), [
    'smoothing'           => ['reference_profit' => '80', 'rule_version' => 'v1', 'input_hash' => 'h1'],
    'apt_reference_price' => '0.5',
    'mapping_multiplier'  => '2',
    'caps'                => [
        'stage_expected_budget' => '70',
        'stage_hard_cap'        => '120',
        'cash_support_cap'      => '90',
        'human_approved_cap'    => '60',
    ],
    'parameter_release_id' => 'R1',
    'snapshot_id'          => 'S1',
    'business_date'        => '2026-08-16',
]);
$i1 = $d1->forInternal();
check($i1['confirmed_profit'] === '100', 'confirmed_profit=100');
check($i1['reference_profit'] === '80', 'reference_profit=80（smoothing）');
check($i1['mapped_apt_budget'] === '320.000000000000000000', 'mapped=80/0.5*2=320');
check($i1['daily_ai_budget'] === '60', 'daily=min(320,70,120,90,60)=60（五 cap 最小）');
check($i1['algorithm'] === 'APPROVED_SMOOTHING', 'algorithm=APPROVED_SMOOTHING');
check($i1['rule_version'] === 'v1', 'rule_version=v1');
echo "\n";

// ======================= 3. 各步骤 fail-closed =======================
echo "[3] 各步骤 fail-closed\n";
expectDomainException(function () use ($engine) {
    $engine->compute(confirmedInput('100'), []);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, '>0 无 smoothing → DEPENDENCY_UNAVAILABLE');

expectDomainException(function () use ($engine) {
    $engine->compute(confirmedInput('100'), [
        'smoothing'          => ['reference_profit' => '80', 'rule_version' => 'v1'],
        // 缺 apt_reference_price
        'mapping_multiplier' => '2',
        'caps'               => ['stage_expected_budget' => '70', 'stage_hard_cap' => '120', 'cash_support_cap' => '90', 'human_approved_cap' => '60'],
    ]);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, '缺 price → DEPENDENCY_UNAVAILABLE');

expectDomainException(function () use ($engine) {
    $engine->compute(confirmedInput('100'), [
        'smoothing'           => ['reference_profit' => '80', 'rule_version' => 'v1'],
        'apt_reference_price' => '0.5',
        'mapping_multiplier'  => '2',
        'caps'                => ['stage_expected_budget' => '70', 'stage_hard_cap' => '120', 'cash_support_cap' => '90'],
        // 缺 human_approved_cap
    ]);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, '缺 human_approved_cap → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 4. persist 持久化 fail-closed =======================
echo "[4] persist 持久化 fail-closed（预算对象未冻结）\n";
expectDomainException(function () use ($engine, $d0) {
    $engine->persist($d0);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'persist → DEPENDENCY_UNAVAILABLE（预算持久对象未冻结）');
echo "\n";

// ======================= 5. generateFromActiveRelease fail-closed =======================
echo "[5] generateFromActiveRelease fail-closed（参数未激活）\n";
$closedEngine = new AiBudgetEngine($unavailableReader);
expectDomainException(function () use ($closedEngine) {
    $closedEngine->generateFromActiveRelease(confirmedInput('0'));
}, ErrorDict::DEPENDENCY_UNAVAILABLE, '参数未激活 → DEPENDENCY_UNAVAILABLE（引擎 closed）');
echo "\n";

// ======================= 6. 脱敏边界 =======================
echo "[6] 脱敏边界（forExternal 不含内部字段）\n";
$external = $d1->forExternal();
check(count($external) === 5, '外部视图仅 5 字段（四字段 + source_status）');
check($d1->assertExternalSafe($external) === true, '外部视图负向扫描通过');
check(!array_key_exists('source_hash', $external), '外部无 source_hash');
check(!array_key_exists('algorithm', $external), '外部无 algorithm');
check(!array_key_exists('idempotency_key', $external), '外部无 idempotency_key');
check($d1->assertExternalSafe($d1->forInternal()) === false, '内部视图不应用于对外');
echo "\n";

summary();
