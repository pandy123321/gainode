<?php

namespace app\admin\controller\sys;

use library\service\sys\NoticeService;
use library\validator\sys\NoticeValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 通知管理
 */
class NoticeController extends Admin
{
    public function __construct()
    {
        $this->service = new NoticeService();
        $this->validation = new NoticeValidation();
        parent::__construct();
    }

    /**
     * 通知列表
     * @param string $title 标题
     * @param integer $category_id 分类ID
     * @method GET
     * @url /admin/sys/notice
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
     * 通知详情
     * @method GET
     * @url /admin/sys/notice/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $noticeObj = $this->service->get($id);
            if(empty($noticeObj)){
                throw new VerifyException('执行失败');
            }
            $data = $noticeObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加通知
     * @method POST
     * @url /admin/sys/notice
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $noticeObj = $this->service->create($post);
            if(empty($noticeObj)){
                throw new VerifyException('执行失败');
            }
            $data = $noticeObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改通知
     * @method PUT
     * @url /admin/sys/notice/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $noticeObj = $this->service->update($id,$post);
            if(empty($noticeObj)){
                throw new VerifyException('执行失败');
            }
            $data = $noticeObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置通知状态
     * @method PUT
     * @url /admin/sys/notice/setStatus/{id}
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
     * 删除通知
     * @method DELETE
     * @url /admin/sys/notice/{id}
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
     * 批量删除通知
     * @method DELETE
     * @url /admin/sys/notice/deleteAll/{ids}
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
