<?php

namespace app\api\controller;

use library\service\arbitrage\PositionService;
use library\service\arbitrage\ProjectOrderLogsService;
use library\service\arbitrage\ProjectOrderService;
use library\service\member\UserService;
use library\validator\arbitrage\ProjectOrderValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;
use support\utils\Data;

/**
 * 矿机订单管理
 */
class ProjectOrderController extends Api
{
    public function __construct()
    {
        $this->service = new ProjectOrderService();
        $this->validation = new ProjectOrderValidation();
        parent::__construct();
    }

    /**
     * 购买矿机
     * @method POST
     * @url /api/projectOrder/create
     * @return Response
     */
    public function create(): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $project_id = (int)$this->getPost('project_id',0);
            $data = $this->service->createOrder((int)$userData['id'],$project_id);
            return $this->json($data);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 矿机订单列表
     * @param string $order_status 订单状态(paid:运行中,completed:已完成)
     * @method GET
     * @url /api/projectOrder/list
     * @return Response
     */
    public function list(): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $params = $this->getAllRequest();
            $data = $this->service->getUserProjectOrderList((int)$userData['id'],$params);
            $orderLogSvc = new ProjectOrderLogsService();
            foreach ($data['data'] as &$item){
                $item['incomeMoney'] = $orderLogSvc->getGroupUserOrderIncomeMoney($userData['id'],$item['id']);
            }
            return $this->json($data);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 获取我买的矿机ID
     * @param string $order_status 订单状态(paid:运行中,completed:已完成)
     * @method GET
     * @url /api/projectOrder/productIds
     * @return Response
     */
    public function projectIds():Response {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $order_status = $this->getParams('order_status');
            $params = ['user_id'=>$userData['id']];
            if(!empty($params) && in_array($order_status,['paid','completed'])){
                $params['order_status'] = $order_status;
            }
            $data = $this->service->fetchAll($params,['id'=>'asc'],['id','project_id','status']);
            return $this->json($data->toArray());
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 矿机订单详情
     * @method GET
     * @url /api/projectOrder/detail/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $projectOrderObj = $this->service->getUserProjectOrderDetail($id,(int)$userData['id']);
            if(empty($projectOrderObj)){
                throw new VerifyException('暂无订单数据');
            }
            $data = $projectOrderObj->toArray();
            return $this->json($data);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 矿机订单收益记录
     * @param int $project_id 矿机ID
     * @param int $status 状态(状态(0:待执行,1:待领取,2已结算)
     * @param string $level 来源 (0:自己,1:直推,2:间推,3:间间推)
     * @method GET
     * @url /api/projectOrder/getIncomeLogs
     * @responseField int $order_id 订单ID
     * @responseField int $project_id 矿机ID
     * @responseField int $user_id 购买人用户ID
     * @responseField int $plan_id 套利计划ID
     * @responseField int $position_id 套利仓位ID
     * @responseField int $level 分销级别(0:自己,1:直推,2:间推,3:间间推)
     * @responseField int $to_day 第几天收益
     * @responseField float $money 计算的金额
     * @responseField float $income_rate 收益率
     * @responseField int $income_userid 收益人ID
     * @responseField date $income_day 收益日期
     * @responseField float $income_amount 收益金额
     * @responseField string $created_time 申请时间
     * @return Response
     */
    public function getIncomeLogs(): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $project_id = (int)$this->getParams('project_id',0);
            $level = (int)$this->getParams('level',0);
            $status = $this->getParams('status');
            $page = (int)$this->getParams('page',1);
            $size = (int)$this->getParams('size',10);
            $params = [
                'income_userid'=>$userData['id'],
                'level'=>$level,
                'page'=>$page,
                'size'=>$size
            ];
            if(is_numeric($status)){
                $params['status'] = $status;
            }
            if(!empty($project_id)){
                $params['project_id'] = $project_id;
            }
            $projectOrderLogSvc = new ProjectOrderLogsService();
            $data = $projectOrderLogSvc->paginateArray($params,['id'=>'desc']);
            $ids = Data::toFlatArray($data['data'],'user_id');
            $userService = new UserService();
            if(!empty($ids)){
                $userList = $userService->getUserFieldsByIds($ids,['account','user_no']);
                foreach($data['data'] as $k=>$v){
                    $data['data'][$k]['account'] = $userList[$v['user_id']]['account']??'';
                    $data['data'][$k]['user_no'] = $userList[$v['user_id']]['user_no']??'';
                }
            }
            return $this->json($data);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 设置默认矿机订单
     * @method PUT
     * @url /api/projectOrder/setDefaultOrder/{id}
     * @return Response
     */
    public function setDefaultOrder(int $id){
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $res = $this->service->setDefaultOrder($id,(int)$userData['id']);
            return $this->json($res);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 领取矿机收益
     * @param int $order_id 矿机订单ID
     * @method POST
     * @url /api/projectOrder/receive
     * @return Response
     */
    public function receive(int $order_id): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $projectOrderLogSvc = new ProjectOrderLogsService();
            $data = $projectOrderLogSvc->receive((int)$userData['id'],$order_id);
            return $this->json($data);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }
}
