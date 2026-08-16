<?php

namespace app\admin\controller\sys;

use library\service\sys\LangService;
use library\validator\sys\LangValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 语言管理
 */
class LangController extends Admin
{
    public function __construct()
    {
        $this->service = new LangService();
        $this->validation = new LangValidation();
        parent::__construct();
    }

    /**
     * 语言列表
     * @param string $name 名称
     * @param string $code 编码
     * @method GET
     * @url /admin/sys/lang
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
     * 语言详情
     * @method GET
     * @url /admin/sys/lang/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $langObj = $this->service->get($id);
            if(empty($langObj)){
                throw new VerifyException('执行失败');
            }
            $data = $langObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加语言
     * @method POST
     * @url /admin/sys/lang
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $langObj = $this->service->create($post);
            if(empty($langObj)){
                throw new VerifyException('执行失败');
            }
            $data = $langObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改语言
     * @method PUT
     * @url /admin/sys/lang/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $langObj = $this->service->update($id,$post);
            if(empty($langObj)){
                throw new VerifyException('执行失败');
            }
            $data = $langObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置语言状态
     * @method PUT
     * @url /admin/sys/lang/setStatus/{id}
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
     * 删除语言
     * @method DELETE
     * @url /admin/sys/lang/{id}
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
