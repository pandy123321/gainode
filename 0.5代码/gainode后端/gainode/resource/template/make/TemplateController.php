<?php

namespace app\from\controller\module;

use library\service\module\TemplateService;
use library\validator\module\TemplateValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 管理
 */
class TemplateController extends Api
{
    public function __construct()
    {
        $this->service = new TemplateService();
        $this->validation = new TemplateValidation();
        parent::__construct();
    }

    /**
     * 获取数据
     * @method GET
     * @url /from/urlPathAll
     * @return Response
     */
    public function all(): Response
    {
        try {
            $data = $this->service->fetchAll(['status'=>1]);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 列表
     * @method GET
     * @url /from/urlPath
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
     * 详情
     * @method GET
     * @url /from/urlPath/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $templateObj = $this->service->get($id);
            if(empty($templateObj)){
                throw new VerifyException('执行失败');
            }
            $data = $templateObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加
     * @method POST
     * @url /from/urlPath
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $templateObj = $this->service->create($post);
            if(empty($templateObj)){
                throw new VerifyException('执行失败');
            }
            $data = $templateObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改
     * @method PUT
     * @url /from/urlPath/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $templateObj = $this->service->update($id,$post);
            if(empty($templateObj)){
                throw new VerifyException('执行失败');
            }
            $data = $templateObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置状态
     * @method PUT
     * @url /from/urlPath/setStatus/{id}
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
     * 删除
     * @method DELETE
     * @url /from/urlPath/{id}
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

    /**
     * 批量删除
     * @method DELETE
     * @url /from/urlPath/deleteAll/{ids}
     * @return Response
     */
    public function deleteAll(string $ids): Response
    {
        try {
            $ids = str_replace('%2C',',',$ids);
            $params = ['id'=>['in',explode(',',$ids)]];
            $res = $this->service->deleteAll($params);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'删除成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
