<?php

declare(strict_types=1);

namespace app\api\controller;

use library\service\auth\MfaApplicationService;
use library\service\entitlement\EligibilityApplicationService;
use library\service\user\UserApplicationService;
use support\controller\ApiV2;
use support\Response;

/**
 * User / MFA / SecurityProfile / Eligibility / LoginAudit 控制器（05 §3）。
 *
 * 全部为 me 作用域只读 + MFA 注册写路径。资格聚合与登录审计均 fail-closed。
 */
class UserController extends ApiV2
{
    /** GET /api/v1/me */
    public function me(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new UserApplicationService())->me($userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/me/mfa/enrollments */
    public function mfaEnrollmentSetup(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $data = $this->getPost();
            $result = (new MfaApplicationService())->setup($userId, (string) ($data['method_type'] ?? 'totp'));
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/me/mfa/enrollments/{id}/confirm */
    public function mfaEnrollmentConfirm(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $data = $this->getPost();
            $result = (new MfaApplicationService())->confirm($userId, $id, (string) ($data['code'] ?? ''));
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/me/mfa/enrollments/{id}/disable */
    public function mfaEnrollmentDisable(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new MfaApplicationService())->disable($userId, $id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/me/security-profile */
    public function securityProfile(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new UserApplicationService())->securityProfile($userId, $userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/me/eligibility */
    public function eligibility(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new EligibilityApplicationService())->getBundle($userId, $userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/me/login-audit */
    public function loginAudit(): Response
    {
        try {
            $this->request->getTokenUser();
            $userId = (string) $this->request->getUserID();
            $result = (new UserApplicationService())->loginAudit($userId, $userId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
