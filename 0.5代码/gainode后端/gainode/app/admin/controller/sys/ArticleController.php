<?php

namespace app\admin\controller\sys;

use library\service\sys\ArticleService;
use library\validator\sys\ArticleValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 内容管理
 */
class ArticleController extends Admin
{
    public function __construct()
    {
        $this->service = new ArticleService();
        $this->validation = new ArticleValidation();
        parent::__construct();
    }

    /**
     * 内容列表
     * @param string $title 标题
     * @param int $category_id 分类ID
     * @method GET
     * @url /admin/sys/article
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
     * 内容详情
     * @method GET
     * @url /admin/sys/article/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $articleObj = $this->service->get($id);
            if(empty($articleObj)){
                throw new VerifyException('执行失败');
            }
            $data = $articleObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加内容
     * @method POST
     * @url /admin/sys/article
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $articleObj = $this->service->create($post);
            if(empty($articleObj)){
                throw new VerifyException('执行失败');
            }
            $data = $articleObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改内容
     * @method PUT
     * @url /admin/sys/article/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $articleObj = $this->service->update($id,$post);
            if(empty($articleObj)){
                throw new VerifyException('执行失败');
            }
            $data = $articleObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置内容状态
     * @method PUT
     * @url /admin/sys/article/setStatus/{id}
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
     * 删除内容
     * @method DELETE
     * @url /admin/sys/article/{id}
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
