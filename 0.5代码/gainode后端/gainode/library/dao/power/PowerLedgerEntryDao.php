<?php

declare(strict_types=1);

namespace library\dao\power;

use support\extend\Dao;
use support\exception\RunException;
use library\model\power\PowerLedgerEntryModel;

/**
 * PowerLedgerEntry DAO — power_ledger_entries 表查询封装（append-only）
 *
 * 对标 AptLedgerEntryDao：append-only 表禁止物理删除/覆盖。本 DAO 对继承的
 * delete/deleteAll/update/updateAll/updateOrCreate 全部 fail-closed 覆写，
 * 从代码层面机械阻断 DAO 层的删除/覆盖路径。仅保留只读查询与追加（create/insert）。
 */
class PowerLedgerEntryDao extends Dao
{
    public function __construct()
    {
        $this->model = PowerLedgerEntryModel::class;
    }

    /**
     * 按用户查询分录（按创建时间倒序）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId], ['created_time' => 'desc']);
    }

    /**
     * 按幂等键查询（写操作去重）
     *
     * @param string $idempotencyKey
     * @return PowerLedgerEntryModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按来源对象查询分录
     *
     * @param string $sourceObjectType
     * @param string $sourceObjectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBySource(string $sourceObjectType, string $sourceObjectId)
    {
        return $this->fetchAll(['source_object_type' => $sourceObjectType, 'source_object_id' => $sourceObjectId]);
    }

    /** @throws RunException */
    public function delete($id, bool $force = false)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止删除分录');
    }

    /** @throws RunException */
    public function deleteAll(array $params, bool $force = false)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止批量删除分录');
    }

    /** @throws RunException */
    public function update($id, array $data)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 UPDATE 分录，更正请追加 reversal 分录');
    }

    /** @throws RunException */
    public function updateAll(array $params, array $data)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止批量 UPDATE 分录');
    }

    /** @throws RunException */
    public function updateOrCreate(array $params, array $data)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 updateOrCreate，更正请追加 reversal 分录');
    }
}
