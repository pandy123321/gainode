<?php

declare(strict_types=1);

namespace library\service\auth;

use support\extend\ProjectionService;
use library\dao\auth\MfaEnrollmentDao;
use library\model\auth\MfaEnrollmentModel;
use library\response\auth\SecurityProfileResponse;

/**
 * SecurityProfile 投影服务（05 §3，非持久投影）。
 *
 * 只读聚合：mfa_enrolled_methods 来自 mfa_enrollments（已建表，可读）；
 * login_history_window / suspicious_flags / mfa_required_actions 依赖安全策略参数（06 TBC）
 * → 保持 null/空，source_status=PARTIAL，绝不 mock、绝不回退旧值。
 *
 * 越权（跨用户）访问返回 UNAVAILABLE 安全 reason，不泄露对象存在性（05 §11.1）。
 */
class SecurityProfileProjectionService extends ProjectionService
{
    private MfaEnrollmentDao $mfaEnrollmentDao;

    public function __construct()
    {
        $this->mfaEnrollmentDao = new MfaEnrollmentDao();
    }

    /**
     * 获取安全画像投影。
     *
     * @param string $viewerUserId 当前访问者
     * @param string $targetUserId 目标用户
     */
    public function getProfile(string $viewerUserId, string $targetUserId): SecurityProfileResponse
    {
        $response = new SecurityProfileResponse();

        // 越权：不泄露存在性，返回安全 reason
        if ($viewerUserId !== $targetUserId) {
            $this->applyMetadata($response, $this->unavailableMetadata('projection.access_denied'));
            return $response;
        }

        $response->user_id = $targetUserId;

        // mfa_enrolled_methods 可读（已建表 mfa_enrollments，仅 active 态）
        $enrollments = $this->mfaEnrollmentDao->fetchAll([
            'user_id' => $targetUserId,
            'status' => MfaEnrollmentModel::STATUS_ACTIVE,
        ]);
        $methods = [];
        foreach ($enrollments as $enrollment) {
            $methods[] = $enrollment->method_type;
        }
        $response->mfa_enrolled_methods = $methods;

        // 可读部分为实时数据；安全策略 TBC → source_status=PARTIAL
        $this->applyMetadata($response, $this->realtimeMetadata());
        $response->source_status = self::SOURCE_STATUS_PARTIAL;

        return $response;
    }
}
