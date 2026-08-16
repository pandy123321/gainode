<?php

declare(strict_types=1);

/**
 * S02-P07 Approval / Parameter / Risk / Support / Notice / Audit 状态机 + fail-closed +
 * 只读投影集成测试（独立 CLI 脚本，无需 PHPUnit，SQLite in-memory）。
 *
 * 覆盖 07 §S02-P07 验证项：
 *   1. ApprovalRequest AR1-AR8 + SoD（审批人 ≠ 申请人）
 *   2. ParameterRelease PR1/PR2/PR5-PR11 + SoD（operator ≠ approver）+ ParameterSnapshot append-only
 *   3. RiskCase 5 态 + SoD（approver ≠ detector）+ appeal + execute fail-closed
 *   4. Ticket TK1-TK8 + appeal guard + TicketMessage/TicketAttachment append-only
 *   5. Notice markRead（unread→read 幂等）+ NotificationDelivery 4 态 + deliver fail-closed
 *   6. AuditEvent append-only + Admin 脱敏查询
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use library\model\approval\ApprovalRequestModel;
use library\model\parameter\ParameterReleaseModel;
use library\model\risk\RiskCaseModel;
use library\model\support\TicketModel;
use library\model\notice\NoticeModel;
use library\model\notice\NotificationDeliveryModel;
use library\service\approval\ApprovalRequestService;
use library\service\parameter\ParameterReleaseService;
use library\service\parameter\ParameterSnapshotService;
use library\service\risk\RiskCaseService;
use library\service\support\TicketService;
use library\service\support\TicketMessageService;
use library\service\support\TicketAttachmentService;
use library\service\notice\NoticeService;
use library\service\notice\NotificationDeliveryService;
use library\service\audit\AuditEventService;
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

// ---- 建表 ----
$mk('approval_requests', function ($t) {
    $t->string('approval_id', 32)->primary();
    $t->string('request_type', 32)->default('');
    $t->string('request_object_type', 32)->default('');
    $t->string('request_object_id', 32)->default('0');
    $t->string('status', 24)->default('draft');
    $t->string('submitted_by', 32)->default('0');
    $t->string('submitter_role', 32)->default('');
    $t->string('assigned_to', 32)->default('0');
    $t->string('decided_by', 32)->default('0');
    $t->integer('decided_at')->default(0);
    $t->string('reason_key', 64)->default('');
    $t->string('changes_requested_reason', 128)->default('');
    $t->string('execution_id', 32)->default('0');
    $t->string('case_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('parameter_releases', function ($t) {
    $t->string('release_id', 32)->primary();
    $t->string('parameter_keys', 255)->default('');
    $t->string('status', 24)->default('draft');
    $t->string('draft_version', 32)->default('');
    $t->string('approved_by', 32)->default('0');
    $t->integer('scheduled_at')->default(0);
    $t->integer('activated_at')->default(0);
    $t->integer('paused_at')->default(0);
    $t->integer('rolled_back_at')->default(0);
    $t->integer('archived_at')->default(0);
    $t->string('monitoring_job_id', 32)->default('0');
    $t->string('snapshot_id', 32)->default('0');
    $t->string('case_id', 32)->default('0');
    $t->string('audit_event_ids', 255)->default('');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('parameter_snapshots', function ($t) {
    $t->string('snapshot_id', 32)->primary();
    $t->string('release_id', 32)->default('0');
    $t->string('parameter_keys', 255)->default('');
    $t->string('parameter_values', 255)->default('');
    $t->string('version', 32)->default('');
    $t->string('created_by', 32)->default('0');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
});

$mk('risk_cases', function ($t) {
    $t->string('case_id', 32)->primary();
    $t->string('user_id', 32)->default('0');
    $t->string('risk_type', 32)->default('');
    $t->string('severity', 16)->default('');
    $t->string('status', 24)->default('open');
    $t->integer('detected_at')->default(0);
    $t->string('detected_by', 32)->default('0');
    $t->string('reviewed_by', 32)->default('0');
    $t->string('disposition', 32)->default('');
    $t->string('disposition_reason_key', 64)->default('');
    $t->string('restrictions', 255)->default('');
    $t->integer('appeal_eligible')->default(0);
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('tickets', function ($t) {
    $t->string('ticket_id', 32)->primary();
    $t->string('user_id', 32)->default('0');
    $t->string('category', 32)->default('');
    $t->string('status', 24)->default('submitted');
    $t->string('assigned_to', 32)->default('0');
    $t->integer('last_activity_at')->default(0);
    $t->string('resolution_type', 32)->default('');
    $t->string('resolution_summary_key', 64)->default('');
    $t->integer('appeal_eligible')->default(0);
    $t->string('ticket_message_ids', 255)->default('');
    $t->string('case_id', 32)->default('0');
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('ticket_messages', function ($t) {
    $t->string('message_id', 32)->primary();
    $t->string('ticket_id', 32)->default('0');
    $t->string('sender_role', 32)->default('');
    $t->string('body_key', 128)->default('');
    $t->string('attachments', 255)->default('');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
});

$mk('ticket_attachments', function ($t) {
    $t->string('attachment_id', 32)->primary();
    $t->string('ticket_id', 32)->default('0');
    $t->string('ticket_message_id', 32)->default('0');
    $t->string('file_type', 32)->default('');
    $t->string('file_url', 255)->default('');
    $t->string('file_hash', 128)->default('');
    $t->string('uploaded_by', 32)->default('0');
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
});

$mk('notices', function ($t) {
    $t->string('notice_id', 32)->primary();
    $t->string('user_id', 32)->default('0');
    $t->string('notice_type', 32)->default('');
    $t->string('title_key', 128)->default('');
    $t->string('body_key', 128)->default('');
    $t->string('priority', 16)->default('INFO');
    $t->string('related_object_type', 32)->default('');
    $t->string('related_object_id', 32)->default('0');
    $t->string('read_state', 16)->default('unread');
    $t->string('content_version', 32)->default('');
    $t->string('locale', 16)->default('');
    $t->integer('expires_at')->default(0);
    $t->integer('object_version')->default(0);
    $t->string('idempotency_key', 64)->nullable();
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('notification_deliveries', function ($t) {
    $t->string('delivery_id', 32)->primary();
    $t->string('notice_id', 32)->default('0');
    $t->string('channel', 16)->default('IN_APP');
    $t->string('delivery_status', 16)->default('pending');
    $t->string('dedupe_key', 64)->default('');
    $t->integer('attempt_count')->default(0);
    $t->integer('last_attempt_at')->default(0);
    $t->integer('next_retry_at')->default(0);
    $t->integer('delivered_at')->default(0);
    $t->string('failure_reason_code', 64)->default('');
    $t->integer('object_version')->default(0);
    $t->string('audit_event_id', 32)->default('0');
    $t->integer('created_time')->default(0);
    $t->integer('updated_time')->default(0);
});

$mk('audit_events', function ($t) {
    $t->string('audit_event_id', 32)->primary();
    $t->string('event_code', 64)->default('');
    $t->string('actor_id', 32)->default('0');
    $t->string('actor_role', 32)->default('');
    $t->string('target_object_type', 64)->default('');
    $t->string('target_object_id', 32)->default('0');
    $t->string('before_snapshot_type', 32)->default('');
    $t->string('before_snapshot_id', 32)->default('0');
    $t->string('after_snapshot_type', 32)->default('');
    $t->string('after_snapshot_id', 32)->default('0');
    $t->string('outcome', 16)->default('SUCCESS');
    $t->string('reason_code', 64)->default('');
    $t->string('request_id', 64)->default('');
    $t->string('approval_id', 32)->default('0');
    $t->string('case_id', 32)->default('0');
    $t->integer('created_time')->default(0);
});

echo "=====================================================\n";
echo "S02-P07 Policy / Governance state machine test\n";
echo "=====================================================\n\n";

$approvalSvc = new ApprovalRequestService();
$paramSvc = new ParameterReleaseService();
$snapshotSvc = new ParameterSnapshotService();
$riskSvc = new RiskCaseService();
$ticketSvc = new TicketService();
$msgSvc = new TicketMessageService();
$attSvc = new TicketAttachmentService();
$noticeSvc = new NoticeService();
$deliverySvc = new NotificationDeliveryService();
$auditSvc = new AuditEventService();

// ======================= 1. ApprovalRequest（AR1-AR8 + SoD） =======================
echo "[1] ApprovalRequest 状态机（AR1-AR8 + SoD）\n";
$approvalSvc->create([
    'approval_id'  => 'AP1',
    'request_type' => 'PARAM_RELEASE',
    'status'       => ApprovalRequestModel::STATUS_DRAFT,
    'submitted_by' => 'U1',
]);
$approvalSvc->submit('AP1', 'U1', 'PARAM_EDITOR');
check((string) $approvalSvc->get('AP1')->status === ApprovalRequestModel::STATUS_PENDING, 'AR1 submit → pending');

$approvalSvc->requestChanges('AP1', 'A1', 'PARAM_APPROVER', 'need fix');
check((string) $approvalSvc->get('AP1')->status === ApprovalRequestModel::STATUS_CHANGES_REQUESTED, 'AR2 requestChanges → changes_requested');
check((string) $approvalSvc->get('AP1')->decided_by === 'A1', 'AR2 decided_by 回写');

$approvalSvc->resubmit('AP1', 'U1', 'PARAM_EDITOR');
check((string) $approvalSvc->get('AP1')->status === ApprovalRequestModel::STATUS_PENDING, 'AR3 resubmit → pending');

$approvalSvc->approve('AP1', 'A1', 'PARAM_APPROVER', 'ok');
check((string) $approvalSvc->get('AP1')->status === ApprovalRequestModel::STATUS_APPROVED, 'AR4 approve → approved');

$approvalSvc->startExecution('AP1', 'SYS', 'SYSTEM', 'EX1');
check((string) $approvalSvc->get('AP1')->status === ApprovalRequestModel::STATUS_EXECUTING, 'AR6 startExecution → executing');

$approvalSvc->completeExecution('AP1', 'SYS', 'SYSTEM');
check((string) $approvalSvc->get('AP1')->status === ApprovalRequestModel::STATUS_EXECUTED, 'AR7 completeExecution → executed');

expectDomainException(function () use ($approvalSvc) {
    $approvalSvc->completeExecution('AP1', 'SYS', 'SYSTEM');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'executed 态再 completeExecution → OBJECT_VERSION_CONFLICT（终态）');

// SoD：审批人 == 申请人 → POLICY_DENIED
$approvalSvc->create([
    'approval_id'  => 'AP2',
    'request_type' => 'PARAM_RELEASE',
    'status'       => ApprovalRequestModel::STATUS_PENDING,
    'submitted_by' => 'U9',
]);
expectDomainException(function () use ($approvalSvc) {
    $approvalSvc->approve('AP2', 'U9', 'PARAM_APPROVER');
}, ErrorDict::POLICY_DENIED, 'approve 审批人=申请人 → POLICY_DENIED（SoD）');
echo "\n";

// ======================= 2. ParameterRelease（PR1/PR2/PR5-PR11 + SoD）+ Snapshot =======================
echo "[2] ParameterRelease 状态机（PR1/PR2/PR5-PR11 + SoD）+ Snapshot append-only\n";
$paramSvc->create([
    'release_id'   => 'R1',
    'status'       => ParameterReleaseModel::STATUS_DRAFT,
]);
$paramSvc->submit('R1', 'E1', 'PARAM_EDITOR');
check((string) $paramSvc->get('R1')->status === ParameterReleaseModel::STATUS_PENDING_APPROVAL, 'PR1 submit → pending_approval');

$paramSvc->approve('R1', 'A1', 'PARAM_APPROVER');
check((string) $paramSvc->get('R1')->status === ParameterReleaseModel::STATUS_APPROVED, 'PR2 approve → approved');
check((string) $paramSvc->get('R1')->approved_by === 'A1', 'PR2 approved_by 回写');

$paramSvc->activateFromApproved('R1', 'O1', 'RELEASE_OPERATOR');
check((string) $paramSvc->get('R1')->status === ParameterReleaseModel::STATUS_ACTIVE, 'PR6 activateFromApproved → active');

$paramSvc->pause('R1', 'O1', 'RELEASE_OPERATOR');
check((string) $paramSvc->get('R1')->status === ParameterReleaseModel::STATUS_PAUSED, 'PR8 pause → paused');

$paramSvc->resume('R1', 'O1', 'RELEASE_OPERATOR');
check((string) $paramSvc->get('R1')->status === ParameterReleaseModel::STATUS_ACTIVE, 'PR9 resume → active');

$paramSvc->rollback('R1', 'O1', 'RELEASE_OPERATOR');
check((string) $paramSvc->get('R1')->status === ParameterReleaseModel::STATUS_ROLLED_BACK, 'PR10 rollback → rolled_back');

$paramSvc->archive('R1', 'O1', 'RELEASE_OPERATOR');
check((string) $paramSvc->get('R1')->status === ParameterReleaseModel::STATUS_ARCHIVED, 'PR11 archive → archived');

// SoD：operator == approver → POLICY_DENIED
$paramSvc->create([
    'release_id' => 'R2',
    'status'     => ParameterReleaseModel::STATUS_APPROVED,
    'approved_by' => 'A9',
]);
expectDomainException(function () use ($paramSvc) {
    $paramSvc->activateFromApproved('R2', 'A9', 'RELEASE_OPERATOR');
}, ErrorDict::POLICY_DENIED, 'activate operator=approver → POLICY_DENIED（SoD）');

// PR7: scheduled → active
$paramSvc->create([
    'release_id'  => 'R3',
    'status'      => ParameterReleaseModel::STATUS_SCHEDULED,
    'approved_by' => 'A1',
]);
$paramSvc->activateFromScheduled('R3', 'O1', 'RELEASE_OPERATOR');
check((string) $paramSvc->get('R3')->status === ParameterReleaseModel::STATUS_ACTIVE, 'PR7 activateFromScheduled → active');

// Snapshot append-only 只读投影
$snapshotSvc->create([
    'snapshot_id'      => 'S1',
    'release_id'       => 'R1',
    'parameter_keys'   => '["k1"]',
    'parameter_values' => '{"k1":"v1"}',
    'version'          => '1',
    'created_by'       => 'O1',
    'created_time'     => time(),
]);
$sdetail = $snapshotSvc->detail('S1');
check($sdetail['snapshot_id'] === 'S1', 'snapshot detail.snapshot_id=S1');
check($sdetail['version'] === '1', 'snapshot detail.version=1');
$slist = $snapshotSvc->listByRelease('R1');
check(count($slist['snapshots']) === 1, 'listByRelease(R1) 数量=1');
echo "\n";

// ======================= 3. RiskCase（5 态 + SoD + appeal + execute fail-closed） =======================
echo "[3] RiskCase 状态机（5 态 + SoD + appeal + execute fail-closed）\n";
$riskSvc->create([
    'case_id'     => 'C1',
    'user_id'     => 'U1',
    'status'      => RiskCaseModel::STATUS_OPEN,
    'detected_by' => 'SYS',
]);
$riskSvc->startInvestigate('C1', 'RA1', 'RISK_ANALYST');
check((string) $riskSvc->get('C1')->status === RiskCaseModel::STATUS_INVESTIGATING, 'open → investigating');

$riskSvc->submitDecision('C1', 'RA1', 'RISK_ANALYST');
check((string) $riskSvc->get('C1')->status === RiskCaseModel::STATUS_UNDER_REVIEW, 'investigating → under_review');

$riskSvc->resolve('C1', 'RA2', 'RISK_APPROVER', 'FREEZE', 'reason');
check((string) $riskSvc->get('C1')->status === RiskCaseModel::STATUS_RESOLVED, 'under_review → resolved');
check((string) $riskSvc->get('C1')->reviewed_by === 'RA2', 'resolve reviewed_by 回写');

$riskSvc->closeResolved('C1', 'RA2', 'RISK_APPROVER');
check((string) $riskSvc->get('C1')->status === RiskCaseModel::STATUS_CLOSED, 'resolved → closed');

// 误报关闭：open → closed
$riskSvc->create([
    'case_id'     => 'C2',
    'user_id'     => 'U2',
    'status'      => RiskCaseModel::STATUS_OPEN,
    'detected_by' => 'SYS',
]);
$riskSvc->closeFalsePositive('C2', 'RA2', 'RISK_APPROVER');
check((string) $riskSvc->get('C2')->status === RiskCaseModel::STATUS_CLOSED, 'open → closed（误报）');

// SoD：approver == detector → POLICY_DENIED
$riskSvc->create([
    'case_id'     => 'C3',
    'user_id'     => 'U3',
    'status'      => RiskCaseModel::STATUS_UNDER_REVIEW,
    'detected_by' => 'RA9',
]);
expectDomainException(function () use ($riskSvc) {
    $riskSvc->resolve('C3', 'RA9', 'RISK_APPROVER');
}, ErrorDict::POLICY_DENIED, 'resolve approver=detector → POLICY_DENIED（SoD）');

// 申诉重开：resolved + appeal_eligible=1 → investigating
$riskSvc->create([
    'case_id'        => 'C4',
    'user_id'        => 'U4',
    'status'         => RiskCaseModel::STATUS_RESOLVED,
    'detected_by'    => 'SYS',
    'appeal_eligible' => 1,
]);
$riskSvc->reopenAppeal('C4', 'RA1', 'RISK_ANALYST');
check((string) $riskSvc->get('C4')->status === RiskCaseModel::STATUS_INVESTIGATING, 'resolved → investigating（申诉重开）');

// appeal_eligible=0 → reopen 拒绝
$riskSvc->create([
    'case_id'        => 'C5',
    'user_id'        => 'U5',
    'status'         => RiskCaseModel::STATUS_RESOLVED,
    'detected_by'    => 'SYS',
    'appeal_eligible' => 0,
]);
expectDomainException(function () use ($riskSvc) {
    $riskSvc->reopenAppeal('C5', 'RA1', 'RISK_ANALYST');
}, ErrorDict::POLICY_DENIED, 'reopen appeal_eligible=0 → POLICY_DENIED');

expectDomainException(function () use ($riskSvc) {
    $riskSvc->execute('C1', 'RA2', 'RISK_APPROVER');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'RiskCase execute → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 4. Ticket（TK1-TK8 + appeal）+ Message/Attachment =======================
echo "[4] Ticket 状态机（TK1-TK8 + appeal）+ Message/Attachment append-only\n";
$ticketSvc->create([
    'ticket_id' => 'TK1',
    'user_id'   => 'U1',
    'status'    => TicketModel::STATUS_SUBMITTED,
]);
$ticketSvc->accept('TK1', 'SA1', 'SUPPORT_AGENT', 'SA1');
check((string) $ticketSvc->get('TK1')->status === TicketModel::STATUS_IN_PROGRESS, 'TK1 accept → in_progress');
check((string) $ticketSvc->get('TK1')->assigned_to === 'SA1', 'TK1 assigned_to 回写');

$ticketSvc->waitUser('TK1', 'SA1', 'SUPPORT_AGENT');
check((string) $ticketSvc->get('TK1')->status === TicketModel::STATUS_WAITING_USER, 'TK2 waitUser → waiting_user');

$ticketSvc->userReplied('TK1', 'U1', 'END_USER');
check((string) $ticketSvc->get('TK1')->status === TicketModel::STATUS_IN_PROGRESS, 'TK3 userReplied → in_progress');

$ticketSvc->escalate('TK1', 'SA1', 'SUPPORT_AGENT');
check((string) $ticketSvc->get('TK1')->status === TicketModel::STATUS_UNDER_REVIEW, 'TK4 escalate → under_review');

$ticketSvc->resolve('TK1', 'SA1', 'SUPPORT_AGENT', 'FIXED');
check((string) $ticketSvc->get('TK1')->status === TicketModel::STATUS_RESOLVED, 'TK5 resolve → resolved');

// TK8: resolved + appeal_eligible=1 → in_progress（重开）
$ticketSvc->create([
    'ticket_id'      => 'TK8',
    'user_id'        => 'U2',
    'status'         => TicketModel::STATUS_RESOLVED,
    'appeal_eligible' => 1,
]);
$ticketSvc->reopen('TK8', 'U2', 'END_USER');
check((string) $ticketSvc->get('TK8')->status === TicketModel::STATUS_IN_PROGRESS, 'TK8 reopen → in_progress');

// appeal_eligible=0 → reopen 拒绝
$ticketSvc->create([
    'ticket_id'      => 'TK9',
    'user_id'        => 'U3',
    'status'         => TicketModel::STATUS_RESOLVED,
    'appeal_eligible' => 0,
]);
expectDomainException(function () use ($ticketSvc) {
    $ticketSvc->reopen('TK9', 'U3', 'END_USER');
}, ErrorDict::POLICY_DENIED, 'reopen appeal_eligible=0 → POLICY_DENIED');

// TK7: resolved → closed
$ticketSvc->close('TK1', 'SA1', 'SUPPORT_AGENT');
check((string) $ticketSvc->get('TK1')->status === TicketModel::STATUS_CLOSED, 'TK7 close → closed');
expectDomainException(function () use ($ticketSvc) {
    $ticketSvc->accept('TK1', 'SA1', 'SUPPORT_AGENT');
}, ErrorDict::OBJECT_VERSION_CONFLICT, 'closed 态 accept → OBJECT_VERSION_CONFLICT（终态）');

// Message append-only + 只读
$msgSvc->create([
    'message_id'  => 'M1',
    'ticket_id'   => 'TK1',
    'sender_role' => 'END_USER',
    'body_key'    => 'body.1',
    'created_time' => time(),
]);
$mdetail = $msgSvc->detail('M1');
check($mdetail['message_id'] === 'M1', 'message detail.message_id=M1');
check($mdetail['sender_role'] === 'END_USER', 'message detail.sender_role=END_USER');
$mlist = $msgSvc->listByTicket('TK1');
check(count($mlist['messages']) === 1, 'listByTicket(TK1) 数量=1');

// Attachment create → fail-closed
expectDomainException(function () use ($attSvc) {
    $attSvc->create([]);
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'TicketAttachment create → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 5. Notice markRead + NotificationDelivery =======================
echo "[5] Notice markRead（幂等）+ NotificationDelivery 4 态 + deliver fail-closed\n";
$noticeSvc->create([
    'notice_id' => 'N1',
    'user_id'   => 'U1',
    'read_state' => NoticeModel::READ_UNREAD,
]);
$noticeSvc->markRead('N1', 'U1', 'END_USER');
check((string) $noticeSvc->get('N1')->read_state === NoticeModel::READ_READ, 'markRead unread → read');
$noticeSvc->markRead('N1', 'U1', 'END_USER');
check((string) $noticeSvc->get('N1')->read_state === NoticeModel::READ_READ, 'markRead 再读 → 幂等保持 read');

$deliverySvc->create([
    'delivery_id' => 'D1',
    'notice_id'   => 'N1',
    'channel'     => NotificationDeliveryModel::CHANNEL_IN_APP,
    'delivery_status' => NotificationDeliveryModel::STATUS_PENDING,
]);
$deliverySvc->markDelivered('D1', 'SYS', 'SYSTEM');
check((string) $deliverySvc->get('D1')->delivery_status === NotificationDeliveryModel::STATUS_DELIVERED, 'pending → delivered');

// failed → retry → delivered
$deliverySvc->create([
    'delivery_id' => 'D2',
    'notice_id'   => 'N1',
    'channel'     => NotificationDeliveryModel::CHANNEL_PUSH,
    'delivery_status' => NotificationDeliveryModel::STATUS_PENDING,
]);
$deliverySvc->markFailed('D2', 'SYS', 'SYSTEM', 'CHANNEL_DOWN', time() + 60);
check((string) $deliverySvc->get('D2')->delivery_status === NotificationDeliveryModel::STATUS_FAILED, 'pending → failed');
check((int) $deliverySvc->get('D2')->attempt_count === 1, 'failed attempt_count=1');
$deliverySvc->retry('D2', 'SYS', 'SYSTEM');
check((string) $deliverySvc->get('D2')->delivery_status === NotificationDeliveryModel::STATUS_PENDING, 'failed → pending（重试）');

// cancel
$deliverySvc->create([
    'delivery_id' => 'D3',
    'notice_id'   => 'N1',
    'channel'     => NotificationDeliveryModel::CHANNEL_EMAIL,
    'delivery_status' => NotificationDeliveryModel::STATUS_PENDING,
]);
$deliverySvc->cancel('D3', 'SYS', 'SYSTEM');
check((string) $deliverySvc->get('D3')->delivery_status === NotificationDeliveryModel::STATUS_CANCELLED, 'pending → cancelled');

expectDomainException(function () use ($deliverySvc) {
    $deliverySvc->deliver('D1', 'SYS', 'SYSTEM');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'NotificationDelivery deliver → DEPENDENCY_UNAVAILABLE');
echo "\n";

// ======================= 6. AuditEvent append-only + 脱敏查询 =======================
echo "[6] AuditEvent append-only + Admin 脱敏查询\n";
$auditSvc->create([
    'audit_event_id'   => 'AE1',
    'event_code'       => ApprovalRequestService::EVENT_APPROVED,
    'actor_id'         => 'A1',
    'actor_role'       => 'PARAM_APPROVER',
    'target_object_type' => 'approval_requests',
    'target_object_id' => 'AP1',
    'outcome'          => 'SUCCESS',
    'created_time'     => time(),
]);
$adetail = $auditSvc->detail('AE1');
check($adetail['audit_event_id'] === 'AE1', 'audit detail.audit_event_id=AE1');
check($adetail['event_code'] === 'APPROVAL_APPROVED', 'audit detail.event_code=APPROVAL_APPROVED');

$alist = $auditSvc->listAdmin(['target_object_type' => 'approval_requests', 'target_object_id' => 'AP1']);
check(count($alist['audit_events']) >= 1, 'listAdmin(approval_requests/AP1) 有审计事件');
check(!array_key_exists('before_snapshot_type', $alist['audit_events'][0]), 'listAdmin 脱敏：不暴露 before_snapshot_type');
echo "\n";

// ======================= 7. 只读投影（detail 不存在 → VALIDATION_ERROR） =======================
echo "[7] 只读投影（detail 不存在 → VALIDATION_ERROR）\n";
expectDomainException(function () use ($approvalSvc) {
    $approvalSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'approval detail(不存在) → VALIDATION_ERROR');
expectDomainException(function () use ($paramSvc) {
    $paramSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'param release detail(不存在) → VALIDATION_ERROR');
expectDomainException(function () use ($ticketSvc) {
    $ticketSvc->detail('NOPE');
}, ErrorDict::VALIDATION_ERROR, 'ticket detail(不存在) → VALIDATION_ERROR');
echo "\n";

summary();
