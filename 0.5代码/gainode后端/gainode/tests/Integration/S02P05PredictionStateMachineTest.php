<?php

declare(strict_types=1);

/**
 * S02-P05 Prediction P0 状态机 + fail-closed + 只读投影集成测试
 * （独立 CLI 脚本，无需 PHPUnit，SQLite in-memory）。
 *
 * 覆盖 07 §S02-P05 验证项：
 *   1. Market 状态机：合法/非法转移 + CAS（M1-M12）
 *   2. Order 状态机：P1-P4 + fail-closed 退款/纠错
 *   3. Result 状态机：RS3/RS4/RS5 + correction_version 仅一次守卫
 *   4. Settlement 状态机：ST1/ST3/ST4/ST6/ST7 + fail-closed calculate/pay
 *   5. SettlementBatch 状态机 + fail-closed create
 *   6. RefundCase / CorrectionCase 状态机 + fail-closed create/complete
 *   7. ConsentReceipt grant 幂等去重 + expire
 *   8. 只读投影（detail/list/allowedActions）
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use library\model\policy\ConsentReceiptModel;
use library\model\prediction\CorrectionCaseModel;
use library\model\prediction\PredictionMarketModel;
use library\model\prediction\PredictionOrderModel;
use library\model\prediction\RefundCaseModel;
use library\model\prediction\ResultModel;
use library\model\prediction\SettlementBatchModel;
use library\model\prediction\SettlementModel;
use library\service\policy\ConsentReceiptService;
use library\service\prediction\CorrectionCaseService;
use library\service\prediction\PredictionMarketService;
use library\service\prediction\PredictionOrderService;
use library\service\prediction\RefundCaseService;
use library\service\prediction\ResultService;
use library\service\prediction\SettlementBatchService;
use library\service\prediction\SettlementService;
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

$mk('prediction_markets', function ($t) {
    $t->string('market_id', 32)->primary();
    $t->string('event_id', 32)->default('');
    $t->string('template_id', 64)->default('FOOTBALL_PREMATCH_1X2');
    $t->string('market_status', 24)->default('draft');
    $t->integer('lock_at')->default(0);
    $t->text('selections')->nullable();
    $t->text('liquidity_summary')->nullable();
    $t->string('result_status', 24)->nullable();
    $t->string('idempotency_key', 64)->nullable();
    $t->string('rule_version', 64)->default('');
    $t->string('parameter_release_id', 32)->default('0');
    $t->string('policy_version', 64)->default('');
    $t->string('snapshot_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('prediction_orders', function ($t) {
    $t->string('order_id', 32)->primary();
    $t->string('user_id', 32);
    $t->string('market_id', 32);
    $t->string('selection', 16)->default('HOME');
    $t->string('amount_apt', 64)->default('0');
    $t->string('order_status', 24)->default('submitted');
    $t->string('asset_status', 32)->nullable();
    $t->string('risk_status', 32)->nullable();
    $t->string('consent_receipt_id', 32)->default('0');
    $t->string('submit_snapshot_id', 32)->default('0');
    $t->string('parameter_release_id', 32)->default('0');
    $t->string('policy_version', 64)->default('');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('results', function ($t) {
    $t->string('result_id', 32)->primary();
    $t->string('market_id', 32);
    $t->string('event_id', 32)->default('');
    $t->text('scores')->nullable();
    $t->string('outcome', 16)->default('HOME');
    $t->string('status', 16)->default('provisional');
    $t->string('confirmed_by', 32)->default('0');
    $t->integer('confirmed_at')->default(0);
    $t->text('evidence_ids')->nullable();
    $t->string('dispute_reason_code', 64)->default('');
    $t->integer('correction_version')->default(0);
    $t->string('rule_version', 64)->default('');
    $t->string('snapshot_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('settlements', function ($t) {
    $t->string('settlement_id', 32)->primary();
    $t->string('market_id', 32);
    $t->string('batch_id', 32)->default('0');
    $t->string('status', 24)->default('queued');
    $t->string('principal_total_apt', 64)->default('0');
    $t->string('reward_total_apt', 64)->default('0');
    $t->string('service_fee_total_apt', 64)->default('0');
    $t->string('ledger_batch_id', 32)->default('0');
    $t->string('approved_by', 32)->default('0');
    $t->integer('executed_at')->default(0);
    $t->string('rule_version', 64)->default('');
    $t->string('parameter_release_id', 32)->default('0');
    $t->string('snapshot_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('settlement_batches', function ($t) {
    $t->string('batch_id', 32)->primary();
    $t->string('status', 24)->default('created');
    $t->integer('market_count')->default(0);
    $t->integer('order_count')->default(0);
    $t->text('settlement_ids')->nullable();
    $t->string('total_principal_apt', 64)->default('0');
    $t->string('total_reward_apt', 64)->default('0');
    $t->string('total_service_fee_apt', 64)->default('0');
    $t->integer('executed_at')->default(0);
    $t->string('rule_version', 64)->default('');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('refund_cases', function ($t) {
    $t->string('refund_id', 32)->primary();
    $t->string('market_id', 32);
    $t->integer('batch_size')->default(0);
    $t->string('principal_total_apt', 64)->default('0');
    $t->string('service_fee_total_apt', 64)->default('0');
    $t->string('status', 24)->default('pending');
    $t->string('approved_by', 32)->default('0');
    $t->integer('executed_at')->default(0);
    $t->text('ledger_batch_ids')->nullable();
    $t->string('reason_code', 64)->default('');
    $t->string('case_id', 32)->default('0');
    $t->string('approval_id', 32)->default('0');
    $t->string('rule_version', 64)->default('');
    $t->string('snapshot_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('correction_cases', function ($t) {
    $t->string('correction_id', 32)->primary();
    $t->string('market_id', 32);
    $t->string('result_id_old', 32)->default('0');
    $t->string('result_id_new', 32)->default('0');
    $t->text('settlement_ids_old')->nullable();
    $t->text('settlement_ids_new')->nullable();
    $t->string('status', 24)->default('pending');
    $t->string('approved_by', 32)->default('0');
    $t->integer('executed_at')->default(0);
    $t->text('ledger_reversal_ids')->nullable();
    $t->text('ledger_new_ids')->nullable();
    $t->string('case_id', 32)->default('0');
    $t->string('approval_id', 32)->default('0');
    $t->text('evidence_ids')->nullable();
    $t->string('rule_version', 64)->default('');
    $t->string('snapshot_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('consent_receipts', function ($t) {
    $t->string('receipt_id', 32)->primary();
    $t->string('user_id', 32);
    $t->string('consent_type', 32);
    $t->string('consent_version', 32)->default('');
    $t->string('content_hash', 128)->default('');
    $t->string('status', 16)->default('active');
    $t->integer('agreed_at')->default(0);
    $t->integer('expires_at')->default(0);
    $t->string('policy_version', 64)->default('');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 128)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
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
echo "S02-P05 prediction P0 state machine test\n";
echo "=====================================================\n\n";

$marketSvc   = new PredictionMarketService();
$orderSvc    = new PredictionOrderService();
$resultSvc   = new ResultService();
$settleSvc   = new SettlementService();
$batchSvc    = new SettlementBatchService();
$refundSvc   = new RefundCaseService();
$correctSvc  = new CorrectionCaseService();
$consentSvc  = new ConsentReceiptService();

// ======================= 1. Market 状态机 =======================
echo "[1] Market 状态机（draft→open→closing→locked→awaiting_result→settlement→settled）\n";
$marketSvc->create([
    'market_id'      => 'M1',
    'event_id'       => 'E1',
    'template_id'    => PredictionMarketModel::TEMPLATE_FOOTBALL_PREMATCH_1X2,
    'market_status'  => PredictionMarketModel::MARKET_STATUS_DRAFT,
    'rule_version'   => 'v1',
    'object_version' => 0,
]);
$marketSvc->publish('M1', 'OPS', 'OPS_OPERATOR');
check((string) $marketSvc->get('M1')->market_status === PredictionMarketModel::MARKET_STATUS_OPEN, 'publish → open');
expectDomainException(function () use ($marketSvc) {
    $marketSvc->publish('M1', 'OPS', 'OPS_OPERATOR');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'open 态 publish → OBJECT_VERSION_CONFLICT');

$marketSvc->startClosing('M1', 'SYS', 'SYSTEM');
check((string) $marketSvc->get('M1')->market_status === PredictionMarketModel::MARKET_STATUS_CLOSING, 'startClosing → closing');
$marketSvc->lock('M1', 'SYS', 'SYSTEM');
check((string) $marketSvc->get('M1')->market_status === PredictionMarketModel::MARKET_STATUS_LOCKED, 'lock → locked');
$marketSvc->awaitResult('M1', 'SYS', 'SYSTEM');
check((string) $marketSvc->get('M1')->market_status === PredictionMarketModel::MARKET_STATUS_AWAITING_RESULT, 'awaitResult → awaiting_result');
$marketSvc->startSettlement('M1', 'SYS', 'SYSTEM');
check((string) $marketSvc->get('M1')->market_status === PredictionMarketModel::MARKET_STATUS_SETTLEMENT, 'startSettlement → settlement');
$marketSvc->completeSettlement('M1', 'SYS', 'SYSTEM');
check((string) $marketSvc->get('M1')->market_status === PredictionMarketModel::MARKET_STATUS_SETTLED, 'completeSettlement → settled');
check((int) $marketSvc->get('M1')->object_version === 6, 'object_version → 6（6 次转移）');

// exception 旁路：M2 settlement→exception→settlement→settled(manual)
$marketSvc->create([
    'market_id'      => 'M2',
    'event_id'       => 'E2',
    'template_id'    => PredictionMarketModel::TEMPLATE_FOOTBALL_PREMATCH_1X2,
    'market_status'  => PredictionMarketModel::MARKET_STATUS_SETTLEMENT,
    'rule_version'   => 'v1',
    'object_version' => 0,
]);
$marketSvc->failSettlement('M2', 'SYS', 'SYSTEM');
check((string) $marketSvc->get('M2')->market_status === PredictionMarketModel::MARKET_STATUS_EXCEPTION, 'failSettlement → exception');
$marketSvc->completeSettlementManual('M2', 'OPS', 'RISK_APPROVER');
check((string) $marketSvc->get('M2')->market_status === PredictionMarketModel::MARKET_STATUS_SETTLED, 'completeSettlementManual → settled');

// M10：exception → settlement（重试）
$marketSvc->create([
    'market_id'      => 'M2b',
    'event_id'       => 'E2b',
    'template_id'    => PredictionMarketModel::TEMPLATE_FOOTBALL_PREMATCH_1X2,
    'market_status'  => PredictionMarketModel::MARKET_STATUS_EXCEPTION,
    'rule_version'   => 'v1',
    'object_version' => 0,
]);
$marketSvc->retrySettlement('M2b', 'SYS', 'SYSTEM');
check((string) $marketSvc->get('M2b')->market_status === PredictionMarketModel::MARKET_STATUS_SETTLEMENT, 'retrySettlement → settlement');

// void 旁路：M3 draft→void
$marketSvc->create([
    'market_id'      => 'M3',
    'event_id'       => 'E3',
    'template_id'    => PredictionMarketModel::TEMPLATE_FOOTBALL_PREMATCH_1X2,
    'market_status'  => PredictionMarketModel::MARKET_STATUS_DRAFT,
    'rule_version'   => 'v1',
    'object_version' => 0,
]);
$marketSvc->voidMarket('M3', 'OPS', 'OPS_OPERATOR');
check((string) $marketSvc->get('M3')->market_status === PredictionMarketModel::MARKET_STATUS_VOID, 'voidMarket → void');
expectDomainException(function () use ($marketSvc) {
    $marketSvc->voidMarket('M3', 'OPS', 'OPS_OPERATOR');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'void 态 voidMarket → OBJECT_VERSION_CONFLICT（终态）');
echo "\n";

// ======================= 2. Order 状态机 =======================
echo "[2] Order 状态机（submitted→locked→awaiting_result→settling→settled）\n";
$orderSvc->create([
    'order_id'       => 'O1',
    'user_id'        => 'U1',
    'market_id'      => 'M1',
    'selection'      => PredictionOrderModel::SELECTION_HOME,
    'amount_apt'     => '100.000000000000000000',
    'order_status'   => PredictionOrderModel::ORDER_STATUS_SUBMITTED,
    'object_version' => 0,
]);
$orderSvc->lock('O1', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O1')->order_status === PredictionOrderModel::ORDER_STATUS_LOCKED, 'lock → locked');
$orderSvc->awaitResult('O1', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O1')->order_status === PredictionOrderModel::ORDER_STATUS_AWAITING_RESULT, 'awaitResult → awaiting_result');
$orderSvc->startSettling('O1', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O1')->order_status === PredictionOrderModel::ORDER_STATUS_SETTLING, 'startSettling → settling');
$orderSvc->settle('O1', 'SYS', 'SYSTEM');
check((string) $orderSvc->get('O1')->order_status === PredictionOrderModel::ORDER_STATUS_SETTLED, 'settle → settled');
check((string) $orderSvc->get('O1')->audit_event_id !== '0', 'audit_event_id 已回写');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->settle('O1', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'settled 态 settle → OBJECT_VERSION_CONFLICT');
echo "\n";

// ======================= 3. Result 状态机 =======================
echo "[3] Result 状态机（RS3 uphold / RS4+RS5 corrected，仅一次）\n";
$resultSvc->create([
    'result_id'         => 'RS1',
    'market_id'         => 'M1',
    'event_id'          => 'E1',
    'outcome'           => ResultModel::OUTCOME_HOME,
    'status'            => ResultModel::STATUS_DISPUTED,
    'correction_version' => 0,
    'object_version'    => 0,
]);
$resultSvc->uphold('RS1', 'RA', 'RISK_APPROVER');
check((string) $resultSvc->get('RS1')->status === ResultModel::STATUS_OFFICIAL, 'uphold → official');

$resultSvc->correctFromOfficial('RS1', 'RA', 'RISK_APPROVER');
check((string) $resultSvc->get('RS1')->status === ResultModel::STATUS_CORRECTED, 'correctFromOfficial → corrected');
check((int) $resultSvc->get('RS1')->correction_version === 1, 'correction_version → 1');
expectDomainException(function () use ($resultSvc) {
    $resultSvc->correctFromOfficial('RS1', 'RA', 'RISK_APPROVER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'corrected 态再纠错 → OBJECT_VERSION_CONFLICT（仅一次 MC2 #11）');

// RS4：disputed → corrected
$resultSvc->create([
    'result_id'          => 'RS2',
    'market_id'          => 'M2',
    'event_id'           => 'E2',
    'outcome'            => ResultModel::OUTCOME_DRAW,
    'status'             => ResultModel::STATUS_DISPUTED,
    'correction_version' => 0,
    'object_version'     => 0,
]);
$resultSvc->correctFromDisputed('RS2', 'RA', 'RISK_APPROVER');
check((string) $resultSvc->get('RS2')->status === ResultModel::STATUS_CORRECTED, 'correctFromDisputed → corrected');

// 非法：corrected 终态不可再转移
expectDomainException(function () use ($resultSvc) {
    $resultSvc->uphold('RS2', 'RA', 'RISK_APPROVER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'corrected 态 uphold → OBJECT_VERSION_CONFLICT（终态）');
echo "\n";

// ======================= 4. Settlement 状态机 =======================
echo "[4] Settlement 状态机（queued→calculating→review→payable + failed→queued）\n";
$settleSvc->create([
    'settlement_id'     => 'ST1',
    'market_id'         => 'M1',
    'status'            => SettlementModel::STATUS_QUEUED,
    'object_version'    => 0,
]);
$settleSvc->start('ST1', 'SYS', 'SYSTEM');
check((string) $settleSvc->get('ST1')->status === SettlementModel::STATUS_CALCULATING, 'start → calculating');
$settleSvc->reviewRequired('ST1', 'SYS', 'SYSTEM');
check((string) $settleSvc->get('ST1')->status === SettlementModel::STATUS_REVIEW, 'reviewRequired → review');
$settleSvc->approveReview('ST1', 'RA', 'RISK_APPROVER');
check((string) $settleSvc->get('ST1')->status === SettlementModel::STATUS_PAYABLE, 'approveReview → payable');

// fail/retry：ST2 queued→failed→queued
$settleSvc->create([
    'settlement_id'  => 'ST2',
    'market_id'      => 'M2',
    'status'         => SettlementModel::STATUS_QUEUED,
    'object_version' => 0,
]);
$settleSvc->fail('ST2', 'SYS', 'SYSTEM');
check((string) $settleSvc->get('ST2')->status === SettlementModel::STATUS_FAILED, 'fail → failed');
$settleSvc->retry('ST2', 'SYS', 'SYSTEM');
check((string) $settleSvc->get('ST2')->status === SettlementModel::STATUS_QUEUED, 'retry → queued');
expectDomainException(function () use ($settleSvc) {
    $settleSvc->approveReview('ST2', 'RA', 'RISK_APPROVER');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'queued 态 approveReview → OBJECT_VERSION_CONFLICT');
echo "\n";

// ======================= 5. SettlementBatch 状态机 =======================
echo "[5] SettlementBatch 状态机（created→processing→completed / partially_failed→processing）\n";
$batchSvc->create([
    'batch_id'       => 'B1',
    'status'         => SettlementBatchModel::STATUS_CREATED,
    'object_version' => 0,
]);
$batchSvc->process('B1', 'SYS', 'SYSTEM');
check((string) $batchSvc->get('B1')->status === SettlementBatchModel::STATUS_PROCESSING, 'process → processing');
$batchSvc->complete('B1', 'SYS', 'SYSTEM');
check((string) $batchSvc->get('B1')->status === SettlementBatchModel::STATUS_COMPLETED, 'complete → completed');
expectDomainException(function () use ($batchSvc) {
    $batchSvc->process('B1', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'completed 态 process → OBJECT_VERSION_CONFLICT');

$batchSvc->create([
    'batch_id'       => 'B2',
    'status'         => SettlementBatchModel::STATUS_PROCESSING,
    'object_version' => 0,
]);
$batchSvc->partiallyFail('B2', 'SYS', 'SYSTEM');
check((string) $batchSvc->get('B2')->status === SettlementBatchModel::STATUS_PARTIALLY_FAILED, 'partiallyFail → partially_failed');
$batchSvc->retry('B2', 'SYS', 'SYSTEM');
check((string) $batchSvc->get('B2')->status === SettlementBatchModel::STATUS_PROCESSING, 'retry → processing');
echo "\n";

// ======================= 6. RefundCase / CorrectionCase 状态机 =======================
echo "[6] RefundCase / CorrectionCase 状态机（pending→approved→executing→failed→executing / rejected）\n";
$refundSvc->create([
    'refund_id'      => 'RF1',
    'market_id'      => 'M1',
    'status'         => RefundCaseModel::STATUS_PENDING,
    'object_version' => 0,
]);
$refundSvc->approve('RF1', 'RA', 'RISK_APPROVER');
check((string) $refundSvc->get('RF1')->status === RefundCaseModel::STATUS_APPROVED, 'approve → approved');
$refundSvc->execute('RF1', 'SYS', 'SYSTEM');
check((string) $refundSvc->get('RF1')->status === RefundCaseModel::STATUS_EXECUTING, 'execute → executing');
$refundSvc->fail('RF1', 'SYS', 'SYSTEM');
check((string) $refundSvc->get('RF1')->status === RefundCaseModel::STATUS_FAILED, 'fail → failed');
$refundSvc->retry('RF1', 'SYS', 'SYSTEM');
check((string) $refundSvc->get('RF1')->status === RefundCaseModel::STATUS_EXECUTING, 'retry → executing');

// reject 旁路
$refundSvc->create([
    'refund_id'      => 'RF2',
    'market_id'      => 'M2',
    'status'         => RefundCaseModel::STATUS_PENDING,
    'object_version' => 0,
]);
$refundSvc->reject('RF2', 'RA', 'RISK_APPROVER');
check((string) $refundSvc->get('RF2')->status === RefundCaseModel::STATUS_REJECTED, 'reject → rejected');

$correctSvc->create([
    'correction_id'  => 'CC1',
    'market_id'      => 'M1',
    'status'         => CorrectionCaseModel::STATUS_PENDING,
    'object_version' => 0,
]);
$correctSvc->approve('CC1', 'RA', 'RISK_APPROVER');
check((string) $correctSvc->get('CC1')->status === CorrectionCaseModel::STATUS_APPROVED, 'correction approve → approved');
$correctSvc->execute('CC1', 'SYS', 'SYSTEM');
check((string) $correctSvc->get('CC1')->status === CorrectionCaseModel::STATUS_EXECUTING, 'correction execute → executing');
$correctSvc->fail('CC1', 'SYS', 'SYSTEM');
check((string) $correctSvc->get('CC1')->status === CorrectionCaseModel::STATUS_FAILED, 'correction fail → failed');
$correctSvc->retry('CC1', 'SYS', 'SYSTEM');
check((string) $correctSvc->get('CC1')->status === CorrectionCaseModel::STATUS_EXECUTING, 'correction retry → executing');
echo "\n";

// ======================= 7. ConsentReceipt grant 幂等 + expire =======================
echo "[7] ConsentReceipt grant 幂等去重 + expire\n";
$consentSvc->grant('U1', 'PREDICTION_TOS', 'v1', 'hash-abc', time() + 86400, 'P1', 'U1', 'END_USER');
$r1 = $consentSvc->getByUser('U1')->first();
check($r1 !== null, 'grant 创建回执');
check((string) $r1->status === ConsentReceiptModel::STATUS_ACTIVE, 'grant → active');

// 幂等：同 user+type+version 再次 grant 不重复创建
$consentSvc->grant('U1', 'PREDICTION_TOS', 'v1', 'hash-abc', time() + 86400, 'P1', 'U1', 'END_USER');
check($consentSvc->getByUser('U1')->count() === 1, '同 type+version 幂等（仅 1 条）');

// 不同版本：新回执
$consentSvc->grant('U1', 'PREDICTION_TOS', 'v2', 'hash-def', time() + 86400, 'P1', 'U1', 'END_USER');
check($consentSvc->getByUser('U1')->count() === 2, '不同 version 新回执（2 条）');

// expire
$receiptId = (string) $r1->receipt_id;
$consentSvc->expire($receiptId, 'SYS', 'SYSTEM');
check((string) $consentSvc->get($receiptId)->status === ConsentReceiptModel::STATUS_EXPIRED, 'expire → expired');
expectDomainException(function () use ($consentSvc, $receiptId) {
    $consentSvc->expire($receiptId, 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'expired 态 expire → OBJECT_VERSION_CONFLICT（终态）');
echo "\n";

// ======================= 8. 只读投影 =======================
echo "[8] 只读投影（detail / list / allowedActions）\n";
$mdetail = $marketSvc->detail('M1');
check($mdetail['market_id'] === 'M1', 'market detail.market_id=M1');
check($mdetail['market_status'] === PredictionMarketModel::MARKET_STATUS_SETTLED, 'market detail 状态=settled');

$mlist = $marketSvc->listByEvent('E1');
check(count($mlist['markets']) === 1, 'listByEvent(E1) 数量=1');

$actions = $marketSvc->allowedActions('M1');
check($actions['allowed_actions'] === [], 'settled 态 allowed_actions=[]');
check($actions['blocked_actions'] === [], 'settled 态 blocked_actions=[]（非 open 无候选）');

// open 态候选动作进 blocked（下单 fail-closed）
$marketSvc->create([
    'market_id'      => 'M9',
    'event_id'       => 'E9',
    'template_id'    => PredictionMarketModel::TEMPLATE_FOOTBALL_PREMATCH_1X2,
    'market_status'  => PredictionMarketModel::MARKET_STATUS_OPEN,
    'rule_version'   => 'v1',
    'object_version' => 0,
]);
$actions9 = $marketSvc->allowedActions('M9');
check($actions9['allowed_actions'] === [], 'open 态 allowed_actions=[]（下单 fail-closed）');
check(in_array('place_bet', $actions9['blocked_actions'], true), 'open 态 blocked_actions 含 place_bet');

$odetail = $orderSvc->detail('O1');
check($odetail['order_id'] === 'O1', 'order detail.order_id=O1');
check($odetail['amount_apt'] === '100.000000000000000000', 'order detail.amount_apt 精确 decimal string');
$olist = $orderSvc->listByUser('U1');
check(count($olist['orders']) === 1, 'listByUser(U1) 数量=1');

$rdetail = $resultSvc->detail('RS1');
check($rdetail['status'] === ResultModel::STATUS_CORRECTED, 'result detail.status=corrected');
check($rdetail['correction_version'] === 1, 'result detail.correction_version=1');

$sdetail = $settleSvc->detail('ST1');
check($sdetail['status'] === SettlementModel::STATUS_PAYABLE, 'settlement detail.status=payable');

$bdetail = $batchSvc->detail('B1');
check($bdetail['status'] === SettlementBatchModel::STATUS_COMPLETED, 'batch detail.status=completed');

$rfdetail = $refundSvc->detail('RF1');
check($rfdetail['status'] === RefundCaseModel::STATUS_EXECUTING, 'refund detail.status=executing');

$ccdetail = $correctSvc->detail('CC1');
check($ccdetail['status'] === CorrectionCaseModel::STATUS_EXECUTING, 'correction detail.status=executing');

$cdetail = $consentSvc->detail($receiptId);
check($cdetail['status'] === ConsentReceiptModel::STATUS_EXPIRED, 'consent detail.status=expired');

expectDomainException(function () use ($marketSvc) {
    $marketSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'market detail(不存在) → VALIDATION_ERROR');
echo "\n";

// ======================= 9. fail-closed（依赖未冻结） =======================
echo "[9] fail-closed（经济/依赖写）\n";
expectDomainException(function () use ($marketSvc) {
    $marketSvc->createMarket([], 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Market create → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submit([], 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Order submit → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->startRefund('O1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Order startRefund → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($resultSvc) {
    $resultSvc->confirm('RS1', 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Result confirm → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($resultSvc) {
    $resultSvc->dispute('RS1', 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Result dispute → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($settleSvc) {
    $settleSvc->calculate('ST1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Settlement calculate → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($settleSvc) {
    $settleSvc->pay('ST1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Settlement pay → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($refundSvc) {
    $refundSvc->complete('RF1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'RefundCase complete → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($correctSvc) {
    $correctSvc->complete('CC1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'CorrectionCase complete → DEPENDENCY_UNAVAILABLE');
echo "\n";

summary();
