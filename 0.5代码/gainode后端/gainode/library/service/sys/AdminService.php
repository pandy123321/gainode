<?php

namespace library\service\sys;

use library\model\sys\AdminModel;
use library\dao\sys\AdminDao;
use support\exception\VerifyException;
use support\extend\Cache;
use support\extend\Service;
use support\utils\Data;
use support\utils\Random;
use Workerman\Coroutine\Context;

/**
 * Service
 * @method AdminModel create($data)
 * @method AdminModel updateOrCreate(array $params,array $data)
 * @method AdminModel update($id,array $data){
 * @method AdminModel get($id,string $field = null)
 * @method AdminModel find($id)
 * @method AdminModel findOrFail($id)
 * @method AdminModel firstOrCreate(array $params,array $data)
 * @method AdminModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method AdminModel getUserByAccount($account)
 */
class AdminService extends Service
{
    public function __construct()
    {
        $this->dao = AdminDao::class;
        parent::__construct();
    }

    public function getUserById($userid,$clearCache=false){
        $key = 'model_admin_'.$userid;
        $data = Cache::get($key);
        if(empty($data) || $clearCache){
            $userObj = $this->get($userid);
            $data = $userObj->toM();
            Cache::set($key,$data,3600*24);
        }
        return $data;
    }

    /**
     * 创建用户
     * @param $data {account,password,role_id,dept_id,name,email,mobile,photo,descr}
     */
    public function createUser($data){
        $data['eid'] = getEid();
        $data['encrypt'] = Random::getRandStr(7,6);
        $data['password'] = hashPassword($data['password'],$data['encrypt']);
        $adminObj = $this->create($data);
        if(!empty($adminObj) && !empty($data['role_id'])){
            $rbacService = new CasbinRbacService();
            $rbacService->setUserRole($adminObj['id'],$data['role_id']);
        }
        return $adminObj;
    }

    public function updateUser($id, array $data)
    {
        $adminObj = $this->get($id);
        $old_role_id = $adminObj['role_id'];
        $res = $adminObj->saveData($data);
        if(!empty($res) && !empty($data['role_id']) && $old_role_id!=$data['role_id']){
            $rbacService = new CasbinRbacService();
            $rbacService->setUserRole($id,$data['role_id']);
        }
        return $adminObj;
    }

    /**
     * 重制用户密码
     * @param $account
     */
    public function resetUserPassword($account){
        $userObj = $this->getUserByAccount($account);
        if(empty($userObj)){
            throw new VerifyException('账号不存在');
        }
        elseif($userObj['status']!=1){
            throw new VerifyException('账号已被锁定');
        }
        $new_password = Random::getPwdRandom(6);
        $passpwd= hashPassword($new_password,$userObj->encrypt);
        $data = [
            'password'=>$passpwd,
            'modify_pwd_time'=>getCurrentDate(),
            'pwd_strong'=>0
        ];
        $res = $userObj->saveData($data);
        if(!empty($res)){
            return $new_password;
        }
        return false;
    }

    /**
     * 修改用户密码
     * @param int $userid  用户ID
     * @param string $new_password 新密码
     * @param string $old_password 旧密码
     * @return bool
     */
    public function modifyPassword(int $userid,string $new_password,string $old_password = null,int $pwd_strong=0):bool {
        $userObj = $this->get($userid);
        if (empty($userObj)) {
            throw new VerifyException('账号不存在');
        }
        elseif(!empty($old_password) && hashPassword($old_password,$userObj->encrypt)!=$userObj->password){
            throw new VerifyException('你输入的密码错误');
        }
        $passpwd= hashPassword($new_password,$userObj->encrypt);
        $data = [
            'password'=>$passpwd,
            'modify_pwd_time'=>getCurrentDate(),
        ];
        if(!empty($pwd_strong)){
            $data['pwd_strong'] =$pwd_strong;
        }
        return $userObj->saveData($data);
    }

    /**
     * 修改用户密码
     * @param array $ids 用户ID
     * @param string $password 密码
     * @return bool
     */
    public function modifyUsersPassword(array $ids,string $password){
        $rows = $this->fetchAll(['id'=>['in',$ids]]);
        foreach($rows as $obj){
            $passpwd = hashPassword($password,$obj->encrypt);
            $obj->update(['password'=>$passpwd,'modify_pwd_time'=>getCurrentDate()]);
        }
        return true;
    }

    /**
     * 保存用户权限
     * @param int $userid
     * @param array $menu_ids
     */
    public function saveAdminMenusGrant(int $userid,array $menu_ids){
        $adminObj = $this->get($userid);
        $roleService = new RoleService();
        $roleObj = $roleService->get($adminObj['role_id']);
        $role_menu_ids = [];
        if(!empty($roleObj['menu_ids'])){
            $role_menu_ids = json_decode($roleObj['menu_ids'],true);
        }
        $user_menu_ids = array_diff($menu_ids,$role_menu_ids);
        $res = $this->update($userid,['menu_ids'=>implode(',',$menu_ids)]);
        if($res){
            $menuService = new MenusService();
            $route_keys = $menuService->pluck('route_key',['id'=>['in',$user_menu_ids]]);
            $route_keys = array_filter($route_keys);
            $rbacService = new CasbinRbacService();
            return $rbacService->saveUserGrant($userid,$route_keys);
        }
        return false;
    }
}
