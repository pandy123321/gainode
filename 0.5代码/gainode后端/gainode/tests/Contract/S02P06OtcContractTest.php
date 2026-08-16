<?php

declare(strict_types=1);

/**
 * S02-P06 OTC / Power 契约测试（独立 CLI 脚本，无需 PHPUnit，不触数据库）。
 *
 * 覆盖纯逻辑：领域状态常量冻结、Event Catalog、fail-closed 写路径、V2 错误码 HTTP 映射。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\model\otc\OtcOrderModel;
use library\model\otc\OtcTradeModel;
use library\service\otc\OtcOrderService;
use library\service\otc\OtcTradeService;
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
echo "S02-P06 OTC / Power contract test\n";
echo "=====================================================\n\n";

// ======================= 1. 领域状态常量（MC1/05 §4 冻结） =======================
echo "[1] 领域状态常量（MC1/05 §4 冻结）\n";
check(OtcOrderModel::STATUSES === ['draft', 'review', 'matching', 'partial', 'completed', 'cancelled', 'expired', 'rejected', 'disputed'], 'OtcOrder 九态冻结');
check(OtcOrderModel::SIDE_BUY === 'BUY', 'OtcOrder side BUY 冻结');
check(OtcOrderModel::SIDE_SELL === 'SELL', 'OtcOrder side SELL 冻结');
check(OtcTradeModel::STATUSES === ['completed'], 'OtcTrade 单态 completed 冻结');
check(OtcTradeModel::STATUS_COMPLETED === 'completed', 'OtcTrade STATUS_COMPLETED 冻结');
echo "\n";

// ======================= 2. Event Catalog =======================
echo "[2] Event Catalog（MC2 §5）\n";
check(OtcOrderService::EVENT_CREATED === 'OTC_ORDER_CREATED', 'EVENT_CREATED');
check(OtcOrderService::EVENT_SUBMITTED_REVIEW === 'OTC_ORDER_SUBMITTED_REVIEW', 'EVENT_SUBMITTED_REVIEW');
check(OtcOrderService::EVENT_SUBMITTED_MATCHING === 'OTC_ORDER_SUBMITTED_MATCHING', 'EVENT_SUBMITTED_MATCHING');
check(OtcOrderService::EVENT_REVIEW_APPROVED === 'OTC_ORDER_REVIEW_APPROVED', 'EVENT_REVIEW_APPROVED');
check(OtcOrderService::EVENT_REJECTED === 'OTC_ORDER_REJECTED', 'EVENT_REJECTED');
check(OtcOrderService::EVENT_PARTIAL_FILLED === 'OTC_ORDER_PARTIAL_FILLED', 'EVENT_PARTIAL_FILLED');
check(OtcOrderService::EVENT_COMPLETED === 'OTC_ORDER_COMPLETED', 'EVENT_COMPLETED');
check(OtcOrderService::EVENT_CANCELLED === 'OTC_ORDER_CANCELLED', 'EVENT_CANCELLED');
check(OtcOrderService::EVENT_EXPIRED === 'OTC_ORDER_EXPIRED', 'EVENT_EXPIRED');
check(OtcOrderService::EVENT_DISPUTED === 'OTC_ORDER_DISPUTED', 'EVENT_DISPUTED');
check(OtcOrderService::EVENT_DISPUTE_RESOLVED === 'OTC_ORDER_DISPUTE_RESOLVED', 'EVENT_DISPUTE_RESOLVED');
check(OtcTradeService::EVENT_TRADE_RECORDED === 'OTC_TRADE_RECORDED', 'EVENT_TRADE_RECORDED');
echo "\n";

// ======================= 3. fail-closed 写路径 =======================
echo "[3] fail-closed 写路径（DEPENDENCY_UNAVAILABLE，不触 DB）\n";
$orderSvc = new OtcOrderService();
expectDomainException(function () use ($orderSvc) {
    $orderSvc->quote([], 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'OTC quote → DEPENDENCY_UNAVAILABLE');
expectDomainException(function () use ($orderSvc) {
    $orderSvc->createOrder([], 'U1', 'END_USER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'OTC createOrder → DEPENDENCY_UNAVAILABLE');

$tradeSvc = new OtcTradeService();
expectDomainException(function () use ($tradeSvc) {
    $tradeSvc->recordTrade([], 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'OTC recordTrade → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 4. V2 错误码 HTTP 映射 =======================
echo "[4] V2 错误码 HTTP 映射（05 §7）\n";
check(ErrorDict::httpStatus(ErrorDict::DEPENDENCY_UNAVAILABLE) === 503, 'DEPENDENCY_UNAVAILABLE → 503');
check(ErrorDict::httpStatus(ErrorDict::OBJECT_VERSION_CONFLICT) === 409, 'OBJECT_VERSION_CONFLICT → 409');
check(ErrorDict::httpStatus(ErrorDict::VALIDATION_ERROR) === 400, 'VALIDATION_ERROR → 400');
check(ErrorDict::httpStatus(ErrorDict::INSUFFICIENT_POWER) === 422, 'INSUFFICIENT_POWER → 422');
check(ErrorDict::httpStatus(ErrorDict::INSUFFICIENT_APT) === 422, 'INSUFFICIENT_APT → 422');
check(ErrorDict::httpStatus(ErrorDict::QUOTE_EXPIRED) === 409, 'QUOTE_EXPIRED → 409');
echo "\n";

summary();
