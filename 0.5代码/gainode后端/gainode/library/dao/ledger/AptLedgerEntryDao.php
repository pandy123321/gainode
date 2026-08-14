<?php

declare(strict_types=1);

namespace library\dao\ledger;

use support\extend\Dao;
use library\model\ledger\AptLedgerEntryModel;

/**
 * AptLedgerEntry DAO — apt_ledger_entries 表查询封装（append-only）
 *
 * 注意：append-only 表禁止物理删除。本 DAO 继承的 delete/deleteAll 不得用于账本分录。
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
}
