<?php

declare(strict_types=1);

namespace library\service\power;

use support\extend\ProjectionService;
use library\dao\power\PowerPositionDao;
use library\response\power\PowerImpactPreviewResponse;

/**
 * PowerImpactPreview 投影服务（05 §3，非持久投影）。
 *
 * 只读聚合：available_before/frozen_before 来自 power_positions（MC1 已建，可读）。
 * required_power/freeze_power/consume_power/release_power/power_cap/available_after_preview
 * 依赖 AI.power_* 参数（06 TBC）→ 保持 null，allowed=false（默认 deny，绝不 mock）。
 *
 * Power 数值一律 decimal string，禁 float。
 * 越权访问返回 UNAVAILABLE 安全 reason。
 */
class PowerImpactPreviewProjectionService extends ProjectionService
{
    // ---- 动作类型（05 §3，冻结）----
    public const ACTION_OTC_SELL = 'OTC_SELL';
    public const ACTION_WITHDRAWAL = 'WITHDRAWAL';
    public const ACTION_ROBOT_START = 'ROBOT_START';

    public const ACTIONS = [
        self::ACTION_OTC_SELL,
        self::ACTION_WITHDRAWAL,
        self::ACTION_ROBOT_START,
    ];

    /** @var string 原因码：Power 规则参数未冻结 */
    public const REASON_POWER_RULE_UNAVAILABLE = 'POWER_RULE_UNAVAILABLE';

    private PowerPositionDao $powerPositionDao;

    public function __construct()
    {
        $this->powerPositionDao = new PowerPositionDao();
    }

    /**
     * 计算 Power 影响预览（仅服务端计算，05 §9）。
     *
     * @param string $viewerUserId 当前访问者
     * @param string $targetUserId 目标用户
     * @param string $actionType   动作类型（OTC_SELL/WITHDRAWAL/ROBOT_START）
     */
    public function preview(string $viewerUserId, string $targetUserId, string $actionType): PowerImpactPreviewResponse
    {
        $response = new PowerImpactPreviewResponse();

        if ($viewerUserId !== $targetUserId) {
            $this->applyMetadata($response, $this->unavailableMetadata('projection.access_denied'));
            return $response;
        }

        $response->action_type = $actionType;

        // 读取 power_positions（可读部分：available_before/frozen_before/robot_level）
        $position = $this->powerPositionDao->getByUser($targetUserId);
        $positionReadable = $position !== null;

        if ($positionReadable) {
            $response->available_before = (string) $position->available;
            $response->frozen_before = (string) $position->frozen;
            $response->robot_level = $position->power_cap_source_robot_level !== null
                ? (string) $position->power_cap_source_robot_level
                : null;
            $response->rule_version = $position->rule_version !== null && $position->rule_version !== ''
                ? (string) $position->rule_version
                : null;
            $response->parameter_release_id = $position->parameter_release_id !== null && $position->parameter_release_id !== ''
                ? (string) $position->parameter_release_id
                : null;
        }

        // Power 消耗/冻结/释放规则（AI.power_* TBC）→ 无法计算预览 → 默认 deny
        $response->allowed = false;
        $response->reason_code = self::REASON_POWER_RULE_UNAVAILABLE;

        if ($positionReadable) {
            // 持仓可读（实时），但动作规则 TBC → PARTIAL
            $this->applyMetadata($response, $this->realtimeMetadata($this->rawUnix($position, 'updated_time')));
            $response->source_status = self::SOURCE_STATUS_PARTIAL;
        } else {
            $this->applyMetadata($response, $this->unavailableMetadata());
        }

        return $response;
    }
}
