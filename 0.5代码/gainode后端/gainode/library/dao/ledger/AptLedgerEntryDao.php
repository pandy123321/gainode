<?php

declare(strict_types=1);

namespace library\dao\ledger;

use support\extend\Dao;
use support\exception\RunException;
use library\model\ledger\AptLedgerEntryModel;

/**
 * AptLedgerEntry DAO — apt_ledger_entries 表查询封装（append-only）
 *
 * 注意：append-only 表禁止物理删除。本 DAO 对继承的 delete/deleteAll/update/updateAll/
 * updateOrCreate 全部 fail-closed 覆写，从代码层面机械阻断任何删除/覆盖路径，
 * 而非仅靠调用方自觉。仅保留只读查询与追加（create/insert）。
 */
class AptLedgerEntryDao extends Dao
{
    public function __construct()
    {
        $this->model = AptLedgerEntryModel::class;
    }

    /**
     * 按账号查询分录（按创建时间倒序）
     *
     * @param string $accountId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByAccount(string $accountId)
    {
        return $this->fetchAll(['account_id' => $accountId], ['created_time' => 'desc']);
    }

    /**
     * 按幂等键查询（写操作去重）
     *
     * @param string $idempotencyKey
     * @return AptLedgerEntryModel|null
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

    /**
     * append-only 账本：禁止删除单条分录。
     *
     * @throws RunException
     */
    public function delete($id, bool $force = false)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止删除分录');
    }

    /**
     * append-only 账本：禁止批量删除分录。
     *
     * @throws RunException
     */
    public function deleteAll(array $params, bool $force = false)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止批量删除分录');
    }

    /**
     * append-only 账本：禁止 UPDATE 已落盘分录。
     *
     * @throws RunException
     */
    public function update($id, array $data)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 UPDATE 分录，更正请追加 reversal 分录');
    }

    /**
     * append-only 账本：禁止批量 UPDATE 分录。
     *
     * @throws RunException
     */
    public function updateAll(array $params, array $data)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止批量 UPDATE 分录');
    }

    /**
     * append-only 账本：禁止 updateOrCreate（可能覆盖已落盘分录）。
     *
     * @throws RunException
     */
    public function updateOrCreate(array $params, array $data)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 updateOrCreate，更正请追加 reversal 分录');
    }
}
