<?php

namespace app\admin\controller\arbitrage;

use library\service\arbitrage\ProjectService;
use library\validator\arbitrage\ProjectValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 矿机项目管理
 */
class ProjectController extends Admin
{
    public function __construct()
    {
        $this->service = new ProjectService();
        $this->validation = new ProjectValidation();
        parent::__construct();
    }

    /**
     * 矿机项目列表
     * @method GET
     * @param string $name 项目名称
     * @url /admin/arbitrage/project
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
     * 矿机项目详情
     * @method GET
     * @url /admin/arbitrage/project/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $projectObj = $this->service->get($id);
            if(empty($projectObj)){
                throw new VerifyException('执行失败');
            }
            $data = $projectObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加矿机项目
     * @method POST
     * @url /admin/arbitrage/project
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            if(empty($post['start_date'])){
                $post['start_date'] = null;
            }
            $projectObj = $this->service->create($post);
            if(empty($projectObj)){
                throw new VerifyException('执行失败');
            }
            $data = $projectObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改矿机项目
     * @method PUT
     * @url /admin/arbitrage/project/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            if(empty($post['start_date'])){
                $post['start_date'] = null;
            }
            $projectObj = $this->service->update($id,$post);
            if(empty($projectObj)){
                throw new VerifyException('执行失败');
            }
            $data = $projectObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置矿机项目状态
     * @method PUT
     * @url /admin/arbitrage/project/setStatus/{id}
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
     * 删除矿机项目
     * @method DELETE
     * @url /admin/arbitrage/project/{id}
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
