<?php

namespace library\service\sys;

use library\model\sys\CasbinRbacModel;
use library\dao\sys\CasbinRbacDao;
use support\extend\Service;

/**
 * Service
 * @method CasbinRbacModel create($data)
 * @method CasbinRbacModel updateOrCreate(array $params,array $data)
 * @method CasbinRbacModel update($id,array $data){
 * @method CasbinRbacModel get($id,string $field = null)
 * @method CasbinRbacModel find($id)
 * @method CasbinRbacModel findOrFail($id)
 * @method CasbinRbacModel firstOrCreate(array $params,array $data)
 * @method CasbinRbacModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class CasbinRbacService extends Service
{
    public function __construct()
    {
        $this->dao = CasbinRbacDao::class;
        parent::__construct();
    }

    /**
     * 安全地重载服务（避免直接 exec）
     */
    private function safeReload(): void
    {
        // 使用 posix_kill 发送 SIGUSR1 信号实现平滑重载（Linux/macOS）
        // 如果不可用，记录日志由外部定时任务或守护进程重载
        if (function_exists('posix_kill')) {
            $pidFile = runtime_path() . '/webman.pid';
            if (file_exists($pidFile)) {
                $pid = (int) file_get_contents($pidFile);
                if ($pid > 0) {
                    posix_kill($pid, SIGUSR1);
                }
            }
        }
    }

    /**
     * 设置角色权限
     * @param $user_id
     * @param $route_ids
     * @return bool
     */
    public function saveUserGrant($user_id,$route_keys){
        $this->deleteAll(['ptype'=>'p','v0'=>'user'.$user_id]);
        $casbinList = [];
        $routeService = new RouteService();
        foreach($route_keys as $key){
            $route = $routeService->getRouteByKey($key);
            if(!empty($route) && $route['verify']>0){
                $casbinList[] = ['ptype'=>'p','v0'=>('user'.$user_id),'v1'=>$key,'v2'=>$route['method'],'created_time'=>getCurrentDate()];
            }
        }
        $res = $this->insert($casbinList);
        if($res){
            $this->safeReload();
        }
        return $res;
    }

    /**
     * 设置角色权限
     * @param $role_id
     * @param $route_ids
     * @return bool
     */
    public function saveRoleGrant($role_id,$route_keys){
        $this->deleteAll(['ptype'=>'p','v0'=>'role'.$role_id]);
        $casbinList = [];
        $routeService = new RouteService();
        foreach($route_keys as $key){
            $route = $routeService->getRouteByKey($key);
            if(!empty($route) && $route['verify']>0){
                $casbinList[] = ['ptype'=>'p','v0'=>('role'.$role_id),'v1'=>$key,'v2'=>$route['method'],'created_time'=>getCurrentDate()];
            }
        }
        $res = $this->insert($casbinList);
        if($res){
            $this->safeReload();
        }
        return $res;
    }

    /**
     * 设置用户角色
     * @param $userid
     * @param $role_id
     */
    public function setUserRole($userid,$role_id){
        $user = 'user'.$userid;
        $role = 'role'.$role_id;
        $this->deleteAll(['ptype'=>'g','v0'=>'user'.$userid]);
        $res = $this->create(['ptype'=>'g','v0'=>$user,'v1'=>$role]);
        if($res){
            $this->safeReload();
        }
        return $res;
    }
}
