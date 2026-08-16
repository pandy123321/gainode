<?php

declare(strict_types=1);

namespace library\response\auth;

use support\extend\ProjectionResponse;

/**
 * SessionDevice 投影响应（05 §3 SessionDevice，非持久投影）。
 *
 * 只读聚合/Projection：从 auth_sessions.device_info 派生，不写任何表。
 * 越权访问不返回本对象（由服务层抛 AuthorizeException，不泄露存在性）。
 */
class SessionDeviceResponse extends ProjectionResponse
{
    /** @var string|null 会话ID */
    public ?string $session_id = null;

    /** @var string|null 设备指纹（device_info 缺失 → null） */
    public ?string $device_fingerprint = null;

    /** @var string|null 操作系统 */
    public ?string $os = null;

    /** @var string|null 浏览器 */
    public ?string $browser = null;

    /** @var string|null IP 地址 */
    public ?string $ip = null;

    /** @var string|null 位置区域（device_info 缺失 → null） */
    public ?string $location_region = null;

    /** @var int|null 最后活跃时间（Unix 秒） */
    public ?int $last_active_at = null;

    /** @var bool 是否当前会话 */
    public bool $is_current = false;

    /** @var bool 是否可撤销（撤销规则未冻结 → 默认 false，fail-closed） */
    public bool $revocable = false;
}
