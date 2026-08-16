<?php

namespace library\service\sys;

use library\model\sys\RouteModel;
use library\dao\sys\RouteDao;
use support\extend\Cache;
use support\extend\Service;
use support\Request;

/**
 * Service
 * @method RouteModel create($data)
 * @method RouteModel updateOrCreate(array $params,array $data)
 * @method RouteModel update($id,array $data){
 * @method RouteModel get($id,string $field = null)
 * @method RouteModel find($id)
 * @method RouteModel findOrFail($id)
 * @method RouteModel firstOrCreate(array $params,array $data)
 * @method RouteModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class RouteService extends Service
{
    public function __construct()
    {
        $this->dao = RouteDao::class;
        parent::__construct();
    }

    public function getSelectList(array $params=[]){
        return $this->fetchAll($params,[],['method','key','url','path']);
    }

    public function getRouteObj($module,$controller,$action){
        return $this->fetch(['module'=>$module,'controller'=>$controller,'action'=>$action]);
    }
    public function getRouteByKey($key,$ttl=3600){
        $cache_key = 'route_'.$key;
        $data = Cache::get($cache_key);
        if(is_null($data)){
            $obj = $this->fetch(['key'=>$key],[],['id','module','controller','action','method','url','middleware','verify','descr','status']);
            $data = empty($obj)?[]:$obj->toArray();
            Cache::set($cache_key,$data,$ttl);
        }
        return $data;
    }

    public function getRouteVerify($key,$ttl=3600){
        $data = $this->getRouteByKey($key,$ttl);
        if(!empty($data)){
            return $data['verify'];
        }
        return -1;
    }

    public function getNotJoinRouteList(){
        return $this->fetchAll(['status'=>0,'verify'=>['gt',0]])->toArray();
    }
}
