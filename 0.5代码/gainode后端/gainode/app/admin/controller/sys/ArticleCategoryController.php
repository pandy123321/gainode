<?php

namespace app\admin\controller\sys;

use library\service\sys\ArticleCategoryService;
use library\validator\sys\ArticleCategoryValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 内容分类管理
 */
class ArticleCategoryController extends Admin
{
    public function __construct()
    {
        $this->service = new ArticleCategoryService();
        $this->validation = new ArticleCategoryValidation();
        parent::__construct();
    }

    /**
     * 获取内容分类数据
     * @method GET
     * @url /admin/sys/articleCategoryAll
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
     * 内容分类列表
     * @param string $name 分类名称
     * @method GET
     * @url /admin/sys/articleCategory
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
     * 内容分类详情
     * @method GET
     * @url /admin/sys/articleCategory/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $articleCategoryObj = $this->service->get($id);
            if(empty($articleCategoryObj)){
                throw new VerifyException('执行失败');
            }
            $data = $articleCategoryObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加内容分类
     * @method POST
     * @url /admin/sys/articleCategory
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $articleCategoryObj = $this->service->create($post);
            if(empty($articleCategoryObj)){
                throw new VerifyException('执行失败');
            }
            $data = $articleCategoryObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改内容分类
     * @method PUT
     * @url /admin/sys/articleCategory/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $articleCategoryObj = $this->service->update($id,$post);
            if(empty($articleCategoryObj)){
                throw new VerifyException('执行失败');
            }
            $data = $articleCategoryObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置内容分类状态
     * @method PUT
     * @url /admin/sys/articleCategory/setStatus/{id}
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
     * 删除内容分类
     * @method DELETE
     * @url /admin/sys/articleCategory/{id}
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
