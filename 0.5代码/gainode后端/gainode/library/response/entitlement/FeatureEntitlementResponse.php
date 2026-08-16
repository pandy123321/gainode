<?php

declare(strict_types=1);

namespace library\response\entitlement;

use support\extend\ProjectionResponse;

/**
 * FeatureEntitlement 投影响应（05 §3 FeatureEntitlement，非持久投影）。
 *
 * 功能资格投影：输出 allowed/denied/reason_codes/allowed_actions（07 S01-P06 步骤 4）。
 * 资格规则依赖 06 Feature 参数（TBC），未冻结时 allowed=false。
 */
class FeatureEntitlementResponse extends ProjectionResponse
{
    /** @var string|null 功能键 */
    public ?string $feature_key = null;

    /** @var bool 是否允许（默认 deny） */
    public bool $allowed = false;

    /** @var string|null 原因码 */
    public ?string $reason_code = null;

    /** @var string|null 原因文案 I18N key */
    public ?string $reason_text_key = null;

    /**
     * @var array 允许的动作列表（07 步骤 4 要求；05 §3 缺失 → Contract Gap G2，
     *            未裁决前返回空数组，不自行推断）
     */
    public array $allowed_actions = [];

    /** @var string|null 策略版本号（未冻结 → null） */
    public ?string $policy_version = null;

    /** @var string|null 规则版本号（未冻结 → null） */
    public ?string $rule_version = null;

    /** @var int|null 过期时间（Unix 秒） */
    public ?int $expires_at = null;
}
