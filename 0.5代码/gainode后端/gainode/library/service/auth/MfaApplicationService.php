<?php

declare(strict_types=1);

namespace library\service\auth;

/**
 * MFA 应用服务（S02-P02 子流程 2：enrollment setup→confirm→challenge→recovery/disable）。
 *
 * 纯编排：委托 MfaEnrollmentService 领域状态转移。
 * TOTP secret 生成/校验受 Contract Gap（冻结 DDL 无 secret 字段）限制 fail-closed，
 * 因此 setup 返回 pending、disable 可执行、confirm/challenge 抛 DEPENDENCY_UNAVAILABLE。
 */
class MfaApplicationService
{
    /**
     * 发起注册 → pending。
     */
    public function setup(string $userId, string $methodType = 'totp'): array
    {
        $service = new MfaEnrollmentService();
        $enrollment = $service->setup(
            $userId,
            $methodType,
            '{}',
            $this->idempotencyKey(),
            ''
        );
        return [
            'enrollment_id' => (string) $enrollment->enrollment_id,
            'method_type'   => (string) $enrollment->method_type,
            'status'        => (string) $enrollment->status,
            'secret'        => null,   // fail-closed：secret 存储未冻结
            'otpauth_url'   => null,
        ];
    }

    /**
     * 确认注册（fail-closed：TOTP 校验不可用）。
     */
    public function confirm(string $userId, string $enrollmentId, string $code): array
    {
        $service = new MfaEnrollmentService();
        $service->confirm($enrollmentId, $userId, $code);
        return ['confirmed' => true];
    }

    /**
     * 挑战（fail-closed）。
     */
    public function challenge(string $userId, string $code): array
    {
        $service = new MfaEnrollmentService();
        $service->challenge($userId, $code);
        return ['verified' => true];
    }

    /**
     * 停用/吊销。
     */
    public function disable(string $userId, string $enrollmentId): array
    {
        $service = new MfaEnrollmentService();
        $service->disable($enrollmentId, $userId);
        return ['disabled' => true];
    }

    private function idempotencyKey(): string
    {
        return \support\middleware\RequestContext::getContext()['idempotency_key'] ?? '';
    }
}
