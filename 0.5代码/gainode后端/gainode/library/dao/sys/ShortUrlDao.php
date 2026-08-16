<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\ShortUrlModel;

class ShortUrlDao extends Dao
{
    public function __construct()
    {
        $this->model = ShortUrlModel::class;
    }
}
