<?php

declare(strict_types=1);

/**
 * S02-P03 Ledger 契约测试（独立 CLI 脚本，无需 PHPUnit，不触数据库）。
 *
 * 覆盖纯逻辑：领域常量、append-only 机械强制（Model 实例层）、append 输入校验、
 * V2 错误码 HTTP 映射。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\model\ledger\AptLedgerEntryModel;
use library\service\ledger\LedgerService;
use support\exception\DomainException;
use support\exception\RunException;

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
echo "S02-P03 ledger contract test\n";
echo "=====================================================\n\n";

// ======================= 1. 领域常量 =======================
echo "[1] 领域常量（MC1/MC2 对齐）\n";
check(AptLedgerEntryModel::ENTRY_DIRECTION_CREDIT === 1, 'ENTRY_DIRECTION_CREDIT = 1');
check(AptLedgerEntryModel::ENTRY_DIRECTION_DEBIT === -1, 'ENTRY_DIRECTION_DEBIT = -1');
check(AptLedgerEntryModel::ENTRY_TYPE_LEDGER_REVERSAL === 'LEDGER_REVERSAL', 'ENTRY_TYPE_LEDGER_REVERSAL');
check(AptLedgerEntryModel::ASSET_APT_I === 'APT-I', 'ASSET_APT_I = APT-I');
check(AptLedgerEntryModel::STATES === ['pending', 'posted', 'reversed', 'disputed'], 'STATES 冻结四态');
check(in_array('object_version', (new AptLedgerEntryModel())->fields, true), 'object_version 在 $fields 中');
echo "\n";

// ======================= 2. append-only 机械强制 =======================
echo "[2] append-only 机械强制（Model 实例层）\n";
$m = new AptLedgerEntryModel();
$m->exists = true; // 模拟已落盘实例
try {
    $m->save();
    check(false, 'save() 已落盘实例应抛异常');
} catch (RunException $e) {
    check(true, 'save() 已落盘实例 → RunException');
}

try {
    (new AptLedgerEntryModel())->delete();
    check(false, 'delete() 应抛异常');
} catch (RunException $e) {
    check(true, 'delete() → RunException');
}
echo "\n";

// ======================= 3. append 输入校验 =======================
echo "[3] append 输入校验（VALIDATION_ERROR，DB 前拦截）\n";
$ledgerSvc = new LedgerService();
expectDomainException(function () use ($ledgerSvc) {
    $ledgerSvc->append(['quantity' => '100', 'entry_direction' => 1]);
}, ErrorDict::VALIDATION_ERROR, '缺 account_id → VALIDATION_ERROR');
expectDomainException(function () use ($ledgerSvc) {
    $ledgerSvc->append(['account_id' => 'A1', 'quantity' => '0', 'entry_direction' => 1]);
}, ErrorDict::VALIDATION_ERROR, 'quantity=0 → VALIDATION_ERROR');
expectDomainException(function () use ($ledgerSvc) {
    $ledgerSvc->append(['account_id' => 'A1', 'quantity' => '-5', 'entry_direction' => 1]);
}, ErrorDict::VALIDATION_ERROR, 'quantity<0 → VALIDATION_ERROR');
expectDomainException(function () use ($ledgerSvc) {
    $ledgerSvc->append(['account_id' => 'A1', 'quantity' => '10', 'entry_direction' => 0]);
}, ErrorDict::VALIDATION_ERROR, 'entry_direction=0 → VALIDATION_ERROR');
expectDomainException(function () use ($ledgerSvc) {
    $ledgerSvc->append(['account_id' => 'A1', 'quantity' => '10', 'entry_direction' => 1, 'asset' => 'APT-C']);
}, ErrorDict::VALIDATION_ERROR, 'asset=APT-C → VALIDATION_ERROR');
echo "\n";

// ======================= 4. V2 错误码 HTTP 映射 =======================
echo "[4] V2 错误码 HTTP 映射（05 §7）\n";
check(ErrorDict::httpStatus(ErrorDict::INSUFFICIENT_APT) === 422, 'INSUFFICIENT_APT → 422');
check(ErrorDict::httpStatus(ErrorDict::OBJECT_VERSION_CONFLICT) === 409, 'OBJECT_VERSION_CONFLICT → 409');
check(ErrorDict::httpStatus(ErrorDict::IDEMPOTENCY_CONFLICT) === 409, 'IDEMPOTENCY_CONFLICT → 409');
check(ErrorDict::httpStatus(ErrorDict::DEPENDENCY_UNAVAILABLE) === 503, 'DEPENDENCY_UNAVAILABLE → 503');
check((new DomainException(ErrorDict::INSUFFICIENT_APT))->httpStatus() === 422, 'DomainException(INSUFFICIENT_APT).httpStatus() = 422');
echo "\n";

summary();
