<?php

declare(strict_types=1);

namespace library\response\auth;

use support\extend\ProjectionResponse;

/**
 * SecurityProfile 投影响应（05 §3 SecurityProfile，非持久投影）。
 *
 * 只读聚合/Projection：仅返回服务端聚合结果，不写任何表。
 * 越权访问不返回本对象（由服务层抛 AuthorizeException，不泄露存在性）。
 *
 * 字段未冻结（login_history_window/suspicious_flags/mfa_required_actions）一律 null，
 * 不回退旧值、不填 mock。
 */
class SecurityProfileResponse extends ProjectionResponse
{
    /** @var string|null 用户ID（越权不返回） */
    public ?string $user_id = null;

    /** @var string[] MFA 已注册方法（来自 mfa_enrollments，可读） */
    public array $mfa_enrolled_methods = [];

    /** @var string[] 需 MFA 的动作列表（安全策略 TBC → 空） */
    public array $mfa_required_actions = [];

    /** @var string|null 登录历史窗口（安全策略 TBC → null） */
    public ?string $login_history_window = null;

    /** @var array|null 可疑标记（风控规则 TBC → null） */
    public ?array $suspicious_flags = null;

    /** @var int|null 最后改密时间（Unix 秒，源未冻结 → null） */
    public ?int $last_password_change = null;

    /** @var int|null 最后安全复核时间（Unix 秒，源未冻结 → null） */
    public ?int $last_security_review = null;

    /** @var string|null 策略版本号（未冻结 → null） */
    public ?string $policy_version = null;
}
