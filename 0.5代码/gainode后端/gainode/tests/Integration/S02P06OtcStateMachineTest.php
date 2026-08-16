<?php

declare(strict_types=1);

/**
 * S02-P06 OTC / Power 状态机 + fail-closed + 只读投影集成测试
 * （独立 CLI 脚本，无需 PHPUnit，SQLite in-memory）。
 *
 * 覆盖 07 §S02-P06 验证项：
 *   1. OtcOrder 状态机：O1-O12 合法/非法转移 + CAS + audit_event_id 回写
 *   2. OtcTrade 单态 completed append-only 只读查询
 *   3. fail-closed：quote / createOrder / recordTrade
 *   4. 只读投影（detail / listByUser / listByOrder）
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use library\model\otc\OtcOrderModel;
use library\model\otc\OtcTradeModel;
use library\service\otc\OtcOrderService;
use library\service\otc\OtcTradeService;
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

$mk = function (string $table, callable $def) use ($schema) {
    if (!$schema->hasTable($table)) {
        $schema->create($table, $def);
    }
};

$mk('otc_orders', function ($t) {
    $t->string('otc_order_id', 32)->primary();
    $t->string('user_id', 32);
    $t->string('side', 8)->default('BUY');
    $t->string('price', 32)->default('0');
    $t->string('quantity_apt', 64)->default('0');
    $t->string('filled_quantity_apt', 64)->default('0');
    $t->string('remaining_quantity_apt', 64)->default('0');
    $t->string('fee_apt', 64)->default('0');
    $t->string('power_required', 32)->default('0');
    $t->string('power_consumed', 32)->default('0');
    $t->string('power_frozen', 32)->default('0');
    $t->string('status', 24)->default('draft');
    $t->integer('review_required')->default(0);
    $t->string('quote_id', 32)->default('0');
    $t->string('snapshot_id', 32)->default('0');
    $t->string('rule_version', 64)->default('');
    $t->string('parameter_release_id', 32)->default('0');
    $t->string('policy_version', 64)->default('');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('otc_trades', function ($t) {
    $t->string('trade_id', 32)->primary();
    $t->string('otc_order_id', 32);
    $t->string('buyer_user_id', 32);
    $t->string('seller_user_id', 32);
    $t->string('quantity_apt', 64)->default('0');
    $t->string('price_apt', 32)->default('0');
    $t->string('fee_apt', 64)->default('0');
    $t->string('power_consumed', 32)->default('0');
    $t->string('status', 16)->default('completed');
    $t->text('ledger_entry_ids')->nullable();
    $t->string('ledger_batch_id', 32)->default('0');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
});

$mk('audit_events', function ($t) {
    $t->string('audit_event_id', 32)->primary();
    $t->string('event_code', 64);
    $t->string('actor_id', 32);
    $t->string('actor_role', 32);
    $t->string('target_object_type', 64);
    $t->string('target_object_id', 32);
    $t->string('before_snapshot_type', 32)->default('');
    $t->string('before_snapshot_id', 32)->default('0');
    $t->string('after_snapshot_type', 32)->default('');
    $t->string('after_snapshot_id', 32)->default('0');
    $t->string('outcome', 16);
    $t->string('reason_code', 64)->default('');
    $t->string('request_id', 64)->default('');
    $t->string('approval_id', 32)->default('0');
    $t->string('case_id', 32)->default('0');
    $t->integer('created_time')->default(0);
});

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
echo "S02-P06 OTC / Power state machine test\n";
echo "=====================================================\n\n";

$orderSvc = new OtcOrderService();
$tradeSvc = new OtcTradeService();

// ======================= 1. OtcOrder 主流程状态机（draft→review→matching→partial→completed） =======================
echo "[1] OtcOrder 主流程（draft→review→matching→partial→completed）\n";
$orderSvc->create([
    'otc_order_id'   => 'O1',
    'user_id'        => 'U1',
    'side'           => OtcOrderModel::SIDE_BUY,
    'price'          => '1.00000000',
    'quantity_apt'   => '100.000000000000000000',
    'status'         => OtcOrderModel::STATUS_DRAFT,
    'review_required' => 1,
    'object_version' => 0,
]);
$orderSvc->submitReview('O1', 'U1', 'END_USER');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_REVIEW, 'O1 submitReview → review');
$orderSvc->approveReview('O1', 'KYC', 'KYC_REVIEWER');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_MATCHING, 'O3 approveReview → matching');
$orderSvc->partialFill('O1', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_PARTIAL, 'O5 partialFill → partial');
$orderSvc->completeFromPartial('O1', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_COMPLETED, 'O9 completeFromPartial → completed');
check((int) $orderSvc->get('O1')->object_version === 4, 'object_version → 4（4 次转移）');
check((string) $orderSvc->get('O1')->audit_event_id !== '0', 'audit_event_id 已回写');

// O11: completed → disputed
$orderSvc->dispute('O1', 'U1', 'END_USER');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_DISPUTED, 'O11 dispute → disputed');

// O12 (completed 分支): disputed → completed（维持成交）
$orderSvc->resolveDisputeComplete('O1', 'RA', 'RISK_APPROVER');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_COMPLETED, 'O12 resolveDisputeComplete → completed');

// O11 再次争议（STABLE_WITH_EXCEPTION_TRANSITIONS 可重复争议）
$orderSvc->dispute('O1', 'U1', 'END_USER');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_DISPUTED, 'O11 再次 dispute → disputed');
// O12 (cancelled 分支): disputed → cancelled（退钱）
$orderSvc->resolveDisputeCancel('O1', 'RA', 'RISK_APPROVER');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_CANCELLED, 'O12 resolveDisputeCancel → cancelled');
// cancelled = TRUE_TERMINAL，不可再转移
expectDomainException(function () use ($orderSvc) {
    $orderSvc->dispute('O1', 'U1', 'END_USER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'cancelled 态 dispute → OBJECT_VERSION_CONFLICT（终态）');
echo "\n";

// ======================= 2. OtcOrder 旁路状态机 =======================
echo "[2] OtcOrder 旁路（draft→matching / review→rejected / 成交/取消/到期）\n";
// O2: draft → matching（无需审核）
$orderSvc->create([
    'otc_order_id'    => 'O2',
    'user_id'         => 'U1',
    'side'            => OtcOrderModel::SIDE_SELL,
    'status'          => OtcOrderModel::STATUS_DRAFT,
    'review_required' => 0,
    'object_version'  => 0,
]);
$orderSvc->submitMatching('O2', 'U1', 'END_USER');
check((string) $orderSvc->get('O2')->status === OtcOrderModel::STATUS_MATCHING, 'O2 submitMatching → matching');

// O4: review → rejected
$orderSvc->create([
    'otc_order_id'   => 'O4',
    'user_id'        => 'U2',
    'side'           => OtcOrderModel::SIDE_BUY,
    'status'         => OtcOrderModel::STATUS_REVIEW,
    'object_version' => 0,
]);
$orderSvc->reject('O4', 'OPS', 'OPS_OPERATOR');
check((string) $orderSvc->get('O4')->status === OtcOrderModel::STATUS_REJECTED, 'O4 reject → rejected');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->approveReview('O4', 'OPS', 'OPS_OPERATOR');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'rejected 态 approveReview → OBJECT_VERSION_CONFLICT（终态）');

// O6: matching → completed（全部成交）
$orderSvc->create([
    'otc_order_id'   => 'O6',
    'user_id'        => 'U3',
    'side'           => OtcOrderModel::SIDE_BUY,
    'status'         => OtcOrderModel::STATUS_MATCHING,
    'object_version' => 0,
]);
$orderSvc->completeFromMatching('O6', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O6')->status === OtcOrderModel::STATUS_COMPLETED, 'O6 completeFromMatching → completed');

// O7: matching → cancelled
$orderSvc->create([
    'otc_order_id'   => 'O7',
    'user_id'        => 'U4',
    'side'           => OtcOrderModel::SIDE_SELL,
    'status'         => OtcOrderModel::STATUS_MATCHING,
    'object_version' => 0,
]);
$orderSvc->cancel('O7', 'U4', 'END_USER');
check((string) $orderSvc->get('O7')->status === OtcOrderModel::STATUS_CANCELLED, 'O7 cancel → cancelled');

// O8: matching → expired
$orderSvc->create([
    'otc_order_id'   => 'O8',
    'user_id'        => 'U5',
    'side'           => OtcOrderModel::SIDE_BUY,
    'status'         => OtcOrderModel::STATUS_MATCHING,
    'object_version' => 0,
]);
$orderSvc->expire('O8', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O8')->status === OtcOrderModel::STATUS_EXPIRED, 'O8 expire → expired');

// O10 (cancelled): partial → cancelled
$orderSvc->create([
    'otc_order_id'   => 'O10a',
    'user_id'        => 'U6',
    'side'           => OtcOrderModel::SIDE_SELL,
    'status'         => OtcOrderModel::STATUS_PARTIAL,
    'object_version' => 0,
]);
$orderSvc->cancelRemaining('O10a', 'U6', 'END_USER');
check((string) $orderSvc->get('O10a')->status === OtcOrderModel::STATUS_CANCELLED, 'O10 cancelRemaining → cancelled');

// O10 (expired): partial → expired
$orderSvc->create([
    'otc_order_id'   => 'O10b',
    'user_id'        => 'U7',
    'side'           => OtcOrderModel::SIDE_BUY,
    'status'         => OtcOrderModel::STATUS_PARTIAL,
    'object_version' => 0,
]);
$orderSvc->expireRemaining('O10b', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O10b')->status === OtcOrderModel::STATUS_EXPIRED, 'O10 expireRemaining → expired');

// 非法：draft 态直接 completeFromMatching → 冲突
$orderSvc->create([
    'otc_order_id'   => 'OX',
    'user_id'        => 'U8',
    'side'           => OtcOrderModel::SIDE_BUY,
    'status'         => OtcOrderModel::STATUS_DRAFT,
    'object_version' => 0,
]);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->completeFromMatching('OX', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'draft 态 completeFromMatching → OBJECT_VERSION_CONFLICT');
echo "\n";

// ======================= 3. OtcTrade 单态 + 只读查询 =======================
echo "[3] OtcTrade 单态 completed + 只读查询\n";
$tradeSvc->create([
    'trade_id'        => 'T1',
    'otc_order_id'    => 'O6',
    'buyer_user_id'   => 'U3',
    'seller_user_id'  => 'U9',
    'quantity_apt'    => '50.000000000000000000',
    'price_apt'       => '1.00000000',
    'fee_apt'         => '0.500000000000000000',
    'power_consumed'  => '2.0000',
    'status'          => OtcTradeModel::STATUS_COMPLETED,
    'ledger_batch_id' => '0',
    'object_version'  => 0,
]);
$tdetail = $tradeSvc->detail('T1');
check($tdetail['trade_id'] === 'T1', 'trade detail.trade_id=T1');
check($tdetail['status'] === OtcTradeModel::STATUS_COMPLETED, 'trade detail.status=completed（单态）');
check($tdetail['quantity_apt'] === '50.000000000000000000', 'trade detail.quantity_apt 精确 decimal string');

$tlist = $tradeSvc->listByOrder('O6');
check(count($tlist['trades']) === 1, 'listByOrder(O6) 数量=1');

$buyerTrades = $tradeSvc->getByBuyer('U3');
check($buyerTrades->count() === 1, 'getByBuyer(U3) 数量=1');

$sellerTrades = $tradeSvc->getBySeller('U9');
check($sellerTrades->count() === 1, 'getBySeller(U9) 数量=1');
echo "\n";

// ======================= 4. 只读投影 =======================
echo "[4] 只读投影（detail / listByUser）\n";
$odetail = $orderSvc->detail('O1');
check($odetail['otc_order_id'] === 'O1', 'order detail.otc_order_id=O1');
check($odetail['status'] === OtcOrderModel::STATUS_CANCELLED, 'order detail.status=cancelled');
check($odetail['quantity_apt'] === '100.000000000000000000', 'order detail.quantity_apt 精确 decimal string');

$olist = $orderSvc->listByUser('U1');
check(count($olist['orders']) === 2, 'listByUser(U1) 数量=2（O1 + O2）');

expectDomainException(function () use ($orderSvc) {
    $orderSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'order detail(不存在) → VALIDATION_ERROR');
expectDomainException(function () use ($tradeSvc) {
    $tradeSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'trade detail(不存在) → VALIDATION_ERROR');
echo "\n";

// ======================= 5. fail-closed（依赖未冻结） =======================
echo "[5] fail-closed（quote/createOrder/recordTrade）\n";
expectDomainException(function () use ($orderSvc) {
    $orderSvc->quote([], 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'OTC quote → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->createOrder([], 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'OTC createOrder → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($tradeSvc) {
    $tradeSvc->recordTrade([], 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'OTC recordTrade → DEPENDENCY_UNAVAILABLE');
echo "\n";

summary();
