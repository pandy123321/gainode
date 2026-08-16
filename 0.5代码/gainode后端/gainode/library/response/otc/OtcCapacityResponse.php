<?php

declare(strict_types=1);

namespace library\response\otc;

use support\extend\ProjectionResponse;

/**
 * OtcCapacity 投影响应（05 §3 OtcCapacity，非持久投影）。
 *
 * 只读聚合/Projection。容量/储备比例依赖 06 OTC 参数（TBC），未冻结时字段 null。
 * 容量数值一律 decimal string，禁 float。
 */
class OtcCapacityResponse extends ProjectionResponse
{
    /** @var string|null 方向（BUY/SELL） */
    public ?string $direction = null;

    /** @var string|null 用户剩余容量（decimal string，依赖 inventory_limit TBC → null） */
    public ?string $user_remaining_capacity = null;

    /** @var string|null 全局剩余容量（decimal string，依赖 inventory_limit TBC → null） */
    public ?string $global_remaining_capacity = null;

    /** @var string|null 储备比例（依赖 otc.*_reserve_ratio TBC → null） */
    public ?string $reserve_ratio = null;

    /** @var string|null 规则版本号（未冻结 → null） */
    public ?string $rule_version = null;

    /** @var string|null 参数发布版本ID（未冻结 → null） */
    public ?string $parameter_release_id = null;
}
