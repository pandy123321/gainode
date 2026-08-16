<?php

namespace app\api\controller;

use library\service\arbitrage\PositionService;
use library\service\arbitrage\ProjectOrderService;
use library\validator\arbitrage\PositionValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 套利交易管理
 */
class ArbitrageController extends Api
{
    public function __construct()
    {
        $this->service = new PositionService();
        $this->validation = new PositionValidation();
        parent::__construct();
    }

    /**
     * 套利交易记录
     * @method GET
     * @url /api/arbitrage/tradeLogs
     * @return Response
     */
    public function tradeLogs(): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $projectId = (int)$this->getParams('project_id',0);
            $phase = $this->getParams('phase');
            $page = (int)$this->getParams('page',1);
            $size = (int)$this->getParams('size',10);
            $start_date = $this->getParams('start_date');
            $end_date = $this->getParams('end_date');
            $projectOrderSvc = new ProjectOrderService();
            $result = $projectOrderSvc->getUserActiveProjectOrder((int)$userData['id'],(int)$projectId);
            if(empty($result)){
                return $this->json([],'暂无套利订单数据');
            }
            $params = [
                'project_id'=>$projectId,
                'page'=>$page,
                'size'=>$size
            ];
            if(!empty($phase)){
                $params['phase'] = $phase;
            }
            if(!empty($start_date)){
                $params['settled_at'] = ['gt',$start_date];
            }
            if(!empty($end_date)){
                $params['settled_at'] = ['lt',$end_date];
            }
            $data = $this->service->paginateArray($params);
            return $this->json($data);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 套利交易详情
     * @method GET
     * @url /api/arbitrage/tradeDetail/{id}
     * @return Response
     */
    public function tradeDetail(int $id): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $data = $this->service->get($id);
            return $this->json($data);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }
}
