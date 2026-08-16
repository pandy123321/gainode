<?php

declare(strict_types=1);

namespace library\service\otc;

use support\extend\ProjectionService;
use library\response\otc\OtcCapacityResponse;

/**
 * OtcCapacity 投影服务（05 §3，非持久投影）。
 *
 * 容量/储备比例依赖 06 OTC 参数（otc.inventory_limit / otc.settlement_reserve_ratio /
 * otc.risk_reserve_ratio，全部 TBC）→ 字段 null，data_status=UNAVAILABLE。
 * 绝不 mock、绝不回退旧值（05 §9）。
 *
 * 容量数值一律 decimal string，禁 float。
 */
class OtcCapacityProjectionService extends ProjectionService
{
    /**
     * 计算 OTC 容量投影。
     *
     * @param string $viewerUserId 当前访问者
     * @param string $targetUserId 目标用户
     * @param string $direction    BUY/SELL
     */
    public function getCapacity(string $viewerUserId, string $targetUserId, string $direction): OtcCapacityResponse
    {
        $response = new OtcCapacityResponse();

        if ($viewerUserId !== $targetUserId) {
            $this->applyMetadata($response, $this->unavailableMetadata('projection.access_denied'));
            return $response;
        }

        $response->direction = $direction;

        // OTC 容量/储备参数 TBC → 无法计算，字段 null（不回退旧值）
        $this->applyMetadata($response, $this->unavailableMetadata());

        return $response;
    }
}
