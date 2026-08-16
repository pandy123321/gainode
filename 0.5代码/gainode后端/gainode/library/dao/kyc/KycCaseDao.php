<?php

declare(strict_types=1);

namespace library\dao\kyc;

use support\extend\Dao;
use library\model\kyc\KycCaseModel;

/**
 * KycCase DAO — kyc_cases 表查询封装
 */
class KycCaseDao extends Dao
{
    public function __construct()
    {
        $this->model = KycCaseModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return KycCaseModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }

    /**
     * 按用户查询 KYC 案件
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->fetchAll(['user_id' => $userId]);
    }
}
