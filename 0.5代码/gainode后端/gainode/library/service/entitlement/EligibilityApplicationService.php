<?php

declare(strict_types=1);

namespace library\service\entitlement;

/**
 * 资格聚合应用服务（S02-P02 子流程 5：FeatureEntitlement / allowed_actions 聚合）。
 *
 * 三分支（global_p / AI eligibility / Prediction eligibility）独立计算、互不推导
 * （05 §3 User 字段为 source；06 参数未冻结 → 默认 deny）。返回结构对齐
 * openapi eligibility.yaml#/EligibilityResponse。
 */
class EligibilityApplicationService
{
    /**
     * 获取资格聚合（global_p / AI / Prediction 三分支）。
     *
     * @return array{user_id:string, global_p:array, ai:array, prediction:array}
     */
    public function getBundle(string $viewerUserId, string $targetUserId): array
    {
        $service = new FeatureEntitlementProjectionService();
        $bundle = $service->getEligibilityBundle($viewerUserId, $targetUserId);

        return [
            'user_id'    => $targetUserId,
            'global_p'   => $bundle['global_p']->toArray(),
            'ai'         => $bundle['ai']->toArray(),
            'prediction' => $bundle['prediction']->toArray(),
        ];
    }
}
