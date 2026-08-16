<?php

declare(strict_types=1);

namespace library\service\auth;

use library\dao\auth\AuthSessionDao;
use library\model\auth\AuthSessionModel;
use support\extend\Service;
use support\utils\Random;

/**
 * 会话 Service — auth_sessions 表唯一 Authoritative Writer（S02-P02 状态转移落地）
 *
 * @authoritative_writer auth_sessions
 *
 * 状态机（05 §2.2 canonical）：active / mfa_required / restricted / expired / revoked
 *
 * 转移矩阵属 2B-2 CANDIDATE。本包按 07 §S02-P02 已列转移实现：
 *   issue → active | mfa_required
 *   refresh → 更新 token_hash（refresh rotation 在 Application 层经 V1.x refresh_token 校验）
 *   revoke → revoked
 *   expire → 惰性判定（读取时 expires_at <= now 视为 expired，不写回）
 * 未列转移（如 restricted 进入/解除）保持 FAIL_CLOSED。
 *
 * token_hash 只存哈希（sha256），不存明文 token；refresh token 仍由 V1.x
 * UserAuthModel 持久（DDL 冻结，本表无 refresh_token 字段）。
 */
class AuthSessionService extends Service
{
    public function __construct()
    {
        $this->dao = AuthSessionDao::class;
        parent::__construct();
    }

    /**
     * 签发会话（登录/MFA 通过后调用）。
     *
     * @return AuthSessionModel
     */
    public function issue(
        string $userId,
        string $accessToken,
        string $deviceInfo,
        string $ipAddress,
        bool $mfaVerified,
        int $expiresAt,
        string $idempotencyKey,
        string $auditEventId
    ): AuthSessionModel {
        $data = [
            'session_id'      => (string) Random::getSnowflakeID(),
            'user_id'         => $userId,
            'token_hash'      => $this->hashToken($accessToken),
            'status'          => $mfaVerified
                ? AuthSessionModel::STATUS_ACTIVE
                : AuthSessionModel::STATUS_MFA_REQUIRED,
            'device_info'     => $deviceInfo,
            'ip_address'      => $ipAddress,
            'mfa_verified'    => $mfaVerified ? 1 : 0,
            'expires_at'      => $expiresAt,
            'object_version'  => 0,
            'idempotency_key' => $idempotencyKey,
            'audit_event_id'  => $auditEventId,
        ];

        return $this->create($data);
    }

    /**
     * 按访问 token 哈希查找会话（不区分状态）。
     */
    public function findByToken(string $accessToken): ?AuthSessionModel
    {
        return $this->getNewDao()->fetch(['token_hash' => $this->hashToken($accessToken)]);
    }

    /**
     * 刷新后更新会话的访问 token 哈希（rotation）。
     */
    public function rotateAccessToken(AuthSessionModel $session, string $newAccessToken): AuthSessionModel
    {
        $session->update(['token_hash' => $this->hashToken($newAccessToken)]);
        return $session;
    }

    /**
     * 撤销单个会话（本人/安全吊销）。
     * 目标会话必须属于同一用户；对象不存在或已撤销返回 true（幂等），越权返回 false。
     */
    public function revoke(string $sessionId, string $userId): bool
    {
        $session = $this->get($sessionId);
        if (empty($session) || $session->user_id !== $userId) {
            return false;
        }
        if ($session->status !== AuthSessionModel::STATUS_REVOKED) {
            $session->update(['status' => AuthSessionModel::STATUS_REVOKED]);
        }
        return true;
    }

    /**
     * 登出当前用户全部会话，返回撤销数量。
     */
    public function revokeAll(string $userId): int
    {
        $sessions = $this->getByUser($userId);
        $count = 0;
        foreach ($sessions as $session) {
            if ($session->status !== AuthSessionModel::STATUS_REVOKED) {
                $session->update(['status' => AuthSessionModel::STATUS_REVOKED]);
                $count++;
            }
        }
        return $count;
    }

    /**
     * 惰性判定是否过期（不写回）。expired 是时间派生态，非持久转移。
     */
    public function isExpired(AuthSessionModel $session, int $now = 0): bool
    {
        $now = $now ?: time();
        return (int) $session->expires_at <= $now;
    }

    /**
     * 按用户查询会话（只读透传）。
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
