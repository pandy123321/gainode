<?php

declare(strict_types=1);

namespace library\dao\policy;

use support\extend\Dao;
use library\model\policy\ConsentReceiptModel;

/**
 * ConsentReceipt DAO — consent_receipts 表查询封装
 */
class ConsentReceiptDao extends Dao
{
    public function __construct()
    {
        $this->model = ConsentReceiptModel::class;
    }

    /**
     * 按用户查询回执
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }

    /**
     * 按幂等键查询（consent_type + consent_version 去重）
     *
     * @param string $idempotencyKey
     * @return ConsentReceiptModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
