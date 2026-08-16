<?php

declare(strict_types=1);

namespace library\service\entitlement;

use support\extend\ProjectionService;
use library\response\entitlement\FeatureEntitlementResponse;

/**
 * FeatureEntitlement 投影服务（05 §3，非持久投影）。
 *
 * 功能资格投影。资格规则依赖 06 Feature 参数（TBC）→ 未冻结时默认 deny：
 *   allowed=false、reason_code=FEATURE_RULE_UNAVAILABLE、data_status=UNAVAILABLE。
 * allowed_actions 字段在 05 §3 缺失（Contract Gap G2）→ 未裁决前返回空数组，不自行推断。
 *
 * 绝不 mock、绝不回退旧值、绝不前端推导（05 §9）。
 * 越权访问返回 UNAVAILABLE 安全 reason。
 */
class FeatureEntitlementProjectionService extends ProjectionService
{
    /** @var string 原因码：Feature 规则参数未冻结 */
    public const REASON_FEATURE_RULE_UNAVAILABLE = 'FEATURE_RULE_UNAVAILABLE';

    /**
     * 计算功能资格投影。
     *
     * @param string $viewerUserId 当前访问者
     * @param string $targetUserId 目标用户
     * @param string $featureKey   功能键
     */
    public function getEntitlement(string $viewerUserId, string $targetUserId, string $featureKey): FeatureEntitlementResponse
    {
        $response = new FeatureEntitlementResponse();

        if ($viewerUserId !== $targetUserId) {
            $this->applyMetadata($response, $this->unavailableMetadata('projection.access_denied'));
            return $response;
        }

        $response->feature_key = $featureKey;

        // Feature 规则参数 TBC → 默认 deny
        $response->allowed = false;
        $response->reason_code = self::REASON_FEATURE_RULE_UNAVAILABLE;
        $response->reason_text_key = 'entitlement.feature_rule_unavailable';
        // allowed_actions 字段 05 §3 缺失（Contract Gap G2）→ 空数组，不自行推断
        $response->allowed_actions = [];

        $this->applyMetadata($response, $this->unavailableMetadata());

        return $response;
    }
}
