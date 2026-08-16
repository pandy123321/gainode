<?php

declare(strict_types=1);

/**
 * S02-P06 OTC / Power 状态机 + fail-closed + 只读投影集成测试
 * （独立 CLI 脚本，无需 PHPUnit，SQLite in-memory）。
 *
 * 覆盖 07 §S02-P06 验证项（外审 P1-1/P1-2 修复后）：
 *   1. 纯状态转移（O1/O3/O4/O11）完整实现 + Guard/Role（owner / review_required / KYC_REVIEWER+OPS_OPERATOR）
 *   2. 带经济副作用的转移（O5/O6/O7/O8/O9/O10/O12）→ FAIL_CLOSED（DEPENDENCY_UNAVAILABLE）
 *   3. O2（draft→matching）资格依赖 06 TBC → FAIL_CLOSED（先过 owner + review_required 守卫）
 *   4. OtcTrade 单态 completed append-only 只读查询
 *   5. fail-closed：quote / createOrder / recordTrade
 *   6. 只读投影（detail / listByUser / listByOrder）
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

function makeOrder(OtcOrderService $svc, string $id, string $userId, string $status, int $reviewRequired = 0): void
{
    $svc->create([
        'otc_order_id'    => $id,
        'user_id'         => $userId,
        'side'            => OtcOrderModel::SIDE_BUY,
        'status'          => $status,
        'review_required' => $reviewRequired,
        'object_version'  => 0,
    ]);
}

echo "=====================================================\n";
echo "S02-P06 OTC / Power state machine test（外审修复后）\n";
echo "=====================================================\n\n";

$orderSvc = new OtcOrderService();
$tradeSvc = new OtcTradeService();

// ======================= 1. 纯状态转移（O1/O3/O4/O11）+ Guard/Role =======================
echo "[1] 纯状态转移（O1/O3/O4/O11）+ Guard/Role\n";
// O1: draft → review（review_required=1 + owner）
makeOrder($orderSvc, 'O1', 'U1', OtcOrderModel::STATUS_DRAFT, 1);
$orderSvc->submitReview('O1', 'U1', 'END_USER');
check((string) $orderSvc->get('O1')->status === OtcOrderModel::STATUS_REVIEW, 'O1 submitReview(owner) → review');

// O1 负向：非 owner
makeOrder($orderSvc, 'O1X', 'U1', OtcOrderModel::STATUS_DRAFT, 1);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submitReview('O1X', 'U99', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O1 submitReview(非 owner) → AUTH_FORBIDDEN');

// O1 负向：review_required=0（该订单应走 O2）
makeOrder($orderSvc, 'O1Y', 'U1', OtcOrderModel::STATUS_DRAFT, 0);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submitReview('O1Y', 'U1', 'END_USER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'O1 submitReview(review_required=0) → OBJECT_VERSION_CONFLICT');

// O3: review → matching（KYC_REVIEWER）
makeOrder($orderSvc, 'O3', 'U2', OtcOrderModel::STATUS_REVIEW);
$orderSvc->approveReview('O3', 'KYC', 'KYC_REVIEWER');
check((string) $orderSvc->get('O3')->status === OtcOrderModel::STATUS_MATCHING, 'O3 approveReview(KYC_REVIEWER) → matching');

// O3 负向：END_USER 无权审批
makeOrder($orderSvc, 'O3X', 'U2', OtcOrderModel::STATUS_REVIEW);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->approveReview('O3X', 'U1', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O3 approveReview(END_USER) → AUTH_FORBIDDEN');

// O4: review → rejected（OPS_OPERATOR）
makeOrder($orderSvc, 'O4', 'U3', OtcOrderModel::STATUS_REVIEW);
$orderSvc->reject('O4', 'OPS', 'OPS_OPERATOR');
check((string) $orderSvc->get('O4')->status === OtcOrderModel::STATUS_REJECTED, 'O4 reject(OPS_OPERATOR) → rejected');

// O4 负向：END_USER 无权驳回
makeOrder($orderSvc, 'O4X', 'U3', OtcOrderModel::STATUS_REVIEW);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->reject('O4X', 'U3', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O4 reject(END_USER) → AUTH_FORBIDDEN');

// O11: completed → disputed（owner）
makeOrder($orderSvc, 'O11', 'U4', OtcOrderModel::STATUS_COMPLETED);
$orderSvc->dispute('O11', 'U4', 'END_USER');
check((string) $orderSvc->get('O11')->status === OtcOrderModel::STATUS_DISPUTED, 'O11 dispute(owner) → disputed');

// O11 负向：非 owner
makeOrder($orderSvc, 'O11X', 'U4', OtcOrderModel::STATUS_COMPLETED);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->dispute('O11X', 'U99', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O11 dispute(非 owner) → AUTH_FORBIDDEN');
echo "\n";

// ======================= 2. 经济副作用转移（O5/O6/O7/O8/O9/O10/O12）→ FAIL_CLOSED =======================
echo "[2] 经济副作用转移（O5/O6/O7/O8/O9/O10/O12）→ FAIL_CLOSED\n";
// O5: matching → partial
makeOrder($orderSvc, 'O5', 'U5', OtcOrderModel::STATUS_MATCHING);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->partialFill('O5', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O5 partialFill → DEPENDENCY_UNAVAILABLE');

// O6: matching → completed
makeOrder($orderSvc, 'O6', 'U5', OtcOrderModel::STATUS_MATCHING);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->completeFromMatching('O6', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O6 completeFromMatching → DEPENDENCY_UNAVAILABLE');

// O7: matching → cancelled（owner 守卫先过，再 fail-closed）
makeOrder($orderSvc, 'O7', 'U6', OtcOrderModel::STATUS_MATCHING);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->cancel('O7', 'U6', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O7 cancel(owner) → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->cancel('O7', 'U99', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O7 cancel(非 owner) → AUTH_FORBIDDEN');

// O8: matching → expired
makeOrder($orderSvc, 'O8', 'U5', OtcOrderModel::STATUS_MATCHING);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->expire('O8', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O8 expire → DEPENDENCY_UNAVAILABLE');

// O9: partial → completed
makeOrder($orderSvc, 'O9', 'U5', OtcOrderModel::STATUS_PARTIAL);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->completeFromPartial('O9', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O9 completeFromPartial → DEPENDENCY_UNAVAILABLE');

// O10（cancelled 分支）：partial → cancelled
makeOrder($orderSvc, 'O10a', 'U7', OtcOrderModel::STATUS_PARTIAL);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->cancelRemaining('O10a', 'U7', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O10 cancelRemaining(owner) → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->cancelRemaining('O10a', 'U99', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O10 cancelRemaining(非 owner) → AUTH_FORBIDDEN');

// O10（expired 分支）：partial → expired
makeOrder($orderSvc, 'O10b', 'U7', OtcOrderModel::STATUS_PARTIAL);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->expireRemaining('O10b', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O10 expireRemaining → DEPENDENCY_UNAVAILABLE');

// O12（cancelled 分支）：disputed → cancelled（RISK_APPROVER 守卫先过，再 fail-closed）
makeOrder($orderSvc, 'O12a', 'U8', OtcOrderModel::STATUS_DISPUTED);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->resolveDisputeCancel('O12a', 'RA', 'RISK_APPROVER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O12 resolveDisputeCancel(RISK_APPROVER) → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->resolveDisputeCancel('O12a', 'U8', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O12 resolveDisputeCancel(END_USER) → AUTH_FORBIDDEN');

// O12（completed 分支）：disputed → completed
makeOrder($orderSvc, 'O12b', 'U8', OtcOrderModel::STATUS_DISPUTED);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->resolveDisputeComplete('O12b', 'RA', 'RISK_APPROVER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O12 resolveDisputeComplete(RISK_APPROVER) → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 3. O2 资格依赖 fail-closed =======================
echo "[3] O2（draft→matching）资格依赖 06 TBC → FAIL_CLOSED\n";
// O2: draft(review_required=0) → matching（owner 守卫先过，资格依赖 fail-closed）
makeOrder($orderSvc, 'O2', 'U9', OtcOrderModel::STATUS_DRAFT, 0);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submitMatching('O2', 'U9', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'O2 submitMatching(owner,review_required=0) → DEPENDENCY_UNAVAILABLE');

// O2 负向：review_required=1（应先走 O1）
makeOrder($orderSvc, 'O2X', 'U9', OtcOrderModel::STATUS_DRAFT, 1);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submitMatching('O2X', 'U9', 'END_USER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'O2 submitMatching(review_required=1) → OBJECT_VERSION_CONFLICT');

// O2 负向：非 owner
makeOrder($orderSvc, 'O2Y', 'U9', OtcOrderModel::STATUS_DRAFT, 0);
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submitMatching('O2Y', 'U99', 'END_USER');
}, ErrorDict::AUTH_FORBIDDEN, 'O2 submitMatching(非 owner) → AUTH_FORBIDDEN');

// O2 负向：订单不存在
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submitMatching('NOPE', 'U9', 'END_USER');
}, ErrorDict::VALIDATION_ERROR, 'O2 submitMatching(不存在) → VALIDATION_ERROR');
echo "\n";

// ======================= 4. OtcTrade 单态 + 只读查询 =======================
echo "[4] OtcTrade 单态 completed + 只读查询\n";
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

// ======================= 5. 只读投影 =======================
echo "[5] 只读投影（detail / listByUser）\n";
$odetail = $orderSvc->detail('O1');
check($odetail['otc_order_id'] === 'O1', 'order detail.otc_order_id=O1');
check($odetail['status'] === OtcOrderModel::STATUS_REVIEW, 'order detail.status=review');

$olist = $orderSvc->listByUser('U1');
check(count($olist['orders']) >= 1, 'listByUser(U1) 数量>=1');

expectDomainException(function () use ($orderSvc) {
    $orderSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'order detail(不存在) → VALIDATION_ERROR');
expectDomainException(function () use ($tradeSvc) {
    $tradeSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'trade detail(不存在) → VALIDATION_ERROR');
echo "\n";

// ======================= 6. fail-closed（quote/createOrder/recordTrade） =======================
echo "[6] fail-closed（quote/createOrder/recordTrade）\n";
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
