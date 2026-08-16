<?php

declare(strict_types=1);

namespace library\service\auth;

use library\dao\auth\MfaEnrollmentDao;
use library\model\auth\MfaEnrollmentModel;
use support\extend\Service;
use support\utils\Random;
use support\exception\DomainException;
use library\dict\ErrorDict;

/**
 * MFA 注册 Service — mfa_enrollments 表唯一 Authoritative Writer（S02-P02 状态转移落地）
 *
 * @authoritative_writer mfa_enrollments
 *
 * 状态机（05 §4 V2.4 canonical，Owner 2B2-ENUM-02）：pending / active / revoked
 *   setup(method=totp) → pending
 *   confirm(code)       → active（enrolled_at=last_verified_at=now）
 *   challenge(active)   → 保持 active，更新 last_verified_at
 *   disable/recovery    → revoked
 *
 * 转移矩阵属 2B-2 CANDIDATE。本包按 07 §S02-P02 已列转移实现。
 *
 * 已知 Contract Gap：冻结 DDL 无 `secret` 字段（05 §3 MfaEnrollment 未列 secret）。
 * 因此 TOTP secret 的生成/存储/校验 FAIL_CLOSED（DEPENDENCY_UNAVAILABLE），
 * 仅 setup（建 pending 记录）与 disable（→revoked）可执行；confirm/challenge 保持 fail-closed，
 * 直到 Owner 裁决 MFA secret 存储方案（OPEN_OWNER_DECISION）。
 */
class MfaEnrollmentService extends Service
{
    public const METHOD_TOTP = 'totp';

    public function __construct()
    {
        $this->dao = MfaEnrollmentDao::class;
        parent::__construct();
    }

    /**
     * 发起 MFA 注册（method=totp）→ pending。
     *
     * @return MfaEnrollmentModel
     */
    public function setup(
        string $userId,
        string $methodType,
        string $deviceInfo,
        string $idempotencyKey,
        string $auditEventId
    ): MfaEnrollmentModel {
        if ($methodType !== self::METHOD_TOTP) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, '不支持的 MFA 方法类型');
        }

        $existing = $this->getNewDao()->fetch([
            'user_id' => $userId,
            'status'  => MfaEnrollmentModel::STATUS_ACTIVE,
        ]);
        if (!empty($existing)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'MFA 已启用');
        }

        $data = [
            'enrollment_id'      => (string) Random::getSnowflakeID(),
            'user_id'            => $userId,
            'method_type'        => $methodType,
            'status'             => MfaEnrollmentModel::STATUS_PENDING,
            'enrolled_at'        => time(),
            'last_verified_at'   => 0,
            'backup_codes_active'=> 0,
            'device_info'        => $deviceInfo,
            'object_version'     => 0,
            'idempotency_key'    => $idempotencyKey,
            'audit_event_id'     => $auditEventId,
        ];

        return $this->create($data);
    }

    /**
     * 确认注册（pending → active）。
     *
     * Contract Gap：冻结 DDL 无 secret 字段，TOTP 校验不可用 → FAIL_CLOSED。
     *
     * @throws DomainException DEPENDENCY_UNAVAILABLE
     */
    public function confirm(string $enrollmentId, string $userId, string $code): MfaEnrollmentModel
    {
        $enrollment = $this->get($enrollmentId);
        if (empty($enrollment) || $enrollment->user_id !== $userId) {
            throw new DomainException(ErrorDict::AUTH_FORBIDDEN);
        }
        if ($enrollment->status !== MfaEnrollmentModel::STATUS_PENDING) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'MFA 注册状态不可确认');
        }
        // TOTP secret 存储字段未冻结 → 校验 fail-closed
        throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'MFA secret 存储方案未冻结');
    }

    /**
     * 挑战（active 会话，校验 code）。
     *
     * Contract Gap：同 confirm，TOTP 校验不可用 → FAIL_CLOSED。
     *
     * @throws DomainException DEPENDENCY_UNAVAILABLE
     */
    public function challenge(string $userId, string $code): void
    {
        throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'MFA secret 存储方案未冻结');
    }

    /**
     * 停用/吊销（pending|active → revoked）。
     */
    public function disable(string $enrollmentId, string $userId): bool
    {
        $enrollment = $this->get($enrollmentId);
        if (empty($enrollment) || $enrollment->user_id !== $userId) {
            throw new DomainException(ErrorDict::AUTH_FORBIDDEN);
        }
        if ($enrollment->status === MfaEnrollmentModel::STATUS_REVOKED) {
            return true;
        }
        $enrollment->update(['status' => MfaEnrollmentModel::STATUS_REVOKED]);
        return true;
    }

    /**
     * 按用户查询注册（只读透传）。
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    /**
     * 用户是否存在 active MFA。
     */
    public function hasActive(string $userId): bool
    {
        $active = $this->getNewDao()->fetch([
            'user_id' => $userId,
            'status'  => MfaEnrollmentModel::STATUS_ACTIVE,
        ]);
        return !empty($active);
    }
}
