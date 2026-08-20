<?php

declare(strict_types=1);

/**
 * S02-P09 后端接线契约测试（Controller → Service 静态映射）。
 *
 * 独立 CLI 脚本（无需 PHPUnit / DB）。验证：
 *  1. 新增 V2 只读控制器均 extends support\controller\ApiV2；
 *  2. 控制器引用的 Service 类与方法真实存在；
 *  3. 控制器方法与 openapi paths/*.yaml operationId 命名对齐（弱校验，仅测存在性）。
 */

require __DIR__ . '/_bootstrap.php';

use support\controller\ApiV2;

function controllerExists(string $class): bool
{
    return class_exists($class);
}

function serviceMethodExists(string $service, string $method): bool
{
    return class_exists($service) && method_exists($service, $method);
}

// ---- 1. 控制器存在且继承 ApiV2 ----
$controllers = [
    \app\api\controller\LedgerController::class,
    \app\api\controller\RobotController::class,
    \app\api\controller\ParameterController::class,
    \app\api\controller\PredictionController::class,
    \app\api\controller\OtcController::class,
    \app\admin\controller\v2\AdminV2Controller::class,
];
foreach ($controllers as $c) {
    check(controllerExists($c), "controller exists: {$c}");
    check(is_subclass_of($c, ApiV2::class), "extends ApiV2: {$c}");
}

// ---- 2. Service 绑定存在 ----
check(serviceMethodExists(\library\service\ledger\AptAccountService::class, 'getByUser'), 'AptAccountService::getByUser');
check(serviceMethodExists(\library\service\ledger\AptAccountService::class, 'getEffectiveAvailable'), 'AptAccountService::getEffectiveAvailable');
check(serviceMethodExists(\library\service\ledger\AptAccountService::class, 'getAggregateDisputeHold'), 'AptAccountService::getAggregateDisputeHold');
check(serviceMethodExists(\library\service\ledger\LedgerService::class, 'getByAccount'), 'LedgerService::getByAccount');
check(serviceMethodExists(\library\service\power\PowerPositionService::class, 'getByUser'), 'PowerPositionService::getByUser');
check(serviceMethodExists(\library\service\robot\RobotService::class, 'summary'), 'RobotService::summary');
check(serviceMethodExists(\library\service\robot\RobotService::class, 'detail'), 'RobotService::detail');
check(serviceMethodExists(\library\service\robot\RobotService::class, 'allowedActions'), 'RobotService::allowedActions');
check(serviceMethodExists(\library\service\robot\RobotUpgradeOrderService::class, 'getByRobot'), 'RobotUpgradeOrderService::getByRobot');
check(serviceMethodExists(\library\service\robot\RobotRewardService::class, 'listByUser'), 'RobotRewardService::listByUser');
check(serviceMethodExists(\library\service\parameter\ParameterReleaseService::class, 'getActive'), 'ParameterReleaseService::getActive');
check(serviceMethodExists(\library\service\parameter\ParameterReleaseService::class, 'detail'), 'ParameterReleaseService::detail');
check(serviceMethodExists(\library\service\parameter\ParameterSnapshotService::class, 'detail'), 'ParameterSnapshotService::detail');
check(serviceMethodExists(\library\service\prediction\PredictionMarketService::class, 'listByEvent'), 'PredictionMarketService::listByEvent');
check(serviceMethodExists(\library\service\prediction\PredictionMarketService::class, 'detail'), 'PredictionMarketService::detail');
check(serviceMethodExists(\library\service\prediction\PredictionOrderService::class, 'listByUser'), 'PredictionOrderService::listByUser');
check(serviceMethodExists(\library\service\prediction\PredictionOrderService::class, 'detail'), 'PredictionOrderService::detail');
check(serviceMethodExists(\library\service\policy\ConsentReceiptService::class, 'getByUser'), 'ConsentReceiptService::getByUser');
check(serviceMethodExists(\library\service\otc\OtcOrderService::class, 'listByUser'), 'OtcOrderService::listByUser');
check(serviceMethodExists(\library\service\otc\OtcOrderService::class, 'detail'), 'OtcOrderService::detail');
check(serviceMethodExists(\library\service\otc\OtcTradeService::class, 'getByBuyer'), 'OtcTradeService::getByBuyer');
check(serviceMethodExists(\library\service\otc\OtcTradeService::class, 'getBySeller'), 'OtcTradeService::getBySeller');
check(serviceMethodExists(\library\service\otc\OtcEligibilityProjectionService::class, 'getEligibility'), 'OtcEligibilityProjectionService::getEligibility');
check(serviceMethodExists(\library\service\audit\AuditEventService::class, 'listAdmin'), 'AuditEventService::listAdmin');
check(serviceMethodExists(\library\service\admin\AdminUserDtoService::class, 'list'), 'AdminUserDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminOtcDtoService::class, 'list'), 'AdminOtcDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminRobotDtoService::class, 'list'), 'AdminRobotDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminTicketDtoService::class, 'list'), 'AdminTicketDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminLedgerDtoService::class, 'list'), 'AdminLedgerDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminRiskDtoService::class, 'list'), 'AdminRiskDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminApprovalDtoService::class, 'list'), 'AdminApprovalDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminConfigDtoService::class, 'list'), 'AdminConfigDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminPredictionDtoService::class, 'list'), 'AdminPredictionDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminPowerDtoService::class, 'list'), 'AdminPowerDtoService::list');
check(serviceMethodExists(\library\service\admin\AdminRewardDtoService::class, 'list'), 'AdminRewardDtoService::list');
check(serviceMethodExists(\library\service\otc\OtcOrderService::class, 'detail'), 'OtcOrderService::detail');
check(serviceMethodExists(\library\service\robot\RobotService::class, 'detail'), 'RobotService::detail');
check(serviceMethodExists(\library\service\support\TicketService::class, 'detail'), 'TicketService::detail');

// ---- 3. 控制器方法存在 ----
foreach ([
    \app\api\controller\LedgerController::class => ['asset', 'ledgerEntries', 'power'],
    \app\api\controller\RobotController::class => ['userSummary', 'list', 'detail', 'actions', 'upgradeOrders', 'rewards', 'upgradeOrderCreate', 'rewardClaim'],
    \app\api\controller\ParameterController::class => ['activeRelease', 'snapshot'],
    \app\api\controller\PredictionController::class => ['markets', 'marketDetail', 'myOrders', 'orderReceipt', 'myConsentReceipts', 'orderCreate', 'orderAddition', 'appealCreate'],
    \app\api\controller\OtcController::class => ['orderBook', 'orderDetail', 'userOrders', 'trades', 'eligibility', 'quote', 'orderCreate', 'orderCancel'],
    \app\admin\controller\v2\AdminV2Controller::class => ['auditLog', 'asyncJob', 'exportTask', 'users', 'otcOrders', 'robots', 'tickets', 'ledgerAccounts', 'riskCases', 'approvalTasks', 'parameterDefinitions', 'predictionMarkets', 'powerAccounts', 'rewardOps', 'otcOrderDetail', 'robotDetail', 'ticketDetail'],
] as $ctrl => $methods) {
    foreach ($methods as $m) {
        check(method_exists($ctrl, $m), "controller method: {$ctrl}::{$m}");
    }
}

summary();
