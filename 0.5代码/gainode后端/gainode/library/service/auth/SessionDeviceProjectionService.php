<?php

declare(strict_types=1);

namespace library\service\auth;

use support\extend\ProjectionService;
use library\dao\auth\AuthSessionDao;
use library\model\auth\AuthSessionModel;
use library\response\auth\SessionDeviceResponse;

/**
 * SessionDevice 投影服务（05 §3，非持久投影）。
 *
 * 只读聚合：从 auth_sessions.device_info（JSON）派生 os/browser/ip/device_fingerprint/location_region。
 * 不写任何表。device_info 缺失字段 → null（不回退旧值）。
 *
 * revocable 撤销规则未冻结 → 默认 false（fail-closed，不主动暴露可撤销）。
 * 越权（跨用户）访问返回 UNAVAILABLE 安全 reason，不泄露对象存在性。
 */
class SessionDeviceProjectionService extends ProjectionService
{
    private AuthSessionDao $authSessionDao;

    public function __construct()
    {
        $this->authSessionDao = new AuthSessionDao();
    }

    /**
     * 获取会话设备投影。
     *
     * @param string $viewerUserId    当前访问者
     * @param string $sessionId       目标会话ID
     * @param string|null $currentSessionId 当前请求会话ID（用于 is_current 判定）
     */
    public function getDevice(string $viewerUserId, string $sessionId, ?string $currentSessionId = null): SessionDeviceResponse
    {
        $response = new SessionDeviceResponse();

        /** @var AuthSessionModel|null $session */
        $session = $this->authSessionDao->get($sessionId);

        // 越权或不存在：统一返回 UNAVAILABLE，不泄露存在性（存在/不存在不可区分）
        if ($session === null || (string) $session->user_id !== $viewerUserId) {
            $this->applyMetadata($response, $this->unavailableMetadata('projection.access_denied'));
            return $response;
        }

        $response->session_id = (string) $session->session_id;
        $response->last_active_at = $this->rawUnix($session, 'updated_time');
        $response->is_current = ($currentSessionId !== null && $currentSessionId === $sessionId);
        // 撤销规则未冻结 → fail-closed 默认不可撤销
        $response->revocable = false;

        // 从 device_info JSON 派生设备字段；缺失 → null
        $device = $this->decodeDeviceInfo((string) $session->device_info);
        $response->os = $device['os'] ?? null;
        $response->browser = $device['browser'] ?? null;
        $response->device_fingerprint = $device['device_fingerprint'] ?? null;
        $response->location_region = $device['location_region'] ?? null;
        $response->ip = $session->ip_address !== null ? (string) $session->ip_address : null;

        $this->applyMetadata($response, $this->realtimeMetadata($this->rawUnix($session, 'updated_time')));

        return $response;
    }

    /**
     * 解析 device_info JSON（容错：非法/空 JSON 返回空数组）。
     *
     * @return array<string,mixed>
     */
    private function decodeDeviceInfo(string $deviceInfo): array
    {
        if ($deviceInfo === '') {
            return [];
        }
        $decoded = json_decode($deviceInfo, true);
        return is_array($decoded) ? $decoded : [];
    }
}
