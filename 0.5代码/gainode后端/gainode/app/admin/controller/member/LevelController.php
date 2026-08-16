<?php

namespace app\admin\controller\member;

use library\service\member\LevelService;
use library\validator\member\LevelValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 等级管理
 */
class LevelController extends Admin
{
    public function __construct()
    {
        $this->service = new LevelService();
        $this->validation = new LevelValidation();
        parent::__construct();
    }

    /**
     * 获取等级数据
     * @param int $type 类型(0:会员等级 1:代理等级)
     * @method GET
     * @url /admin/member/levelAll
     * @return Response
     */
    public function all(int $type): Response
    {
        try {
            $data = $this->service->getSelectList($type);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 等级列表
     * @method GET
     * @url /admin/member/level
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
     * 等级详情
     * @method GET
     * @url /admin/member/level/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $levelObj = $this->service->get($id);
            if(empty($levelObj)){
                throw new VerifyException('执行失败');
            }
            $data = $levelObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加等级
     * @method POST
     * @url /admin/member/level
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $levelObj = $this->service->create($post);
            if(empty($levelObj)){
                throw new VerifyException('执行失败');
            }
            $data = $levelObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改等级
     * @method PUT
     * @url /admin/member/level/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $levelObj = $this->service->update($id,$post);
            if(empty($levelObj)){
                throw new VerifyException('执行失败');
            }
            $data = $levelObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置等级状态
     * @method PUT
     * @url /admin/member/level/setStatus/{id}
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
     * 删除等级
     * @method DELETE
     * @url /admin/member/level/{id}
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
