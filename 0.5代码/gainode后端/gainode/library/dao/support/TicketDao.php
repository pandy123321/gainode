<?php

declare(strict_types=1);

namespace library\dao\support;

use support\extend\Dao;
use library\model\support\TicketModel;

/**
 * Ticket DAO — tickets 表查询封装
 */
class TicketDao extends Dao
{
    public function __construct()
    {
        $this->model = TicketModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return TicketModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按用户查询工单
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }
}
