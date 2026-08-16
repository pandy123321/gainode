<?php

declare(strict_types=1);

namespace library\dao\parameter;

use support\extend\Dao;
use library\model\parameter\ParameterReleaseModel;

/**
 * ParameterRelease DAO — parameter_releases 表查询封装
 */
class ParameterReleaseDao extends Dao
{
    public function __construct()
    {
        $this->model = ParameterReleaseModel::class;
    }

    /**
     * 按幂等键查询
     *
     * @param string $idempotencyKey
     * @return ParameterReleaseModel|null
     */
    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->fetch(['idempotency_key' => $idempotencyKey]);
    }
}
