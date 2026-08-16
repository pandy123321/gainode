<?php

declare(strict_types=1);

/**
 * S02-P02 状态机 + 资格聚合集成测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * SQLite in-memory（命名 'mysql'），表结构对齐 2B-2 冻结 DDL（auth_sessions /
 * mfa_enrollments / kyc_cases）。覆盖六条子流程的领域层核心：
 *   1. AuthSession：issue → findByToken → rotateAccessToken → revoke → revokeAll → isExpired；
 *   2. MfaEnrollment：setup → confirm/challenge fail-closed → disable；
 *   3. KycCase：submit → startReview → requestInfo → resubmit → approve/reject + 自审守卫；
 *   4. FeatureEntitlement / Eligibility：三分支独立、默认 deny、allowed_actions 空数组。
 */

require __DIR__ . '/_bootstrap.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use library\dict\ErrorDict;
use library\model\auth\AuthSessionModel;
use library\model\auth\MfaEnrollmentModel;
use library\model\kyc\KycCaseModel;
use library\service\auth\AuthSessionService;
use library\service\auth\MfaEnrollmentService;
use library\service\entitlement\EligibilityApplicationService;
use library\service\entitlement\FeatureEntitlementProjectionService;
use library\service\kyc\KycCaseService;
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

if (!$schema->hasTable('auth_sessions')) {
    $schema->create('auth_sessions', function ($table) {
        $table->string('session_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('token_hash', 128);
        $table->string('status', 16);
        $table->text('device_info')->nullable();
        $table->string('ip_address', 64);
        $table->integer('mfa_verified');
        $table->integer('expires_at');
        $table->integer('object_version');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32);
        $table->integer('created_time');
        $table->integer('updated_time');
    });
}

if (!$schema->hasTable('mfa_enrollments')) {
    $schema->create('mfa_enrollments', function ($table) {
        $table->string('enrollment_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('method_type', 32);
        $table->string('status', 16);
        $table->integer('enrolled_at');
        $table->integer('last_verified_at');
        $table->integer('backup_codes_active');
        $table->text('device_info')->nullable();
        $table->integer('object_version');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32);
        $table->integer('created_time');
        $table->integer('updated_time');
    });
}

if (!$schema->hasTable('kyc_cases')) {
    $schema->create('kyc_cases', function ($table) {
        $table->string('case_id', 32)->primary();
        $table->string('user_id', 32);
        $table->string('kyc_level', 16);
        $table->string('status', 16);
        $table->integer('submitted_at');
        $table->integer('reviewed_at');
        $table->string('reviewed_by', 32);
        $table->string('reason_code', 64);
        $table->string('reason_text_key', 64);
        $table->string('next_action', 64);
        $table->string('policy_version', 16);
        $table->string('rule_version', 16);
        $table->integer('object_version');
        $table->string('idempotency_key', 64)->nullable();
        $table->string('audit_event_id', 32);
        $table->integer('created_time');
        $table->integer('updated_time');
    });
}

/**
 * 断言闭包抛出 DomainException 且 resultCode 命中。
 */
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
echo "S02-P02 state machine + eligibility test\n";
echo "=====================================================\n\n";

// ======================= 1. AuthSession =======================
echo "[1] AuthSession 状态机\n";
$sessionSvc = new AuthSessionService();

$s = $sessionSvc->issue('90001', 'raw_access_token_1', '{"os":"Windows","browser":"Chrome"}', '1.2.3.4', false, time() + 3600, 'IK-S1', 'AE-S1');
check($s instanceof AuthSessionModel, 'issue 返回 AuthSessionModel');
check((string) $s->status === AuthSessionModel::STATUS_MFA_REQUIRED, '未验 MFA → mfa_required');
check((string) $s->token_hash === hash('sha256', 'raw_access_token_1'), 'token_hash 仅存 sha256（不存明文）');
check((string) $s->user_id === '90001', 'issue user_id 正确');

$found = $sessionSvc->findByToken('raw_access_token_1');
check($found !== null && (string) $found->session_id === (string) $s->session_id, 'findByToken 命中');

$sessionSvc->rotateAccessToken($s, 'new_access_token_2');
check($sessionSvc->findByToken('new_access_token_2') !== null, 'rotateAccessToken 后新 token 可查');
check($sessionSvc->findByToken('raw_access_token_1') === null, 'rotateAccessToken 后旧 token 不可查');

check($sessionSvc->isExpired($s, time() + 7200) === true, 'isExpired：now > expires_at → true');
check($sessionSvc->isExpired($s, time() - 100) === false, 'isExpired：now < expires_at → false');

// 本人撤销
check($sessionSvc->revoke((string) $s->session_id, '90001') === true, 'revoke 本人成功');
check((string) $sessionSvc->get((string) $s->session_id)->status === AuthSessionModel::STATUS_REVOKED, 'revoke → revoked');

// 越权撤销（false，不泄露存在性）
$s2 = $sessionSvc->issue('90002', 'tok_b', '{}', '5.6.7.8', true, time() + 3600, 'IK-S2', 'AE-S2');
check($sessionSvc->revoke((string) $s2->session_id, '90001') === false, 'revoke 越权 → false');

// revokeAll：新增活跃会话后全撤
$sessionSvc->issue('90001', 'tok_c', '{}', '1.1.1.1', true, time() + 3600, 'IK-S3', 'AE-S3');
$count = $sessionSvc->revokeAll('90001');
check($count >= 1, "revokeAll 撤销活跃会话（count={$count}）");
echo "\n";

// ======================= 2. MfaEnrollment =======================
echo "[2] MfaEnrollment 状态机（fail-closed）\n";
$mfaSvc = new MfaEnrollmentService();

$enr = $mfaSvc->setup('90001', 'totp', '{}', 'IK-M1', 'AE-M1');
check((string) $enr->status === MfaEnrollmentModel::STATUS_PENDING, 'setup → pending');
check((string) $enr->method_type === 'totp', 'setup method_type=totp');

expectDomainException(function () use ($mfaSvc) {
    $mfaSvc->setup('90001', 'sms', '{}', 'IK-M2', 'AE-M2');
}, ErrorDict::VALIDATION_ERROR, '非 totp 方法 → VALIDATION_ERROR');

expectDomainException(function () use ($mfaSvc, $enr) {
    $mfaSvc->confirm((string) $enr->enrollment_id, '90001', '123456');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'confirm（secret 未冻结）→ DEPENDENCY_UNAVAILABLE');

expectDomainException(function () use ($mfaSvc) {
    $mfaSvc->challenge('90001', '123456');
}, ErrorDict::DEPENDENCY_UNAVAILABLE, 'challenge（secret 未冻结）→ DEPENDENCY_UNAVAILABLE');

check($mfaSvc->hasActive('90001') === false, 'hasActive=false（仅 pending，未 confirm）');

check($mfaSvc->disable((string) $enr->enrollment_id, '90001') === true, 'disable 成功');
check((string) $mfaSvc->get((string) $enr->enrollment_id)->status === MfaEnrollmentModel::STATUS_REVOKED, 'disable → revoked');

$enr2 = $mfaSvc->setup('90002', 'totp', '{}', 'IK-M3', 'AE-M3');
expectDomainException(function () use ($mfaSvc, $enr2) {
    $mfaSvc->disable((string) $enr2->enrollment_id, '90001');
}, ErrorDict::AUTH_FORBIDDEN, '越权 disable → AUTH_FORBIDDEN');
echo "\n";

// ======================= 3. KycCase =======================
echo "[3] KycCase 状态机\n";
$kycSvc = new KycCaseService();

expectDomainException(function () use ($kycSvc) {
    $kycSvc->submit('90001', 'L1', [], 'PV1', 'RV1', 'IK-K1', 'AE-K1');
}, ErrorDict::VALIDATION_ERROR, '空附件 → VALIDATION_ERROR');

$case = $kycSvc->submit('90001', 'L1', ['att1'], 'PV1', 'RV1', 'IK-K2', 'AE-K2');
check((string) $case->status === KycCaseModel::STATUS_PENDING, 'submit → pending');
check((string) $case->user_id === '90001', 'submit user_id 正确');

expectDomainException(function () use ($kycSvc) {
    $kycSvc->submit('90001', 'L1', ['att1'], 'PV1', 'RV1', 'IK-K3', 'AE-K3');
}, ErrorDict::VALIDATION_ERROR, 'pending 重复提交 → VALIDATION_ERROR');

$kycSvc->startReview((string) $case->case_id, 'reviewer1');
check((string) $kycSvc->get((string) $case->case_id)->status === KycCaseModel::STATUS_REVIEW, 'startReview → review');

$kycSvc->requestInfo((string) $case->case_id, 'reviewer1', 'DOC_MISSING', 'kyc.doc_missing');
$reviewed = $kycSvc->get((string) $case->case_id);
check((string) $reviewed->status === KycCaseModel::STATUS_NEEDS_INFO, 'requestInfo → needs_info');
check((string) $reviewed->reviewed_by === 'reviewer1', 'reviewed_by 记录 reviewer1');

// needs_info → 重新提交 → pending
$resubmitted = $kycSvc->submit('90001', 'L1', ['att2'], 'PV2', 'RV2', 'IK-K4', 'AE-K4');
check((string) $resubmitted->status === KycCaseModel::STATUS_PENDING, 'needs_info 重新提交 → pending');

$kycSvc->startReview((string) $resubmitted->case_id, 'reviewer2');
$kycSvc->approve((string) $resubmitted->case_id, 'reviewer2');
check((string) $kycSvc->get((string) $resubmitted->case_id)->status === KycCaseModel::STATUS_APPROVED, 'approve → approved');

// 自审守卫
$case3 = $kycSvc->submit('90003', 'L1', ['att3'], 'PV3', 'RV3', 'IK-K5', 'AE-K5');
expectDomainException(function () use ($kycSvc, $case3) {
    $kycSvc->startReview((string) $case3->case_id, '90003');
}, ErrorDict::AUTH_FORBIDDEN, '申请人不得审批本人 → AUTH_FORBIDDEN');
echo "\n";

// ======================= 4. FeatureEntitlement / Eligibility =======================
echo "[4] FeatureEntitlement / Eligibility 三分支聚合\n";
$feSvc = new FeatureEntitlementProjectionService();
$bundle = $feSvc->getEligibilityBundle('90001', '90001');
check(isset($bundle['global_p'], $bundle['ai'], $bundle['prediction']), 'getEligibilityBundle 返回三分支');
check((string) $bundle['global_p']->feature_key === 'global_p', 'global_p feature_key');
check((string) $bundle['ai']->feature_key === 'ai_reward_eligibility', 'ai feature_key');
check((string) $bundle['prediction']->feature_key === 'prediction_eligibility', 'prediction feature_key');
check($bundle['global_p']->allowed === false, 'global_p 默认 deny');
check($bundle['ai']->allowed === false, 'ai 默认 deny');
check($bundle['prediction']->allowed === false, 'prediction 默认 deny');
check($bundle['global_p']->reason_code === FeatureEntitlementProjectionService::REASON_FEATURE_RULE_UNAVAILABLE, 'global_p reason=FEATURE_RULE_UNAVAILABLE');
check($bundle['global_p']->allowed_actions === [], 'allowed_actions=[]（Contract Gap G2，不推断）');

$eligSvc = new EligibilityApplicationService();
$res = $eligSvc->getBundle('90001', '90001');
check($res['user_id'] === '90001', 'Eligibility user_id');
check($res['global_p']['allowed'] === false, 'Eligibility global_p allowed=false');
check($res['ai']['allowed'] === false, 'Eligibility ai allowed=false');
check($res['prediction']['allowed'] === false, 'Eligibility prediction allowed=false');
check($res['global_p']['allowed_actions'] === [], 'Eligibility global_p allowed_actions=[]');
echo "\n";

summary();
