<?php

namespace app\admin\controller\arbitrage;

use library\service\arbitrage\ProjectOrderService;
use library\service\member\UserService;
use library\validator\arbitrage\ProjectOrderValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;
use support\utils\Data;

/**
 * 矿机订单管理
 */
class ProjectOrderController extends Admin
{
    public function __construct()
    {
        $this->service = new ProjectOrderService();
        $this->validation = new ProjectOrderValidation();
        parent::__construct();
    }

    /**
     * 矿机订单列表
     * @param integer $user_id 会员ID
     * @param integer $project_id 矿机ID
     * @param string $project_name 矿机名字
     * @method GET
     * @url /admin/arbitrage/projectOrder
     * @return Response
     */
    public function list(): Response
    {
        $params = $this->getAllRequest();
        if(!empty($params['project_name'])){
            $params['project_name'] = ['like',$params['project_name']];
        }
        $data = $this->service->paginateArray($params);
        $userService = new UserService();
        $ids = Data::toFlatArray($data['data'],'user_id');
        if(!empty($ids)){
            $userList = $userService->getUserFieldsByIds($ids,['user_no']);
            foreach($data['data'] as $k=>$v){
                $data['data'][$k]['user_no'] = $userList[$v['user_id']]['user_no']??'';
            }
        }
        return $this->json($data);
    }

    /**
     * 矿机订单详情
     * @method GET
     * @url /admin/arbitrage/projectOrder/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $projectOrderObj = $this->service->get($id);
            if(empty($projectOrderObj)){
                throw new VerifyException('执行失败');
            }
            $data = $projectOrderObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }


    /**
     * 删除矿机订单
     * @method DELETE
     * @url /admin/arbitrage/projectOrder/{id}
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
