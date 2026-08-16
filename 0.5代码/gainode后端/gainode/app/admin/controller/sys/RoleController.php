<?php

namespace app\admin\controller\sys;

use library\service\sys\RoleService;
use library\service\sys\TableFieldService;
use library\validator\sys\RoleValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 角色管理
 */
class RoleController extends Admin
{
    public function __construct()
    {
        $this->service = new RoleService();
        $this->validation = new RoleValidation();
        parent::__construct();
    }

    /**
     * 获取角色数据
     * @method GET
     * @url /admin/sys/roleAll
     * @return Response
     */
    public function all(): Response
    {
        try {
            $data = $this->service->getSelectList();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 角色列表
     * @method GET
     * @url /admin/sys/role
     * @return Response
     */
    public function list(): Response
    {
        try {
            $tableFieldService = new TableFieldService();
            $config = $tableFieldService->getSearchListData('sys_role');
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
     * 角色详情
     * @method GET
     * @url /admin/sys/role/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $roleObj = $this->service->get($id);
            if(empty($roleObj)){
                throw new VerifyException('执行失败');
            }
            $data = $roleObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加角色
     * @method POST
     * @url /admin/sys/role
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $roleObj = $this->service->create($post);
            if(empty($roleObj)){
                throw new VerifyException('执行失败');
            }
            $data = $roleObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改角色
     * @method PUT
     * @url /admin/sys/role/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $roleObj = $this->service->update($id,$post);
            if(empty($roleObj)){
                throw new VerifyException('执行失败');
            }
            $data = $roleObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置角色状态
     * @method PUT
     * @url /admin/sys/role/setStatus/{id}
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
     * 设置角色菜单权限
     * @method PUT
     * @url /admin/sys/role/setMenuIds/{id}
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
            $res = $this->service->saveRoleMenus($id,$menu_ids);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除角色
     * @method DELETE
     * @url /admin/sys/role/{id}
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
