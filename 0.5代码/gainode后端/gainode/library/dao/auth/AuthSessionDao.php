<?php

declare(strict_types=1);

namespace library\dao\auth;

use support\extend\Dao;
use library\model\auth\AuthSessionModel;

/**
 * AuthSession DAO — auth_sessions 表查询封装
 */
class AuthSessionDao extends Dao
{
    public function __construct()
    {
        $this->model = AuthSessionModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return AuthSessionModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按用户查询会话
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }
}
