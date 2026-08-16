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
 * 三分支（07 §S02-P02 步骤 6）：global_p / AI eligibility / Prediction eligibility
 * 三者独立计算、互不推导（05 §3 User 字段为 source，06 参数未冻结 → 默认 deny）。
 *
 * 绝不 mock、绝不回退旧值、绝不前端推导（05 §9）。越权访问返回 UNAVAILABLE 安全 reason。
 */
class FeatureEntitlementProjectionService extends ProjectionService
{
    /** @var string 原因码：Feature 规则参数未冻结 */
    public const REASON_FEATURE_RULE_UNAVAILABLE = 'FEATURE_RULE_UNAVAILABLE';

    /** @var string 功能键：global_p 等级 */
    public const FEATURE_GLOBAL_P = 'global_p';
    /** @var string 功能键：AI 奖励资格 */
    public const FEATURE_AI = 'ai_reward_eligibility';
    /** @var string 功能键：Prediction 资格 */
    public const FEATURE_PREDICTION = 'prediction_eligibility';

    /**
     * 计算功能资格投影。
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

    /**
     * 资格三分支聚合（07 §S02-P02 步骤 6）。
     *
     * @return array{global_p: FeatureEntitlementResponse, ai: FeatureEntitlementResponse, prediction: FeatureEntitlementResponse}
     */
    public function getEligibilityBundle(string $viewerUserId, string $targetUserId): array
    {
        return [
            'global_p'   => $this->getEntitlement($viewerUserId, $targetUserId, self::FEATURE_GLOBAL_P),
            'ai'         => $this->getEntitlement($viewerUserId, $targetUserId, self::FEATURE_AI),
            'prediction' => $this->getEntitlement($viewerUserId, $targetUserId, self::FEATURE_PREDICTION),
        ];
    }
}
