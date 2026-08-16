<?php

declare(strict_types=1);

namespace library\response\otc;

use support\extend\ProjectionResponse;

/**
 * OtcEligibility 投影响应（05 §3 OtcEligibility，非持久投影）。
 *
 * 每个请求/用户评估的资格投影，非持久实体。不得定义成 OtcEligibility.status 七选一状态机。
 * reason_code 不得覆盖 OtcOrder.status。
 * 容量/Power 数值一律 decimal string，禁 float。
 */
class OtcEligibilityResponse extends ProjectionResponse
{
    // ---- reason_code 枚举（05 §3 冻结）----
    public const REASON_KYC_REQUIRED = 'KYC_REQUIRED';
    public const REASON_SECURITY_VERIFICATION_REQUIRED = 'SECURITY_VERIFICATION_REQUIRED';
    public const REASON_OTC_CAPACITY_INSUFFICIENT = 'OTC_CAPACITY_INSUFFICIENT';
    public const REASON_INSUFFICIENT_POWER = 'INSUFFICIENT_POWER';
    public const REASON_UNDER_REVIEW = 'UNDER_REVIEW';
    public const REASON_REGION_UNAVAILABLE = 'REGION_UNAVAILABLE';
    public const REASON_MAINTENANCE = 'MAINTENANCE';

    /** @var bool 是否允许 OTC（默认 deny） */
    public bool $allowed = false;

    /** @var bool 是否允许买入 */
    public bool $buy_allowed = false;

    /** @var bool 是否允许卖出 */
    public bool $sell_allowed = false;

    /** @var string|null 原因码（05 §3 七选一） */
    public ?string $reason_code = null;

    /** @var string|null 原因文案 I18N key */
    public ?string $reason_text_key = null;

    /** @var string|null 下一步动作 */
    public ?string $next_action = null;

    /** @var string|null 策略版本号（未冻结 → null） */
    public ?string $policy_version = null;

    /** @var string|null 规则版本号（未冻结 → null） */
    public ?string $rule_version = null;

    /** @var array|null 容量（结构未在 05 明确 → null，Contract Gap G3） */
    public ?array $capacity = null;

    /** @var array|null Power 影响（依赖 Power 参数 TBC → null） */
    public ?array $power_impact = null;

    /** @var int|null 过期时间（Unix 秒） */
    public ?int $expires_at = null;
}
