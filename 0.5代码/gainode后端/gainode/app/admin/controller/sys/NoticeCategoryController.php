<?php

namespace app\admin\controller\sys;

use library\service\sys\NoticeCategoryService;
use library\validator\sys\NoticeCategoryValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 通知分类管理
 */
class NoticeCategoryController extends Admin
{
    public function __construct()
    {
        $this->service = new NoticeCategoryService();
        $this->validation = new NoticeCategoryValidation();
        parent::__construct();
    }

    /**
     * 获取分类数据
     * @method GET
     * @url /admin/sys/noticeCategoryAll
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
     * 通知分类列表
     * @param string $name 分类名称
     * @method GET
     * @url /admin/sys/noticeCategory
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
     * 通知分类详情
     * @method GET
     * @url /admin/sys/noticeCategory/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $noticeCategoryObj = $this->service->get($id);
            if(empty($noticeCategoryObj)){
                throw new VerifyException('执行失败');
            }
            $data = $noticeCategoryObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加通知分类
     * @method POST
     * @url /admin/sys/noticeCategory
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $noticeCategoryObj = $this->service->create($post);
            if(empty($noticeCategoryObj)){
                throw new VerifyException('执行失败');
            }
            $data = $noticeCategoryObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改通知分类
     * @method PUT
     * @url /admin/sys/noticeCategory/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $noticeCategoryObj = $this->service->update($id,$post);
            if(empty($noticeCategoryObj)){
                throw new VerifyException('执行失败');
            }
            $data = $noticeCategoryObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置通知分类状态
     * @method PUT
     * @url /admin/sys/noticeCategory/setStatus/{id}
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
     * 删除通知分类
     * @method DELETE
     * @url /admin/sys/noticeCategory/{id}
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
