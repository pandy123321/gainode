<?php

declare(strict_types=1);

/**
 * S02-P07 Approval / Parameter / Risk / Support / Notice / Audit 契约测试
 * （独立 CLI 脚本，无需 PHPUnit，不触数据库）。
 *
 * 覆盖纯逻辑：领域状态常量冻结、Event Catalog、fail-closed 写路径、V2 错误码 HTTP 映射。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\model\approval\ApprovalRequestModel;
use library\model\parameter\ParameterReleaseModel;
use library\model\risk\RiskCaseModel;
use library\model\support\TicketModel;
use library\model\notice\NoticeModel;
use library\model\notice\NotificationDeliveryModel;
use library\service\approval\ApprovalRequestService;
use library\service\parameter\ParameterReleaseService;
use library\service\risk\RiskCaseService;
use library\service\support\TicketService;
use library\service\support\TicketAttachmentService;
use library\service\notice\NoticeService;
use library\service\notice\NotificationDeliveryService;
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
echo "S02-P07 Policy / Governance contract test\n";
echo "=====================================================\n\n";

// ======================= 1. 领域状态常量（05 §4 / §4 V2.4 冻结） =======================
echo "[1] 领域状态常量（05 §4 / §4 V2.4 冻结）\n";
check(ApprovalRequestModel::STATUSES === ['draft', 'pending', 'changes_requested', 'approved', 'rejected', 'executing', 'executed', 'failed'], 'ApprovalRequest 八态冻结');
check(ParameterReleaseModel::STATUSES === ['draft', 'pending_approval', 'approved', 'scheduled', 'active', 'paused', 'rolled_back', 'archived'], 'ParameterRelease 八态冻结');
check(RiskCaseModel::STATUSES === ['open', 'investigating', 'under_review', 'resolved', 'closed'], 'RiskCase 五态冻结（2B2-ENUM-03）');
check(TicketModel::STATUSES === ['submitted', 'in_progress', 'waiting_user', 'under_review', 'resolved', 'closed'], 'Ticket 六态冻结');
check(NotificationDeliveryModel::STATUSES === ['pending', 'delivered', 'failed', 'cancelled'], 'NotificationDelivery 四态冻结（2B2-ENUM-01）');
check(NotificationDeliveryModel::CHANNELS === ['PUSH', 'EMAIL', 'SMS', 'IN_APP'], 'NotificationDelivery 渠道冻结');
check(NoticeModel::PRIORITIES === ['INFO', 'WARNING', 'CRITICAL'], 'Notice 优先级冻结');
check(NoticeModel::READ_STATES === ['unread', 'read'], 'Notice read_state 冻结');
echo "\n";

// ======================= 2. Event Catalog（ENTITY_ACTION 命名） =======================
echo "[2] Event Catalog\n";
check(ApprovalRequestService::EVENT_SUBMITTED === 'APPROVAL_SUBMITTED', 'APPROVAL_SUBMITTED');
check(ApprovalRequestService::EVENT_CHANGES_REQUESTED === 'APPROVAL_CHANGES_REQUESTED', 'APPROVAL_CHANGES_REQUESTED');
check(ApprovalRequestService::EVENT_RESUBMITTED === 'APPROVAL_RESUBMITTED', 'APPROVAL_RESUBMITTED');
check(ApprovalRequestService::EVENT_APPROVED === 'APPROVAL_APPROVED', 'APPROVAL_APPROVED');
check(ApprovalRequestService::EVENT_REJECTED === 'APPROVAL_REJECTED', 'APPROVAL_REJECTED');
check(ApprovalRequestService::EVENT_EXECUTION_STARTED === 'APPROVAL_EXECUTION_STARTED', 'APPROVAL_EXECUTION_STARTED');
check(ApprovalRequestService::EVENT_EXECUTION_COMPLETED === 'APPROVAL_EXECUTION_COMPLETED', 'APPROVAL_EXECUTION_COMPLETED');
check(ApprovalRequestService::EVENT_EXECUTION_FAILED === 'APPROVAL_EXECUTION_FAILED', 'APPROVAL_EXECUTION_FAILED');
check(ParameterReleaseService::EVENT_SUBMITTED === 'PARAMETER_RELEASE_SUBMITTED', 'PARAMETER_RELEASE_SUBMITTED');
check(ParameterReleaseService::EVENT_APPROVED === 'PARAMETER_RELEASE_APPROVED', 'PARAMETER_RELEASE_APPROVED');
check(ParameterReleaseService::EVENT_ACTIVATED === 'PARAMETER_RELEASE_ACTIVATED', 'PARAMETER_RELEASE_ACTIVATED');
check(RiskCaseService::EVENT_INVESTIGATING === 'RISK_CASE_INVESTIGATING', 'RISK_CASE_INVESTIGATING');
check(RiskCaseService::EVENT_RESOLVED === 'RISK_CASE_RESOLVED', 'RISK_CASE_RESOLVED');
check(TicketService::EVENT_ACCEPTED === 'TICKET_ACCEPTED', 'TICKET_ACCEPTED');
check(TicketService::EVENT_RESOLVED === 'TICKET_RESOLVED', 'TICKET_RESOLVED');
check(NoticeService::EVENT_READ === 'NOTICE_READ', 'NOTICE_READ');
check(NotificationDeliveryService::EVENT_DELIVERED === 'NOTIFICATION_DELIVERED', 'NOTIFICATION_DELIVERED');
check(NotificationDeliveryService::EVENT_FAILED === 'NOTIFICATION_DELIVERY_FAILED', 'NOTIFICATION_DELIVERY_FAILED');
echo "\n";

// ======================= 3. fail-closed 写路径 =======================
echo "[3] fail-closed 写路径（DEPENDENCY_UNAVAILABLE，不触 DB）\n";
$riskSvc = new RiskCaseService();
expectDomainException(function () use ($riskSvc) {
    $riskSvc->execute('C1', 'RA', 'RISK_APPROVER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'RiskCase execute → DEPENDENCY_UNAVAILABLE（风险策略 TBC）');

$attSvc = new TicketAttachmentService();
expectDomainException(function () use ($attSvc) {
    $attSvc->create([]);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'TicketAttachment create → DEPENDENCY_UNAVAILABLE（对象存储/病毒 TBC）');

$deliverySvc = new NotificationDeliveryService();
expectDomainException(function () use ($deliverySvc) {
    $deliverySvc->deliver('D1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'NotificationDelivery deliver → DEPENDENCY_UNAVAILABLE（渠道 TBC）');
echo "\n";

// ======================= 4. V2 错误码 HTTP 映射 =======================
echo "[4] V2 错误码 HTTP 映射（05 §7）\n";
check(ErrorDict::httpStatus(ErrorDict::DEPENDENCY_UNAVAILABLE) === 503, 'DEPENDENCY_UNAVAILABLE → 503');
check(ErrorDict::httpStatus(ErrorDict::OBJECT_VERSION_CONFLICT) === 409, 'OBJECT_VERSION_CONFLICT → 409');
check(ErrorDict::httpStatus(ErrorDict::POLICY_DENIED) === 403, 'POLICY_DENIED → 403（SoD 守卫）');
check(ErrorDict::httpStatus(ErrorDict::VALIDATION_ERROR) === 400, 'VALIDATION_ERROR → 400');
check(ErrorDict::httpStatus(ErrorDict::AUTH_FORBIDDEN) === 403, 'AUTH_FORBIDDEN → 403');
echo "\n";

summary();
