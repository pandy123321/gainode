<?php

declare(strict_types=1);

namespace library\dict;

/**
 * 安全 reason mapping（05 §4「安全 reason mapping」）。
 *
 * 目的：KYC / Auth / 风控 / 通知等对外可见文案，不直接暴露内部 reason_code，
 * 而是映射到安全 I18N key。越权、账号不存在、密码错误统一返回同一安全文案，
 * 防止账号枚举（05 §11.1 对象存在性不泄露）。
 *
 * 未知 code 一律回落 DEFAULT，绝不透传 raw reason_code。
 */
final class SecurityReasonMap
{
    /** @var string 未知/兜底安全文案 */
    public const DEFAULT_KEY = 'auth.generic_error';

    /** @var string 统一「账号或密码错误」安全文案（防枚举） */
    public const ACCOUNT_OR_PASSWORD_INCORRECT = 'auth.account_or_password_incorrect';

    /** @var array<string,string> 内部 reason_code → 安全 I18N key */
    private const MAP = [
        'USER_NOT_FOUND'           => self::ACCOUNT_OR_PASSWORD_INCORRECT,
        'PASSWORD_INCORRECT'       => self::ACCOUNT_OR_PASSWORD_INCORRECT,
        'ACCOUNT_LOCKED'           => 'auth.account_locked',
        'ACCOUNT_DELETED'          => 'auth.account_unavailable',
        'ACCOUNT_SUSPENDED'        => 'auth.account_unavailable',
        'OTP_INVALID'              => 'auth.otp_invalid',
        'OTP_EXPIRED'              => 'auth.otp_expired',
        'OTP_RATE_LIMITED'         => 'auth.otp_rate_limited',
        'MFA_REQUIRED'             => 'auth.mfa_required',
        'MFA_INVALID'              => 'auth.mfa_invalid',
        'MFA_ALREADY_ACTIVE'       => 'auth.mfa_already_active',
        'SESSION_REVOKED'          => 'auth.session_revoked',
        'SESSION_EXPIRED'          => 'auth.session_expired',
        'REFRESH_REPLAYED'         => 'auth.session_expired',
        'KYC_REJECTED'             => 'kyc.rejected',
        'KYC_NEEDS_INFO'           => 'kyc.needs_info',
        'KYC_PENDING'              => 'kyc.pending',
        'FEATURE_RULE_UNAVAILABLE' => 'entitlement.feature_rule_unavailable',
        'FEATURE_CLOSED'           => 'entitlement.feature_closed',
        'DEPENDENCY_UNAVAILABLE'   => 'common.dependency_unavailable',
    ];

    /**
     * 解析内部 reason_code → 安全 I18N key。
     * 未知 code 回落 DEFAULT_KEY，绝不透传 raw code。
     */
    public static function resolve(string $internalCode): string
    {
        return self::MAP[$internalCode] ?? self::DEFAULT_KEY;
    }

    /**
     * 判断解析结果是否命中「账号或密码错误」统一文案（用于账户枚举防护断言）。
     */
    public static function isEnumerationSafe(string $internalCode): bool
    {
        return self::resolve($internalCode) === self::ACCOUNT_OR_PASSWORD_INCORRECT;
    }
}
