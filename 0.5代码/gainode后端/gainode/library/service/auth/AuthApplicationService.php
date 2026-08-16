<?php

declare(strict_types=1);

namespace library\service\auth;

use library\dict\ErrorDict;
use library\dict\SecurityReasonMap;
use library\model\member\UserAuthModel;
use library\service\member\UserAuthService;
use library\service\member\UserService;
use support\exception\DomainException;
use support\utils\Http;
use support\utils\JwtToken;

/**
 * Auth 应用服务（S02-P02 子流程 1：注册/登录/OTP/找回/重置）。
 *
 * 复用 V1.x MemberAuth / UserService / UserAuthService / JwtToken 的 hash/OTP/JWT 入口（KEEP），
 * 桥接 V2 AuthSession 会话签发与安全 reason mapping（防账户枚举）。
 *
 * 关键约束：MemberAuth::login()/codeLogin() 返回 UserAuthModel::toM()
 * （仅 user_id/token/terminal），refresh_token/expired_time 需经 UserAuthService
 * 反查完整 UserAuthModel 取得，不得直接读不存在的键。
 */
class AuthApplicationService
{
    /**
     * 注册（复用 V1.x MemberAuth::register）。
     *
     * @return array{user_id:string, account:string}
     */
    public function register(array $data): array
    {
        $memberAuth = new MemberAuth(\request());
        try {
            $user = $memberAuth->register($data);
        } catch (\Throwable $e) {
            throw $this->mapAuthException($e);
        }
        return [
            'user_id' => (string) $user->id,
            'account' => (string) $user->account,
        ];
    }

    /**
     * 登录：复用 V1.x verifyLogin + createLoginAuth，桥接 V2 AuthSession + MFA 门禁。
     *
     * @return array AuthTokenResponse（含 session_id / mfa_required / refresh_token）
     */
    public function login(array $data): array
    {
        $memberAuth = new MemberAuth(\request());
        try {
            $authRecord = $memberAuth->login($data);
        } catch (\Throwable $e) {
            throw $this->mapAuthException($e);
        }

        $accessToken = (string) ($authRecord['token'] ?? '');
        $auth = $this->fetchLoginAuth((string) ($authRecord['user_id'] ?? ''), $accessToken);

        return $this->issueSession(
            (string) ($authRecord['user_id'] ?? ''),
            $accessToken,
            $auth !== null ? (string) $auth->refresh_token : '',
            $auth !== null ? (int) $auth->expired_time : (time() + 3600)
        );
    }

    /**
     * 验证码登录（复用 V1.x MemberAuth::codeLogin）。
     */
    public function codeLogin(array $data): array
    {
        $memberAuth = new MemberAuth(\request());
        try {
            $authRecord = $memberAuth->codeLogin(
                $data['account'],
                $data['vcode'],
                $data['type'] ?? 'email',
                $data['invite_code'] ?? null,
                $data['source'] ?? 'login'
            );
        } catch (\Throwable $e) {
            throw $this->mapAuthException($e);
        }

        $accessToken = (string) ($authRecord['token'] ?? '');
        $auth = $this->fetchLoginAuth((string) ($authRecord['user_id'] ?? ''), $accessToken);

        return $this->issueSession(
            (string) ($authRecord['user_id'] ?? ''),
            $accessToken,
            $auth !== null ? (string) $auth->refresh_token : '',
            $auth !== null ? (int) $auth->expired_time : (time() + 3600)
        );
    }

    /**
     * OTP 验证（复用 V1.x verifyCodeMsg）。
     */
    public function otpVerify(array $data): array
    {
        $account = $data['account'];
        $vcode = $data['vcode'];
        $type = $data['type'] ?? 'email';
        if (!verifyCodeMsg($account, $vcode, $type)) {
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, trans(SecurityReasonMap::resolve('OTP_INVALID')));
        }
        return ['account' => $account, 'verified' => true];
    }

    /**
     * OTP 重发（复用 V1.x sendCodeMsg，含频控与枚举防护）。
     */
    public function otpResend(array $data): array
    {
        $account = $data['account'];
        $type = $data['type'] ?? 'email';
        $source = $data['source'] ?? 'login';
        $userService = new UserService();
        $user = $userService->getUserByAccount($account);

        if ($source === 'login' && empty($user)) {
            // 枚举防护：统一文案
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, trans(SecurityReasonMap::ACCOUNT_OR_PASSWORD_INCORRECT));
        }
        if ($source === 'register' && !empty($user)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, trans('auth.account_exists'));
        }
        if ($source === 'forget' && empty($user)) {
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, trans(SecurityReasonMap::ACCOUNT_OR_PASSWORD_INCORRECT));
        }

        try {
            sendCodeMsg($account, $type, $source);
        } catch (\Throwable $e) {
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, trans('auth.otp_unavailable'));
        }

        return ['sent' => true];
    }

    /**
     * 发起找回（复用 V1.x sendCodeMsg，仅对存在账号发送）。
     */
    public function recovery(array $data): array
    {
        $userService = new UserService();
        $user = $userService->getUserByAccount($data['account']);
        if (empty($user)) {
            // 枚举防护：不区分存在性
            return ['sent' => true];
        }
        try {
            sendCodeMsg($data['account'], $data['type'] ?? 'email', 'forget');
        } catch (\Throwable $e) {
            // 静默失败，不泄露
        }
        return ['sent' => true];
    }

    /**
     * 重置密码（复用 V1.x verifyCodeMsg + UserService::modifyPassword）。
     */
    public function passwordReset(array $data): array
    {
        $userService = new UserService();
        $user = $userService->getUserByAccount($data['account']);
        if (empty($user)) {
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, trans(SecurityReasonMap::ACCOUNT_OR_PASSWORD_INCORRECT));
        }
        if (!verifyCodeMsg($data['account'], $data['vcode'], 'email')) {
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, trans(SecurityReasonMap::resolve('OTP_INVALID')));
        }
        $userService->modifyPassword($user, $data['password']);
        return ['reset' => true];
    }

    /**
     * 反查完整 UserAuthModel（login/codeLogin 只返回 toM 摘要，需补 refresh_token/expired_time）。
     */
    private function fetchLoginAuth(string $userId, string $accessToken): ?UserAuthModel
    {
        if ($accessToken === '') {
            return null;
        }
        $authService = new UserAuthService();
        return $authService->getUserLoginAuth($accessToken);
    }

    /**
     * 登录成功后签发 V2 AuthSession + MFA 门禁判断。
     *
     * @return array AuthTokenResponse
     */
    private function issueSession(string $userId, string $accessToken, string $refreshToken, int $expiresAt): array
    {
        $mfaService = new MfaEnrollmentService();
        $sessionService = new AuthSessionService();
        $http = Http::getInstance(\request());

        $mfaRequired = $mfaService->hasActive($userId);

        $session = $sessionService->issue(
            $userId,
            $accessToken,
            $this->buildDeviceInfo($http),
            (string) $http->getClientIP(),
            !$mfaRequired,
            $expiresAt,
            $this->idempotencyKey(),
            $this->auditEventId()
        );

        return [
            'token_type'    => 'Bearer',
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => max(0, $expiresAt - time()),
            'session_id'    => (string) $session->session_id,
            'mfa_required'  => $mfaRequired,
        ];
    }

    private function buildDeviceInfo(Http $http): string
    {
        return json_encode([
            'os'      => $http->getClientOS('os'),
            'browser' => $http->getBrowser('browser'),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function idempotencyKey(): string
    {
        return \support\middleware\RequestContext::getContext()['idempotency_key'] ?? '';
    }

    private function auditEventId(): string
    {
        return '';
    }

    private function mapAuthException(\Throwable $e): \Throwable
    {
        if ($e instanceof DomainException) {
            return $e;
        }
        // V1.x VerifyException 的 message 已是枚举安全文案；透传为 401
        return new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, $e->getMessage(), $e);
    }
}
