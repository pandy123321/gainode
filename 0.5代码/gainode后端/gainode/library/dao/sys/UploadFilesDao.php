<?php

namespace library\dao\sys;

use support\extend\Dao;
use library\model\sys\UploadFilesModel;

class UploadFilesDao extends Dao
{
    public function __construct()
    {
        $this->model = UploadFilesModel::class;
    }
}
