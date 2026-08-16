<?php

namespace app\admin\controller\sys;

use library\model\sys\AdminModel;
use library\service\sys\AdminService;
use library\service\sys\TableFieldService;
use library\validator\sys\AdminValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 后台账号管理
 */
class AdminController extends Admin
{
    public function __construct()
    {
        $this->service = new AdminService();
        $this->validation = new AdminValidation();
        parent::__construct();
    }

    /**
     * 后台账号列表
     * @param string account 账号
     * @param int dept_id 所属部门
     * @param int role_id 所属角色
     * @method GET
     * @url /admin/sys/admin
     * @return Response
     */
    public function list(): Response
    {
        try {
            $tableFieldService = new TableFieldService();
            $config = $tableFieldService->getSearchListData('sys_admin');
            if(!empty($config)){
                $params = $this->createSearchListArray($config['query'],$config['where']);
                $sort = $this->getSortArray('sort');
                $fields = $config['list']??[];
                $data = $this->service->paginateArray($params,$sort,$fields);
            }
            else{
                $params = $this->getAllRequest();
                $data = $this->service->paginateArray($params);
            }
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 后台账号详情
     * @method GET
     * @url /admin/sys/admin/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $adminObj = $this->service->get($id);
            if(empty($adminObj)){
                throw new VerifyException('执行失败');
            }
            $data = $adminObj->toArray();
            $data['role'] = $adminObj->role()->get(['id','name'])->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 后台账号添加
     * @method POST
     * @url /admin/sys/admin
     * @return AdminModel
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $adminObj = $this->service->createUser($post);
            if(empty($adminObj)){
                throw new VerifyException('执行失败');
            }
            $data = $adminObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 后台账号修改
     * @method PUT
     * @url /admin/sys/admin/{id}
     * @return AdminModel
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $adminObj = $this->service->updateUser($id,$post);
            if(empty($adminObj)){
                throw new VerifyException('执行失败');
            }
            $data = $adminObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置后台账号状态
     * @method PUT
     * @url /admin/sys/admin/setStatus/{id}
     * @return Response
     */
    public function setStatus(int $id): Response
    {
        try {
            $status = $this->getPost('status');
            $res = $this->service->update($id,['status'=>$status]);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改后台账号密码
     * @method PUT
     * @url /admin/sys/admin/modifyPassword/{id}
     * @return Response
     */
    public function modifyPassword(int $id): Response
    {
        try {
            $new_password = $this->getPost('new_password');
            $old_password = $this->getPost('old_password');
            $res = $this->service->modifyPassword($id,$new_password,$old_password);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }



    /**
     * 设置后台账号菜单权限
     * @method PUT
     * @url /admin/sys/admin/setMenuIds/{id}
     * @return Response
     */
    public function setMenuIds(int $id): Response
    {
        try {
            $menu_ids = $this->getPost('menu_ids');
            if(empty($menu_ids)){
                throw new VerifyException('请选择菜单');
            }
            elseif(!is_array($menu_ids)){
                $menu_ids = explode(',',$menu_ids);
            }
            $res = $this->service->saveAdminMenusGrant($id,$menu_ids);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 后台账号删除
     * @method DELETE
     * @url /admin/sys/admin/{id}
     * @return Response
     */
    public function delete(int $id): Response
    {
        try {
            $res = $this->service->delete($id);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'删除成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
