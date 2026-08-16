<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\Web3NetworkWalletModel;

class Web3NetworkWalletDao extends Dao
{
    public function __construct()
    {
        $this->model = Web3NetworkWalletModel::class;
    }
}
