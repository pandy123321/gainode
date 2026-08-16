<?php

declare(strict_types=1);

namespace library\dao\support;

use support\extend\Dao;
use support\exception\RunException;
use library\model\support\TicketMessageModel;

/**
 * TicketMessage DAO — ticket_messages 表查询封装（append-only）
 *
 * 注意：append-only 值对象禁止物理删除/覆盖。本 DAO 对继承的 delete/deleteAll/update/
 * updateAll/updateOrCreate 全部 fail-closed 覆写，从代码层面机械阻断 DAO 层的删除/覆盖路径。
 * 仅保留只读查询与追加（create/insert）。
 */
class TicketMessageDao extends Dao
{
    public function __construct()
    {
        $this->model = TicketMessageModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return TicketMessageModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按工单查询消息
     *
     * @param string $ticketId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByTicket(string $ticketId)
    {
        return $this->fetchAll(['ticket_id' => $ticketId]);
    }

    /**
     * append-only 值对象：禁止删除单条。
     *
     * @throws RunException
     */
    public function delete($id, bool $force = false)
    {
        throw new RunException('ticket_messages 为 append-only 值对象：禁止删除消息');
    }

    /**
     * append-only 值对象：禁止批量删除。
     *
     * @throws RunException
     */
    public function deleteAll(array $params, bool $force = false)
    {
        throw new RunException('ticket_messages 为 append-only 值对象：禁止批量删除消息');
    }

    /**
     * append-only 值对象：禁止 UPDATE。
     *
     * @throws RunException
     */
    public function update($id, array $data)
    {
        throw new RunException('ticket_messages 为 append-only 值对象：禁止 UPDATE 消息');
    }

    /**
     * append-only 值对象：禁止批量 UPDATE。
     *
     * @throws RunException
     */
    public function updateAll(array $params, array $data)
    {
        throw new RunException('ticket_messages 为 append-only 值对象：禁止批量 UPDATE 消息');
    }

    /**
     * append-only 值对象：禁止 updateOrCreate。
     *
     * @throws RunException
     */
    public function updateOrCreate(array $params, array $data)
    {
        throw new RunException('ticket_messages 为 append-only 值对象：禁止 updateOrCreate');
    }
}
