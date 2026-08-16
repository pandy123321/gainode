<?php

namespace app\admin\controller\sys;

use library\service\sys\DictListService;
use library\service\sys\DictService;
use library\validator\sys\DictValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 字典管理
 */
class DictController extends Admin
{
    public function __construct()
    {
        $this->service = new DictService();
        $this->validation = new DictValidation();
        parent::__construct();
    }

    /**
     * 字典群组列表
     * @param int $type 类型 {0:系统配置,1:资金配置,2:套利配置,3:存储配置,4:支付配置,5:其他配置}
     * @method GET
     * @url /admin/sys/dictGroup/{type}
     * @return Response
     */
    public function getDictList(int $type): Response {
        try {
            $data = $this->service->getDictListForType($type);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 保存字典组数据
     * @param array $data 健值对数据,键为字典编码，值为字典值
     * @method PUT
     * @url /admin/sys/dictGroup/{code}
     * @return Response
     */
    public function saveDictList(string $code){
        try {
            $post = $this->getPost('data');
            $dictListService = new DictListService();
            $res = $dictListService->saveDictListValue($code,$post);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'保存成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 字典列表
     * @param string $name 字典名称
     * @param string $code 字典编码
     * @method GET
     * @url /admin/sys/dict
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
     * 字典详情
     * @method GET
     * @url /admin/sys/dict/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $dictObj = $this->service->get($id);
            if(empty($dictObj)){
                throw new VerifyException('执行失败');
            }
            $data = $dictObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加字典
     * @method POST
     * @url /admin/sys/dict
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $dictObj = $this->service->create($post);
            if(empty($dictObj)){
                throw new VerifyException('执行失败');
            }
            $data = $dictObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改字典
     * @method PUT
     * @url /admin/sys/dict/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $dictObj = $this->service->update($id,$post);
            if(empty($dictObj)){
                throw new VerifyException('执行失败');
            }
            $data = $dictObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置字典状态
     * @method PUT
     * @url /admin/sys/dict/setStatus/{id}
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
     * 删除字典
     * @method DELETE
     * @url /admin/sys/dict/{id}
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
