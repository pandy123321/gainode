<?php

namespace library\service\member;

use library\model\member\LevelModel;
use library\dao\member\LevelDao;
use support\extend\Service;

/**
 * Service
 * @method LevelModel create($data)
 * @method LevelModel updateOrCreate(array $params,array $data)
 * @method LevelModel update($id,array $data){
 * @method LevelModel get($id,string $field = null)
 * @method LevelModel find($id)
 * @method LevelModel findOrFail($id)
 * @method LevelModel firstOrCreate(array $params,array $data)
 * @method LevelModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class LevelService extends Service
{
    public function __construct()
    {
        $this->dao = LevelDao::class;
        parent::__construct();
    }

    public function getSelectList($user_type=null){
        $field = ['id','name','grade','discount','amount','invite_cnt','descr'];
        return $this->fetchAll(['user_type'=>$user_type,'status'=>1],['grade'=>'asc'],$field);
    }
}
