<?php

declare(strict_types=1);

namespace library\dao\notice;

use support\extend\Dao;
use library\model\notice\NoticeModel;

/**
 * Notice DAO — notices 表查询封装
 */
class NoticeDao extends Dao
{
    public function __construct()
    {
        $this->model = NoticeModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return NoticeModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按用户查询通知
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }
}
