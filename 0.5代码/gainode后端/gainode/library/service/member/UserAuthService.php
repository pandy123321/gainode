<?php

namespace library\service\member;

use library\model\member\UserAuthModel;
use library\dao\member\UserAuthDao;
use library\model\member\UserModel;
use support\extend\Service;

/**
 * Service
 * @method UserAuthModel create($data)
 * @method UserAuthModel updateOrCreate(array $params,array $data)
 * @method UserAuthModel update($id,array $data){
 * @method UserAuthModel get($id,string $field = null)
 * @method UserAuthModel find($id)
 * @method UserAuthModel findOrFail($id)
 * @method UserAuthModel firstOrCreate(array $params,array $data)
 * @method UserAuthModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class UserAuthService extends Service
{
    public function __construct()
    {
        $this->dao = UserAuthDao::class;
        parent::__construct();
    }

    public function getUserLoginAuth($token){
        return $this->fetch(['access_token'=>$token,'expired_time'=>['gt',time()],'status'=>1]);
    }

    public function getUserTerminalAuth($user_id,$terminal=null){
        return $this->fetch([
            'user_id'=>$user_id,
            'terminal'=>$terminal
        ]);
    }

    public function getUserAuthList($user_id){
        $rows = $this->fetchAll(['user_id'=>$user_id]);
        $data = [];
        foreach($rows as $v){
            $data[$v['terminal']] = $v;
        }
        return $data;
    }
}
