<?php

namespace library\service\sys;

use library\dao\sys\AdminDao;
use library\dao\sys\RoleDao;
use library\dao\sys\RouteDao;
use library\model\sys\MenusModel;
use library\dao\sys\MenusDao;
use support\extend\Service;
use support\utils\Data;

/**
 * Service
 * @method MenusModel create($data)
 * @method MenusModel updateOrCreate(array $params,array $data)
 * @method MenusModel update($id,array $data){
 * @method MenusModel get($id,string $field = null)
 * @method MenusModel find($id)
 * @method MenusModel findOrFail($id)
 * @method MenusModel firstOrCreate(array $params,array $data)
 * @method MenusModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class MenusService extends Service
{
    public function __construct()
    {
        $this->dao = MenusDao::class;
        parent::__construct();
    }

    /**
     * 检测方法是否在数据库存在
     * @param string $url
     * @return boolean
     */
    public function checkUrlIsExists($url) {
        $result = $this->get(route_key($url),'route_key');
        return empty($result) ? false : true;
    }

    public function createMenu($data){
        $obj = $this->create($data);
        if(!empty($obj) && !empty($obj->route_key)){
            $routeService = new RouteDao();
            $routeService->updateAll(['key'=>$obj->route_key,'status'=>0],['status'=>1]);
        }
        return $obj;
    }

    public function updateMenu($id,$data){
        $obj = $this->get($id);
        if(empty($obj)){
            throw new \Exception('菜单不存在');
        }
        elseif(!empty($data['route_key']) && $obj->route_key!=$data['route_key']){
            $routeService = new RouteDao();
            $routeService->updateAll(['key'=>$obj->route_key,'status'=>1],['status'=>0]);
            $routeService->updateAll(['key'=>$data['route_key'],'status'=>0],['status'=>1]);
        }
        return $this->update($id,$data);
    }

    public function getSelectList(){
        return $this->fetchAll([],['id'=>'asc'],['id','name','pid']);
    }

    public function getParentList($type=1){
        $params = ['status'=>1,'type'=>$type];
        if($type>1){
            $params['type'] = ['lt',$type];
        }
        $rows = $this->fetchAll($params,['pid'=>'asc','sort'=>'asc'],['id','name','pid'])->toArray();
        Data::$zoomAry = [];
        return Data::getArrayZoomList($rows,'name','id');
    }

    /**
     * 获取我的菜单树
     * @param $user_id
     * @see {['id','pid','name','icon','route_url','children']}
     * @return void
     */
    public function getUserTreeMenus($user_id){
        $userService = new AdminService();
        $adminObj = $userService->get($user_id);
        $params = ['status'=>1,'is_show'=>1,'platform'=>'system'];
        if($adminObj->is_admin!=1){
            $user_menu_ids = [];
            if(!empty($adminObj->menu_ids)){
                $user_menu_ids = explode(',',$adminObj->menu_ids);
            }
            if(!empty($adminObj->role_id)){
                $role_menu_ids = $adminObj->role->menu_ids;
                if(!empty($role_menu_ids)){
                    $role_menu_ids = explode(',',$role_menu_ids);
                    $user_menu_ids = array_merge($user_menu_ids,$role_menu_ids);
                }
            }
            if(!empty($user_menu_ids)){
                $params['id'] = ['in',$user_menu_ids];
            }
            else{
                $params['id'] = 0;
            }
        }
        $rows = $this->fetchAll($params,['sort'=>'asc'],['id','pid','name','icon','route_url']);
        return Data::getArrayTreeList($rows->toArray());
    }
}
