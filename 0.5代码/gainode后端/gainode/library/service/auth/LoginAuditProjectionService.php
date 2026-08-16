<?php

declare(strict_types=1);

namespace library\service\auth;

use support\extend\ProjectionService;
use library\response\auth\LoginAuditResponse;

/**
 * LoginAudit 投影服务（05 §3，非持久投影）。
 *
 * source-of-truth 未在 05 明确（V1.x user_logs vs MC2 audit_events），属 Contract Gap G1。
 * 未裁决前 source 不可用 → 一律返回 UNAVAILABLE，不读取任何表（避免依赖未裁决 schema）。
 *
 * 越权（跨用户）访问返回 UNAVAILABLE 安全 reason，不泄露对象存在性。
 */
class LoginAuditProjectionService extends ProjectionService
{
    /**
     * 获取登录审计投影（source 未裁决 → UNAVAILABLE）。
     *
     * @param string $viewerUserId 当前访问者
     * @param string $targetUserId 目标用户
     */
    public function getAudit(string $viewerUserId, string $targetUserId): LoginAuditResponse
    {
        $response = new LoginAuditResponse();

        // 越权：不泄露存在性
        if ($viewerUserId !== $targetUserId) {
            $this->applyMetadata($response, $this->unavailableMetadata('projection.access_denied'));
            return $response;
        }

        // source-of-truth 未裁决（Contract Gap G1）→ UNAVAILABLE，不读取任何表
        $this->applyMetadata($response, $this->unavailableMetadata('projection.source_unavailable'));

        return $response;
    }
}
