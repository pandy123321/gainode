<?php

declare(strict_types=1);

namespace library\response\auth;

use support\extend\ProjectionResponse;

/**
 * LoginAudit 投影响应（05 §3 LoginAudit，非持久投影）。
 *
 * 只读聚合/Projection。source-of-truth 未在 05 明确（V1.x user_logs vs MC2 audit_events），
 * 属 Contract Gap G1，未裁决前 source 不可用 → 本响应默认 UNAVAILABLE。
 * 越权访问不返回本对象（由服务层抛 AuthorizeException，不泄露存在性）。
 */
class LoginAuditResponse extends ProjectionResponse
{
    /** @var string|null 审计ID */
    public ?string $audit_id = null;

    /** @var string|null 用户ID */
    public ?string $user_id = null;

    /** @var string|null 事件类型 */
    public ?string $event_type = null;

    /** @var string|null IP 地址 */
    public ?string $ip_address = null;

    /** @var string|null 设备指纹 */
    public ?string $device_fingerprint = null;

    /** @var string|null 结果（success/failure 等，source 未明确） */
    public ?string $outcome = null;

    /** @var string|null 失败原因码 */
    public ?string $failure_reason_code = null;

    /** @var string|null 挑战类型（MFA/验证码等，source 未明确） */
    public ?string $challenge_type = null;

    /** @var int|null 创建时间（Unix 秒） */
    public ?int $created_at = null;
}
