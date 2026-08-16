<?php

namespace app\admin\controller\sys;

use library\service\sys\MenusService;
use library\validator\sys\MenusValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 菜单管理
 */
class MenusController extends Admin
{
    public function __construct()
    {
        $this->service = new MenusService();
        $this->validation = new MenusValidation();
        parent::__construct();
    }

    /**
     * 获取菜单所有数据
     * @method GET
     * @url /admin/sys/menusAll
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
     * 获取用户的菜单权限
     * @method GET
     * @url /admin/sys/userTreeMenus
     * @return Response
     */
    public function tree(): Response{
        try {
            $user_id = $this->request->getUserID();
            $data = $this->service->getUserTreeMenus($user_id);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取上级菜单数据
     * @param integer $type 模块类型(1:目录,2:菜单,3:按钮)
     * @method GET
     * @url /admin/sys/menusParent
     * @return Response
     */
    public function parent(){
        try {
            $pid = $this->getParams('type',1);
            $data = $this->service->getParentList($pid);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 菜单列表
     * @param integer $type 模块类型(1:目录,2:菜单,3:按钮,4:接口)
     * @param integer $pid 父级菜单ID
     * @method GET
     * @url /admin/sys/menus
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            $data = $this->service->paginateArray($params);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 菜单详情
     * @method GET
     * @url /admin/sys/menus/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $menusObj = $this->service->get($id);
            if(empty($menusObj)){
                throw new VerifyException('执行失败');
            }
            $data = $menusObj->toArray();
            if(!empty($menusObj->route_key)){
                $data['route'] = $menusObj->route;
            }
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加菜单
     * @method POST
     * @url /admin/sys/menus
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $menusObj = $this->service->createMenu($post);
            if(empty($menusObj)){
                throw new VerifyException('执行失败');
            }
            $data = $menusObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改菜单
     * @method PUT
     * @url /admin/sys/menus/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $menusObj = $this->service->updateMenu($id,$post);
            if(empty($menusObj)){
                throw new VerifyException('执行失败');
            }
            $data = $menusObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置菜单状态
     * @method PUT
     * @url /admin/sys/menus/setStatus/{id}
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
     * 删除菜单
     * @method DELETE
     * @url /admin/sys/menus/{id}
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
