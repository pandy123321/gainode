<?php

declare(strict_types=1);

namespace library\service\auth;

use library\dict\ErrorDict;
use library\dict\SecurityReasonMap;
use library\model\auth\AuthSessionModel;
use library\service\member\UserAuthService;
use library\service\member\UserService;
use support\exception\DomainException;
use support\utils\JwtToken;

/**
 * Session 应用服务（S02-P02 子流程 3：refresh rotation → list devices → revoke one → logout all）。
 *
 * 复用 V1.x UserAuthModel.refresh_token 持久（DDL 冻结）+ JwtToken 签发，
 * 桥接 V2 AuthSession.token_hash 生命周期。refresh 轮换：旧 refresh 立即失效，
 * 重放/已过期 refresh fail-closed（AUTH_UNAUTHENTICATED）。
 */
class SessionApplicationService
{
    /**
     * 刷新 session（refresh rotation）。
     *
     * @return array AuthTokenResponse
     */
    public function refresh(string $refreshToken): array
    {
        $authService = new UserAuthService();
        $auth = $authService->fetch(['refresh_token' => $refreshToken]);

        if (empty($auth) || (int) $auth->status !== 1 || (int) $auth->expired_time <= time()) {
            // 重放或已撤销/过期 → fail-closed
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, trans(SecurityReasonMap::resolve('REFRESH_REPLAYED')));
        }

        $oldAccessToken = (string) $auth->access_token;
        $userId = (int) $auth->user_id;

        $user = (new UserService())->get($userId);
        if (empty($user)) {
            throw new DomainException(ErrorDict::AUTH_UNAUTHENTICATED, trans(SecurityReasonMap::ACCOUNT_OR_PASSWORD_INCORRECT));
        }
        $account = (string) $user->account;

        $memberAuth = new MemberAuth(\request());
        $expireTime = (int) config('app.jwt_expire');
        $newAccessToken = $memberAuth->createUserToken($userId, $account, $expireTime);
        $newRefreshToken = JwtToken::getToken(['id' => $userId, 'account' => $account]);

        // 轮换：旧 refresh 立即失效
        $auth->update([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expired_time'  => time() + $expireTime,
        ]);

        // 更新 V2 AuthSession token_hash（按旧 access token 定位会话后轮换）
        $sessionService = new AuthSessionService();
        $session = $sessionService->findByToken($oldAccessToken);
        if (!empty($session)) {
            $sessionService->rotateAccessToken($session, $newAccessToken);
        }

        return [
            'token_type'    => 'Bearer',
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in'    => $expireTime,
            'session_id'    => $session ? (string) $session->session_id : '',
            'mfa_required'  => false,
        ];
    }

    /**
     * 列出当前用户会话设备（05 §3 SessionDevice 投影）。
     */
    public function list(string $userId, string $currentSessionId): array
    {
        $sessionService = new AuthSessionService();
        $sessions = $sessionService->getByUser($userId);

        $devices = [];
        foreach ($sessions as $session) {
            $device = json_decode((string) $session->device_info, true) ?: [];
            $devices[] = [
                'session_id'         => (string) $session->session_id,
                'device_fingerprint' => $device['device_fingerprint'] ?? null,
                'os'                 => $device['os'] ?? null,
                'browser'            => $device['browser'] ?? null,
                'ip'                 => (string) $session->ip_address,
                'location_region'    => $device['location_region'] ?? null,
                'last_active_at'     => $this->rawUnix($session, 'updated_time'),
                'is_current'         => (string) $session->session_id === $currentSessionId,
                'revocable'          => (string) $session->status !== AuthSessionModel::STATUS_REVOKED
                    && (string) $session->session_id !== $currentSessionId,
            ];
        }
        return ['sessions' => $devices];
    }

    /**
     * 撤销指定会话（仅本人）。
     */
    public function revoke(string $userId, string $sessionId): array
    {
        $sessionService = new AuthSessionService();
        if (!$sessionService->revoke($sessionId, $userId)) {
            throw new DomainException(ErrorDict::AUTH_FORBIDDEN);
        }
        return ['revoked' => true];
    }

    /**
     * 退出当前用户（V1.x 删除 token + V2 撤销全部会话）。
     */
    public function logout(string $token, string $userId): array
    {
        $memberAuth = new MemberAuth(\request());
        $memberAuth->logout($token);
        (new AuthSessionService())->revokeAll($userId);
        return ['logged_out' => true];
    }

    /**
     * 从 Model 读取原始 Unix 时间字段（规避 $dateFormat='U' 的 Carbon 强转告警）。
     */
    private function rawUnix($model, string $field): ?int
    {
        $raw = $model->getRawOriginal($field);
        if ($raw === null || $raw === '') {
            return null;
        }
        if ($raw instanceof \DateTimeInterface) {
            return $raw->getTimestamp();
        }
        return (int) $raw;
    }
}
