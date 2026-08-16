<?php

namespace app\admin\controller\sys;

use library\service\sys\DeptService;
use library\service\sys\TableFieldService;
use library\validator\sys\DeptValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 部门管理
 */
class DeptController extends Admin
{
    public function __construct()
    {
        $this->service = new DeptService();
        $this->validation = new DeptValidation();
        parent::__construct();
    }

    /**
     * 获取部门数据
     * @method GET
     * @url /admin/sys/deptAll
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
     * 部门列表
     * @method GET
     * @url /admin/sys/dept
     * @return Response
     */
    public function list(): Response
    {
        try {
            $tableFieldService = new TableFieldService();
            $config = $tableFieldService->getSearchListData('sys_dept');
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
     * 部门详情
     * @method GET
     * @url /admin/sys/dept/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $deptObj = $this->service->get($id);
            if(empty($deptObj)){
                throw new VerifyException('执行失败');
            }
            $data = $deptObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加部门
     * @method POST
     * @url /admin/sys/dept
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $deptObj = $this->service->create($post);
            if(empty($deptObj)){
                throw new VerifyException('执行失败');
            }
            $data = $deptObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改部门
     * @method PUT
     * @url /admin/sys/dept/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $deptObj = $this->service->update($id,$post);
            if(empty($deptObj)){
                throw new VerifyException('执行失败');
            }
            $data = $deptObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置部门状态
     * @method PUT
     * @url /admin/sys/dept/setStatus/{id}
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
     * 删除部门
     * @method DELETE
     * @url /admin/sys/dept/{id}
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
