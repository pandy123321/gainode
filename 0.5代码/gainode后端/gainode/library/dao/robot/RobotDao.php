<?php

declare(strict_types=1);

namespace library\dao\robot;

use support\extend\Dao;
use library\model\robot\RobotModel;

/**
 * Robot DAO — robots 表查询封装
 */
class RobotDao extends Dao
{
    public function __construct()
    {
        $this->model = RobotModel::class;
    }

    /**
     * 按用户查询 Robot（一用户多 Robot）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }

    /**
     * 按幂等键查询（创建去重）
     *
     * @param string $idempotencyKey
     * @return RobotModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
