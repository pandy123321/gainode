<?php

declare(strict_types=1);

namespace library\dao\ledger;

use support\extend\Dao;
use library\model\ledger\AptAccountModel;

/**
 * AptAccount DAO — apt_accounts 表查询封装
 */
class AptAccountDao extends Dao
{
    public function __construct()
    {
        $this->model = AptAccountModel::class;
    }

    /**
     * 按用户查询主账号（一用户一账号，uk_user 唯一）
     *
     * @param string $userId
     * @return AptAccountModel|null
     */
    public function getByUser(string $userId)
    {
        return $this->fetch(['user_id' => $userId]);
    }
}
