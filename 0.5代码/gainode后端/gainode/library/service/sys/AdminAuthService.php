<?php

namespace library\service\sys;

use library\model\sys\AdminAuthModel;
use library\dao\sys\AdminAuthDao;
use support\extend\Service;

/**
 * Service
 * @method AdminAuthModel create($data)
 * @method AdminAuthModel updateOrCreate(array $params,array $data)
 * @method AdminAuthModel update($id,array $data){
 * @method AdminAuthModel get($id,string $field = null)
 * @method AdminAuthModel find($id)
 * @method AdminAuthModel findOrFail($id)
 * @method AdminAuthModel firstOrCreate(array $params,array $data)
 * @method AdminAuthModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class AdminAuthService extends Service
{
    public function __construct()
    {
        $this->dao = AdminAuthDao::class;
        parent::__construct();
    }

    public function getUserLoginAuth($token){
        return $this->fetch(['access_token'=>$token,'expired_time'=>['gt',time()],'status'=>1]);
    }

    public function getUserTerminalAuth($admin_id,$terminal=null){
        return $this->fetch([
            'admin_id'=>$admin_id,
            'terminal'=>$terminal
        ]);
    }

    public function getUserAuthList($admin_id){
        $rows = $this->fetchAll(['admin_id'=>$admin_id]);
        $data = [];
        foreach($rows as $v){
            $data[$v['terminal']] = $v;
        }
        return $data;
    }
}
