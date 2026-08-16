<?php

namespace library\dao\member;

use support\exception\VerifyException;
use support\extend\Dao;
use library\model\member\UserWalletModel;

class UserWalletDao extends Dao
{
    public function __construct()
    {
        $this->model = UserWalletModel::class;
    }

    /**
     * 获取用户钱包（默认加行锁）
     * @param int $user_id
     * @param string $wallet_type
     * @param bool $isThrow
     * @return UserWalletModel|null
     * @throws VerifyException
     */
    public function getUserWallet(int $user_id, string $wallet_type = 'Funding', bool $isThrow = true): ?UserWalletModel
    {
        $where = ['user_id' => $user_id, 'wallet_type' => $wallet_type];
        $walletObj = $this->selector($where)->lock(true)->first();
        if (empty($walletObj) && $isThrow) {
            throw new VerifyException('资产数据暂未找到');
        }
        return $walletObj;
    }
}
