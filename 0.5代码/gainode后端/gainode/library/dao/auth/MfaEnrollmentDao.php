<?php

declare(strict_types=1);

namespace library\dao\auth;

use support\extend\Dao;
use library\model\auth\MfaEnrollmentModel;

/**
 * MfaEnrollment DAO — mfa_enrollments 表查询封装
 */
class MfaEnrollmentDao extends Dao
{
    public function __construct()
    {
        $this->model = MfaEnrollmentModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return MfaEnrollmentModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按用户查询 MFA 注册
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }
}
