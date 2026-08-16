<?php

namespace library\service\sys;

use library\model\sys\RoleModel;
use library\dao\sys\RoleDao;
use support\extend\Service;
use support\utils\Data;

/**
 * Service
 * @method RoleModel create($data)
 * @method RoleModel updateOrCreate(array $params,array $data)
 * @method RoleModel update($id,array $data){
 * @method RoleModel get($id,string $field = null)
 * @method RoleModel find($id)
 * @method RoleModel findOrFail($id)
 * @method RoleModel firstOrCreate(array $params,array $data)
 * @method RoleModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class RoleService extends Service
{
    public function __construct()
    {
        $this->dao = RoleDao::class;
        parent::__construct();
    }

    /**
     * 获取可用的角色
     * @param null $ids
     * @param string $cell
     * @return array
     */
    public function getSelectList($parent_id=null,$type=null){
        $params = [];
        if(!is_null($parent_id)){
            $params['pid'] = $parent_id;
        }
        $rows = $this->fetchAll($params,[],['id','name','pid'])->toArray();
        if($type=='tree'){
            Data::$zoomAry = [];
            return Data::getArrayZoomList($rows,'name','id');
        }
        else{
            $data = [];
            foreach($rows as $v){
                $data[$v['id']] = $v;
            }
            return $data;
        }
    }

    /**
     * 根据ID获取所有的名称
     * @param $role_ids
     */
    public function getRoleNameByIds(array $role_ids){
        $data = $this->fetchAll(['id'=>['in',$role_ids]],[],['id','name'])->toArray();
        return Data::toKVArray($data,'id','name');
    }

    /**
     * 保存角色权限
     * @param int $role_id
     * @param array $menu_ids
     */
    public function saveRoleMenus(int $role_id,array $menu_ids){
        $res = $this->update($role_id,['menu_ids'=>implode(',',$menu_ids)]);
        if($res){
            $menuService = new MenusService();
            $route_keys = $menuService->pluck('route_key',['id'=>['in',$menu_ids]]);
            $route_keys = array_filter($route_keys);
            $rbacService = new CasbinRbacService();
            return $rbacService->saveRoleGrant($role_id,$route_keys);
        }
        return false;
    }
}
