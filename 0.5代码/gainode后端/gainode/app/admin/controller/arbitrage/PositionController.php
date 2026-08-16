<?php

namespace app\admin\controller\arbitrage;

use library\service\arbitrage\PositionService;
use library\validator\arbitrage\PositionValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 套利交易管理
 */
class PositionController extends Api
{
    public function __construct()
    {
        $this->service = new PositionService();
        $this->validation = new PositionValidation();
        parent::__construct();
    }

    /**
     * 套利交易列表
     * @param string $event_name 赛事名称
     * @param integer $phase 资金阶段: 1=开仓锁仓中 2=赛果待结算(已完赛待入账) 3=已结算入账 4=已作废回滚
     * @method GET
     * @url /admin/arbitrage/position
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
     * 套利交易详情
     * @method GET
     * @url /admin/arbitrage/position/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $positionObj = $this->service->get($id);
            if(empty($positionObj)){
                throw new VerifyException('执行失败');
            }
            $data = $positionObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
