<?php

declare(strict_types=1);

/**
 * S02-P08 内部 AI 经济引擎 契约测试（独立 CLI 脚本，无需 PHPUnit，不触数据库）。
 *
 * 覆盖纯逻辑：输入适配校验/去重、<=0 短路、price/multiplier fail-closed、
 * bcmath 精度、五 cap 取最小、脱敏负向扫描、幂等键、V2 错误码 HTTP 映射。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\service\aiops\ConfirmedProfitAdapter;
use library\service\aiops\ReferenceProfitService;
use library\service\aiops\AptBudgetMappingService;
use library\service\aiops\DailyAIBudgetService;
use library\service\aiops\AiBudgetParameterReader;
use library\service\aiops\BudgetDecision;
use library\service\aiops\AiBudgetEngine;
use library\service\idempotency\IdempotencyStore;
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

// 测试用 IdempotencyStore 匿名实现
$unavailableStore = new class implements IdempotencyStore {
    public function isAvailable(): bool { return false; }
    public function find(string $idempotencyKey, string $objectType): ?array { return null; }
    public function reserve(string $idempotencyKey, string $objectType, string $objectId, string $requestId): void {}
    public function complete(string $idempotencyKey, string $objectType, string $objectId, array $response): void {}
};
$emptyStore = new class implements IdempotencyStore {
    public function isAvailable(): bool { return true; }
    public function find(string $idempotencyKey, string $objectType): ?array { return null; }
    public function reserve(string $idempotencyKey, string $objectType, string $objectId, string $requestId): void {}
    public function complete(string $idempotencyKey, string $objectType, string $objectId, array $response): void {}
};
$duplicateStore = new class implements IdempotencyStore {
    public function isAvailable(): bool { return true; }
    public function find(string $idempotencyKey, string $objectType): ?array {
        return ['status' => 'completed', 'response' => null, 'object_id' => 'X'];
    }
    public function reserve(string $idempotencyKey, string $objectType, string $objectId, string $requestId): void {}
    public function complete(string $idempotencyKey, string $objectType, string $objectId, array $response): void {}
};

echo "=====================================================\n";
echo "S02-P08 AI Economic Engine contract test\n";
echo "=====================================================\n\n";

// ======================= 1. 常量冻结 =======================
echo "[1] 常量冻结\n";
check(ConfirmedProfitAdapter::SOURCE_STATUS_CONFIRMED === 'CONFIRMED', 'ConfirmedProfitAdapter CONFIRMED');
check(ConfirmedProfitAdapter::DEFAULT_CURRENCY === 'USDT', '默认功能货币 USDT');
check(ReferenceProfitService::ALGORITHM_ZERO_SHORTCUT === 'ZERO_SHORTCUT', 'ZERO_SHORTCUT 算法');
check(ReferenceProfitService::ALGORITHM_APPROVED_SMOOTHING === 'APPROVED_SMOOTHING', 'APPROVED_SMOOTHING 算法');
check(DailyAIBudgetService::REQUIRED_CAPS === ['stage_expected_budget', 'stage_hard_cap', 'cash_support_cap', 'human_approved_cap'], '四 required cap 冻结');
check(AiBudgetParameterReader::KEY_MAPPING_MULTIPLIER === 'AI.apt_budget_mapping_multiplier', 'mapping_multiplier 键');
check(AiBudgetParameterReader::KEY_STAGE_EXPECTED_BUDGET === 'AI.stage_expected_budget', 'stage_expected_budget 键');
check(in_array('source_hash', BudgetDecision::SENSITIVE_KEYS, true), '敏感键含 source_hash');
check(in_array('position', BudgetDecision::SENSITIVE_KEYS, true), '敏感键含 position');
check(in_array('supplier', BudgetDecision::SENSITIVE_KEYS, true), '敏感键含 supplier');
echo "\n";

// ======================= 2. ConfirmedProfitAdapter 输入校验/去重 =======================
echo "[2] ConfirmedProfitAdapter 输入校验 / 归一化 / 去重\n";
$adapter = new ConfirmedProfitAdapter();

expectDomainException(function () use ($adapter) {
    $adapter->normalize(['confirmed' => false, 'source_object_type' => 'sim', 'source_object_id' => '1', 'confirmed_at' => 1700000000, 'profit_amount' => '10']);
}, ErrorDict::VALIDATION_ERROR, '未确认输入 → VALIDATION_ERROR');

expectDomainException(function () use ($adapter) {
    $adapter->normalize(['confirmed' => true, 'source_object_type' => '', 'source_object_id' => '1', 'confirmed_at' => 1700000000, 'profit_amount' => '10']);
}, ErrorDict::VALIDATION_ERROR, '不可追溯（空 type）→ VALIDATION_ERROR');

expectDomainException(function () use ($adapter) {
    $adapter->normalize(['confirmed' => true, 'source_object_type' => 'sim', 'source_object_id' => '1', 'confirmed_at' => 0, 'profit_amount' => '10']);
}, ErrorDict::VALIDATION_ERROR, 'confirmed_at 无效 → VALIDATION_ERROR');

expectDomainException(function () use ($adapter) {
    $adapter->normalize(['confirmed' => true, 'source_object_type' => 'sim', 'source_object_id' => '1', 'confirmed_at' => 1700000000, 'profit_amount' => 12.5]);
}, ErrorDict::VALIDATION_ERROR, 'float 金额 → VALIDATION_ERROR（禁 float）');

$normNeg = $adapter->normalize(['confirmed' => true, 'source_object_type' => 'sim', 'source_object_id' => '1', 'confirmed_at' => 1700000000, 'profit_amount' => '-5']);
check($normNeg['confirmed_profit'] === '-5', '负利润（亏损）被接受，<=0 短路由 ReferenceProfitService 处理');

$norm = $adapter->normalize(['confirmed' => true, 'source_object_type' => 'sim', 'source_object_id' => '42', 'confirmed_at' => 1700000000, 'profit_amount' => '10.25']);
check($norm['source_status'] === 'CONFIRMED', '归一化 source_status=CONFIRMED');
check($norm['confirmed_profit'] === '10.25', '归一化 confirmed_profit=10.25');
check($norm['currency'] === 'USDT', '缺省 currency=USDT');
check($norm['source_hash'] !== '', 'source_hash 非空');
check(strlen($norm['dedupe_key']) === 64, 'dedupe_key 长度 64');

$norm2 = $adapter->normalize(['confirmed' => true, 'source_object_type' => 'sim', 'source_object_id' => '42', 'confirmed_at' => 1700000000, 'profit_amount' => '10.25']);
check($norm2['dedupe_key'] === $norm['dedupe_key'], '同输入 dedupe_key 确定（幂等）');

expectDomainException(function () use ($adapter, $unavailableStore) {
    $adapter->assertNotDuplicate(['dedupe_key' => 'k1'], $unavailableStore);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, '去重存储不可用 → DEPENDENCY_UNAVAILABLE');

// 可用但无记录 → 不抛（通过）
try {
    $adapter->assertNotDuplicate(['dedupe_key' => 'k1'], $emptyStore);
    check(true, '去重存储可用且无记录 → 通过');
} catch (\Throwable $e) {
    check(false, "去重存储可用且无记录 → 通过（意外异常：{$e->getMessage()}）");
}

expectDomainException(function () use ($adapter, $duplicateStore) {
    $adapter->assertNotDuplicate(['dedupe_key' => 'k1'], $duplicateStore);
}, ErrorDict::IDEMPOTENCY_CONFLICT, '重复输入 → IDEMPOTENCY_CONFLICT');
echo "\n";

// ======================= 3. ReferenceProfitService =======================
echo "[3] ReferenceProfitService（<=0 短路 / >0 smoothing）\n";
$refSvc = new ReferenceProfitService();

$r0 = $refSvc->computeReference('0');
check($r0['reference_profit'] === '0', 'confirmed_profit=0 → reference_profit=0');
check($r0['algorithm'] === 'ZERO_SHORTCUT', '短路算法 ZERO_SHORTCUT');

$rNeg = $refSvc->computeReference('-3');
check($rNeg['reference_profit'] === '0', 'confirmed_profit<0 → reference_profit=0');

expectDomainException(function () use ($refSvc) {
    $refSvc->computeReference('100');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'confirmed_profit>0 无 smoothing → DEPENDENCY_UNAVAILABLE');

$rPos = $refSvc->computeReference('100', [
    'reference_profit' => '80',
    'rule_version'     => 'v1',
    'input_hash'       => 'abc',
]);
check($rPos['reference_profit'] === '80', 'smoothing 上下文 → reference_profit=80');
check($rPos['algorithm'] === 'APPROVED_SMOOTHING', '算法 APPROVED_SMOOTHING');
check($rPos['rule_version'] === 'v1', 'rule_version 透传');
echo "\n";

// ======================= 4. AptBudgetMappingService =======================
echo "[4] AptBudgetMappingService（price/multiplier fail-closed / bcmath 精度）\n";
$mapSvc = new AptBudgetMappingService();

$m0 = $mapSvc->mapToApt('0', null, null);
check($m0['mapped_apt_budget'] === '0', 'reference=0 → mapped=0（短路）');

expectDomainException(function () use ($mapSvc) {
    $mapSvc->mapToApt('100', null, '2');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'price 缺失 → DEPENDENCY_UNAVAILABLE');

expectDomainException(function () use ($mapSvc) {
    $mapSvc->mapToApt('100', '0', '2');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'price=0（不除零）→ DEPENDENCY_UNAVAILABLE');

expectDomainException(function () use ($mapSvc) {
    $mapSvc->mapToApt('100', '0.5', null);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'multiplier 缺失 → DEPENDENCY_UNAVAILABLE');

expectDomainException(function () use ($mapSvc) {
    $mapSvc->mapToApt('100', '0.5', '0');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'multiplier=0 → DEPENDENCY_UNAVAILABLE');

$m1 = $mapSvc->mapToApt('100', '0.5', '2');
check($m1['mapped_apt_budget'] === '400.000000000000000000', 'mapped=100/0.5*2=400（bcmath 18 位）');

$m2 = $mapSvc->mapToApt('10', '3', '1');
check($m2['mapped_apt_budget'] === '3.333333333333333333', 'mapped=10/3*1（18 位截断）');
echo "\n";

// ======================= 5. DailyAIBudgetService =======================
echo "[5] DailyAIBudgetService（五 cap 取最小 / missing cap）\n";
$dailySvc = new DailyAIBudgetService();

$d1 = $dailySvc->computeDaily([
    'mapped_apt_budget'   => '100',
    'stage_expected_budget' => '80',
    'stage_hard_cap'      => '120',
    'cash_support_cap'    => '90',
    'human_approved_cap'  => '70',
]);
check($d1 === '70', 'min(100,80,120,90,70)=70');

$d2 = $dailySvc->computeDaily([
    'mapped_apt_budget'   => '10',
    'stage_expected_budget' => '80',
    'stage_hard_cap'      => '120',
    'cash_support_cap'    => '90',
    'human_approved_cap'  => '70',
]);
check($d2 === '10', 'mapped 最小 → daily=mapped');

expectDomainException(function () use ($dailySvc) {
    $dailySvc->computeDaily([
        'mapped_apt_budget'   => '100',
        'stage_expected_budget' => '80',
        'stage_hard_cap'      => '120',
        'cash_support_cap'    => '90',
    ]);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'human_approved_cap 缺失 → DEPENDENCY_UNAVAILABLE');

expectDomainException(function () use ($dailySvc) {
    $dailySvc->computeDaily(['mapped_apt_budget' => '100']);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, '全部 cap 缺失 → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 6. BudgetDecision 脱敏负向扫描 =======================
echo "[6] BudgetDecision 脱敏负向扫描（步骤 8）\n";
$decision = new BudgetDecision([
    'confirmed_profit'      => '100',
    'reference_profit'      => '80',
    'mapped_apt_budget'     => '400',
    'daily_ai_budget'       => '70',
    'source_status'         => 'AVAILABLE',
    'algorithm'             => 'APPROVED_SMOOTHING',
    'rule_version'          => 'v1',
    'source_hash'           => 'deadbeef',
    'parameter_release_id'  => 'R1',
    'snapshot_id'           => 'S1',
    'business_date'         => '2026-08-16',
    'idempotency_key'       => 'key123',
]);
$external = $decision->forExternal();
check($external['confirmed_profit'] === '100', '外部视图含 confirmed_profit');
check($external['daily_ai_budget'] === '70', '外部视图含 daily_ai_budget');
check($decision->assertExternalSafe($external) === true, '外部视图不含任何敏感键（负向扫描）');
check(!array_key_exists('source_hash', $external), '外部视图无 source_hash');
check(!array_key_exists('mapping_multiplier', $external), '外部视图无 mapping_multiplier');
check(!array_key_exists('idempotency_key', $external), '外部视图无 idempotency_key');

$internal = $decision->forInternal();
check($internal['source_hash'] === 'deadbeef', '内部视图含 source_hash（审计重放）');
check($decision->assertExternalSafe($internal) === false, '内部视图含敏感键（不应直接对外）');
echo "\n";

// ======================= 7. AiBudgetEngine 幂等键 =======================
echo "[7] AiBudgetEngine 幂等键（步骤 7）\n";
$engine = new AiBudgetEngine();
$k1 = $engine->buildIdempotencyKey('h1', 'R1', 'S1', '2026-08-16');
$k2 = $engine->buildIdempotencyKey('h1', 'R1', 'S1', '2026-08-16');
check($k1 === $k2, '同输入幂等键确定');
check(strlen($k1) === 64, '幂等键长度 64');
$k3 = $engine->buildIdempotencyKey('h1', 'R1', 'S2', '2026-08-16');
check($k3 !== $k1, 'snapshot 变化 → 幂等键变化（快照隔离）');
echo "\n";

// ======================= 8. V2 错误码 HTTP 映射 =======================
echo "[8] V2 错误码 HTTP 映射（05 §7）\n";
check(ErrorDict::httpStatus(ErrorDict::DEPENDENCY_UNAVAILABLE) === 503, 'DEPENDENCY_UNAVAILABLE → 503');
check(ErrorDict::httpStatus(ErrorDict::IDEMPOTENCY_CONFLICT) === 409, 'IDEMPOTENCY_CONFLICT → 409');
check(ErrorDict::httpStatus(ErrorDict::VALIDATION_ERROR) === 400, 'VALIDATION_ERROR → 400');
echo "\n";

summary();
