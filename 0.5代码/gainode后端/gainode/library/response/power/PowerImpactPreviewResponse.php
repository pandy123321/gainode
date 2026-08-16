<?php

declare(strict_types=1);

namespace library\response\power;

use support\extend\ProjectionResponse;

/**
 * PowerImpactPreview 投影响应（05 §3 PowerImpactPreview，非持久投影）。
 *
 * 只读聚合/Projection：Power 影响预览只由服务端计算返回（05 §9），不写任何表。
 * 所有 Power 消耗/冻结/释放规则依赖 AI.power_* 参数（06 TBC），未冻结时 allowed=false。
 * 金额/Power 一律 decimal string，禁 float。
 */
class PowerImpactPreviewResponse extends ProjectionResponse
{
    /** @var string|null 动作类型（OTC_SELL / WITHDRAWAL / ROBOT_START） */
    public ?string $action_type = null;

    /** @var string|null 所需 Power（decimal string，参数 TBC → null） */
    public ?string $required_power = null;

    /** @var string|null 冻结 Power（decimal string，参数 TBC → null） */
    public ?string $freeze_power = null;

    /** @var string|null 消耗 Power（decimal string，参数 TBC → null） */
    public ?string $consume_power = null;

    /** @var string|null 释放 Power（decimal string，参数 TBC → null） */
    public ?string $release_power = null;

    /** @var string|null 动作前可用 Power（来自 power_positions，可读） */
    public ?string $available_before = null;

    /** @var string|null 动作后可用 Power（依赖消耗规则 TBC → null） */
    public ?string $available_after_preview = null;

    /** @var string|null 动作前冻结 Power（来自 power_positions，可读） */
    public ?string $frozen_before = null;

    /** @var string|null 动作后冻结 Power（依赖冻结规则 TBC → null） */
    public ?string $frozen_after_preview = null;

    /** @var string|null Robot 等级（可读） */
    public ?string $robot_level = null;

    /** @var string|null Power 上限（依赖 AI.power_cap_by_robot_level TBC → null） */
    public ?string $power_cap = null;

    /** @var string|null 规则版本号（未冻结 → null） */
    public ?string $rule_version = null;

    /** @var string|null 参数发布版本ID（未冻结 → null） */
    public ?string $parameter_release_id = null;

    /** @var int|null 过期时间（Unix 秒） */
    public ?int $expires_at = null;

    /** @var bool 是否允许（参数 TBC → false） */
    public bool $allowed = false;

    /** @var string|null 原因码 */
    public ?string $reason_code = null;
}
