<?php

namespace app\admin\controller\sys;

use library\service\sys\DictListService;
use library\validator\sys\DictListValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 字典数据管理
 */
class DictListController extends Admin
{
    public function __construct()
    {
        $this->service = new DictListService();
        $this->validation = new DictListValidation();
        parent::__construct();
    }

    /**
     * 字典数据列表
     * @param string $dict_code 字典编码
     * @method GET
     * @url /admin/sys/dictList
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
     * 字典数据详情
     * @method GET
     * @url /admin/sys/dictList/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $dictListObj = $this->service->get($id);
            if(empty($dictListObj)){
                throw new VerifyException('执行失败');
            }
            $data = $dictListObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加字典数据
     * @method POST
     * @url /admin/sys/dictList
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $dictListObj = $this->service->create($post);
            if(empty($dictListObj)){
                throw new VerifyException('执行失败');
            }
            $data = $dictListObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改字典数据
     * @method PUT
     * @url /admin/sys/dictList/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $dictListObj = $this->service->update($id,$post);
            if(empty($dictListObj)){
                throw new VerifyException('执行失败');
            }
            $data = $dictListObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置字典数据状态
     * @method PUT
     * @url /admin/sys/dictList/setStatus/{id}
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
     * 删除字典数据
     * @method DELETE
     * @url /admin/sys/dictList/{id}
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
     * 批量删除字典数据
     * @method DELETE
     * @url /admin/sys/dictList/deleteAll/{ids}
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
