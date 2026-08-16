<?php

declare(strict_types=1);

/**
 * S02-P05 Prediction P0 契约测试（独立 CLI 脚本，无需 PHPUnit，不触数据库）。
 *
 * 覆盖纯逻辑：领域状态常量冻结、Event Catalog、fail-closed 写路径、V2 错误码 HTTP 映射。
 */

require __DIR__ . '/_bootstrap.php';

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
echo "S02-P05 prediction P0 contract test\n";
echo "=====================================================\n\n";

// ======================= 1. 领域状态常量（MC1/MC2 冻结） =======================
echo "[1] 领域状态常量（MC1/2B-1 冻结）\n";
check(PredictionMarketModel::MARKET_STATUSES === ['draft', 'open', 'closing', 'locked', 'awaiting_result', 'settlement', 'settled', 'void', 'exception'], 'Market 九态冻结');
check(PredictionOrderModel::ORDER_STATUSES === ['submitted', 'locked', 'awaiting_result', 'settling', 'settled', 'refunding', 'refunded', 'correcting', 'corrected'], 'Order 九态冻结');
check(ResultModel::STATUSES === ['provisional', 'official', 'disputed', 'corrected'], 'Result 四态冻结');
check(SettlementModel::STATUSES === ['queued', 'calculating', 'review', 'payable', 'paid', 'failed'], 'Settlement 六态冻结');
check(SettlementBatchModel::STATUSES === ['created', 'processing', 'completed', 'partially_failed', 'failed'], 'SettlementBatch 五态冻结');
check(RefundCaseModel::STATUSES === ['pending', 'approved', 'executing', 'completed', 'rejected', 'failed'], 'RefundCase 六态冻结');
check(CorrectionCaseModel::STATUSES === ['pending', 'approved', 'executing', 'completed', 'rejected', 'failed'], 'CorrectionCase 六态冻结');
check(ConsentReceiptModel::STATUSES === ['active', 'expired'], 'ConsentReceipt 两态冻结');
check(PredictionMarketModel::TEMPLATE_FOOTBALL_PREMATCH_1X2 === 'FOOTBALL_PREMATCH_1X2', 'P0 市场模板冻结');
echo "\n";

// ======================= 2. Event Catalog =======================
echo "[2] Event Catalog（MC2 §5）\n";
check(PredictionMarketService::EVENT_PUBLISHED === 'MARKET_PUBLISHED', 'EVENT_PUBLISHED');
check(PredictionMarketService::EVENT_SETTLED === 'MARKET_SETTLED', 'EVENT_SETTLED');
check(PredictionOrderService::EVENT_SETTLED === 'ORDER_SETTLED', 'ORDER_SETTLED');
check(ResultService::EVENT_CORRECTED === 'RESULT_CORRECTED', 'RESULT_CORRECTED');
check(SettlementService::EVENT_REVIEW_APPROVED === 'SETTLEMENT_REVIEW_APPROVED', 'SETTLEMENT_REVIEW_APPROVED');
check(ConsentReceiptService::EVENT_GRANTED === 'CONSENT_GRANTED', 'CONSENT_GRANTED');
check(ConsentReceiptService::EVENT_EXPIRED === 'CONSENT_EXPIRED', 'CONSENT_EXPIRED');
echo "\n";

// ======================= 3. fail-closed 写路径 =======================
echo "[3] fail-closed 写路径（DEPENDENCY_UNAVAILABLE，不触 DB）\n";
$marketSvc = new PredictionMarketService();
expectDomainException(function () use ($marketSvc) {
    $marketSvc->createMarket([], 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Market create → DEPENDENCY_UNAVAILABLE');

$orderSvc = new PredictionOrderService();
expectDomainException(function () use ($orderSvc) {
    $orderSvc->submit([], 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Order submit → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->startRefund('O1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Order startRefund → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->completeRefund('O1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Order completeRefund → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->startCorrect('O1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Order startCorrect → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->completeCorrect('O1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Order completeCorrect → DEPENDENCY_UNAVAILABLE');

$resultSvc = new ResultService();
expectDomainException(function () use ($resultSvc) {
    $resultSvc->confirm('R1', 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Result confirm → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($resultSvc) {
    $resultSvc->dispute('R1', 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Result dispute → DEPENDENCY_UNAVAILABLE');

$settlementSvc = new SettlementService();
expectDomainException(function () use ($settlementSvc) {
    $settlementSvc->calculate('S1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Settlement calculate → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($settlementSvc) {
    $settlementSvc->pay('S1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'Settlement pay → DEPENDENCY_UNAVAILABLE');

$batchSvc = new SettlementBatchService();
expectDomainException(function () use ($batchSvc) {
    $batchSvc->createBatch([], 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'SettlementBatch create → DEPENDENCY_UNAVAILABLE');

$refundSvc = new RefundCaseService();
expectDomainException(function () use ($refundSvc) {
    $refundSvc->createCase([], 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'RefundCase create → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($refundSvc) {
    $refundSvc->complete('RF1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'RefundCase complete → DEPENDENCY_UNAVAILABLE');

$correctionSvc = new CorrectionCaseService();
expectDomainException(function () use ($correctionSvc) {
    $correctionSvc->createCase([], 'OPS', 'OPS_OPERATOR');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'CorrectionCase create → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($correctionSvc) {
    $correctionSvc->complete('CC1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'CorrectionCase complete → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 4. V2 错误码 HTTP 映射 =======================
echo "[4] V2 错误码 HTTP 映射（05 §7）\n";
check(ErrorDict::httpStatus(ErrorDict::DEPENDENCY_UNAVAILABLE) === 503, 'DEPENDENCY_UNAVAILABLE → 503');
check(ErrorDict::httpStatus(ErrorDict::OBJECT_VERSION_CONFLICT) === 409, 'OBJECT_VERSION_CONFLICT → 409');
check(ErrorDict::httpStatus(ErrorDict::VALIDATION_ERROR) === 400, 'VALIDATION_ERROR → 400');
check(ErrorDict::httpStatus(ErrorDict::MARKET_LOCKED) === 422, 'MARKET_LOCKED → 422');
echo "\n";

summary();
