<?php

declare(strict_types=1);

namespace app\api\controller;

use library\service\auth\AuthApplicationService;
use library\service\auth\AuthSessionService;
use library\service\auth\MfaApplicationService;
use library\service\auth\SessionApplicationService;
use library\validator\AuthValidation;
use support\controller\ApiV2;
use support\Response;

/**
 * Auth / Session P0 控制器（05 §2.1）。
 *
 * 注册/登录/OTP/找回/重置 + MFA challenge + refresh/logout + 会话设备管理。
 * 写操作统一 Idempotency-Key（RequestContext 中间件强制）；失败统一 envelope。
 */
class AuthController extends ApiV2
{
    /** POST /api/v1/auth/register */
    public function register(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('register', $data, new AuthValidation());
            $result = (new AuthApplicationService())->register($data);
            return $this->envelope([
                'user_id' => $result['user_id'],
                'account' => $result['account'],
            ]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/login */
    public function login(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('login', $data, new AuthValidation());
            $result = (new AuthApplicationService())->login($data);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/otp/verify */
    public function otpVerify(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('otpVerify', $data, new AuthValidation());
            $result = (new AuthApplicationService())->otpVerify($data);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/otp/resend */
    public function otpResend(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('otpResend', $data, new AuthValidation());
            $result = (new AuthApplicationService())->otpResend($data);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/mfa/verify */
    public function mfaVerify(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('mfaVerify', $data, new AuthValidation());
            $userId = (string) $this->request->getUserID();
            $result = (new MfaApplicationService())->challenge($userId, (string) $data['code']);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/refresh */
    public function refresh(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('refresh', $data, new AuthValidation());
            $result = (new SessionApplicationService())->refresh((string) $data['refresh_token']);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/logout */
    public function logout(): Response
    {
        try {
            $this->request->getTokenUser(); // 强制鉴权
            $token = (string) $this->request->getToken('Token');
            $userId = (string) $this->request->getUserID();
            $result = (new SessionApplicationService())->logout($token, $userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/recovery */
    public function recovery(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('recovery', $data, new AuthValidation());
            $result = (new AuthApplicationService())->recovery($data);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/auth/password/reset */
    public function passwordReset(): Response
    {
        try {
            $data = $this->getPost();
            $this->validate('passwordReset', $data, new AuthValidation());
            $result = (new AuthApplicationService())->passwordReset($data);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/me/sessions */
    public function sessions(): Response
    {
        try {
            $this->request->getTokenUser();
            $token = (string) $this->request->getToken('Token');
            $userId = (string) $this->request->getUserID();
            $session = (new AuthSessionService())->findByToken($token);
            $currentSessionId = $session ? (string) $session->session_id : '';
            $result = (new SessionApplicationService())->list($userId, $currentSessionId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/me/sessions/{id}/revoke */
    public function sessionRevoke(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new SessionApplicationService())->revoke($userId, $id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
