<?php

declare(strict_types=1);

/**
 * SecurityReasonMap + DomainException 契约测试（S02-P02）。
 *
 * 覆盖：
 *   1. 内部 reason_code → 安全 I18N key 映射（防枚举，05 §4）；
 *   2. 未知 code 回落 auth.generic_error（绝不透传 raw code）；
 *   3. isEnumerationSafe 判定（账号枚举防护断言）；
 *   4. DomainException 携带 05 §7 字符串码 + HTTP 状态映射（区别于 V1.x VerifyException）。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\SecurityReasonMap;
use support\exception\DomainException;

echo "=====================================================\n";
echo "SecurityReasonMap + DomainException contract test\n";
echo "=====================================================\n\n";

// ---- 安全 reason mapping ----
echo "[1] 内部 reason_code → 安全 I18N key\n";
check(SecurityReasonMap::resolve('USER_NOT_FOUND') === SecurityReasonMap::ACCOUNT_OR_PASSWORD_INCORRECT, 'USER_NOT_FOUND → 统一「账号或密码错误」');
check(SecurityReasonMap::resolve('PASSWORD_INCORRECT') === SecurityReasonMap::ACCOUNT_OR_PASSWORD_INCORRECT, 'PASSWORD_INCORRECT → 统一「账号或密码错误」');
check(SecurityReasonMap::resolve('ACCOUNT_LOCKED') === 'auth.account_locked', 'ACCOUNT_LOCKED → auth.account_locked');
check(SecurityReasonMap::resolve('ACCOUNT_DELETED') === 'auth.account_unavailable', 'ACCOUNT_DELETED → auth.account_unavailable');
check(SecurityReasonMap::resolve('OTP_INVALID') === 'auth.otp_invalid', 'OTP_INVALID → auth.otp_invalid');
check(SecurityReasonMap::resolve('OTP_EXPIRED') === 'auth.otp_expired', 'OTP_EXPIRED → auth.otp_expired');
check(SecurityReasonMap::resolve('MFA_INVALID') === 'auth.mfa_invalid', 'MFA_INVALID → auth.mfa_invalid');
check(SecurityReasonMap::resolve('REFRESH_REPLAYED') === 'auth.session_expired', 'REFRESH_REPLAYED → auth.session_expired');
check(SecurityReasonMap::resolve('KYC_REJECTED') === 'kyc.rejected', 'KYC_REJECTED → kyc.rejected');
check(SecurityReasonMap::resolve('FEATURE_RULE_UNAVAILABLE') === 'entitlement.feature_rule_unavailable', 'FEATURE_RULE_UNAVAILABLE → entitlement.feature_rule_unavailable');
echo "\n";

echo "[2] 未知 code 回落 auth.generic_error（不泄露 raw code）\n";
check(SecurityReasonMap::resolve('NOT_A_REAL_CODE') === SecurityReasonMap::DEFAULT_KEY, '未知 code → auth.generic_error');
check(SecurityReasonMap::resolve('') === SecurityReasonMap::DEFAULT_KEY, '空 code → auth.generic_error');
echo "\n";

echo "[3] isEnumerationSafe 判定\n";
check(SecurityReasonMap::isEnumerationSafe('USER_NOT_FOUND') === true, 'USER_NOT_FOUND → 枚举安全（统一文案）');
check(SecurityReasonMap::isEnumerationSafe('PASSWORD_INCORRECT') === true, 'PASSWORD_INCORRECT → 枚举安全');
check(SecurityReasonMap::isEnumerationSafe('OTP_INVALID') === false, 'OTP_INVALID → 非枚举安全（独立文案）');
check(SecurityReasonMap::isEnumerationSafe('NOT_A_REAL_CODE') === false, '未知 code → 非枚举安全');
echo "\n";

// ---- DomainException ----
echo "[4] DomainException 携带 05 §7 字符串码 + HTTP 映射\n";
$e1 = new DomainException('VALIDATION_ERROR');
check($e1->resultCode() === 'VALIDATION_ERROR', 'resultCode=VALIDATION_ERROR');
check($e1->httpStatus() === 400, 'VALIDATION_ERROR → 400');

$e2 = new DomainException('AUTH_UNAUTHENTICATED');
check($e2->httpStatus() === 401, 'AUTH_UNAUTHENTICATED → 401');

$e3 = new DomainException('AUTH_FORBIDDEN');
check($e3->httpStatus() === 403, 'AUTH_FORBIDDEN → 403');

$e4 = new DomainException('DEPENDENCY_UNAVAILABLE');
check($e4->httpStatus() === 503, 'DEPENDENCY_UNAVAILABLE → 503');

$e5 = new DomainException('INTERNAL_ERROR');
check($e5->httpStatus() === 500, 'INTERNAL_ERROR → 500');

$e6 = new DomainException('AUTH_UNAUTHENTICATED', '自定义安全文案');
check($e6->getMessage() === '自定义安全文案', '自定义 message 透传');
echo "\n";

summary();
