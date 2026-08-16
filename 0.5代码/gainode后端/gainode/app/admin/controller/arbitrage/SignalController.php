<?php

namespace app\admin\controller\arbitrage;

use library\service\arbitrage\SignalService;
use library\validator\arbitrage\SignalValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 信号管理
 */
class SignalController extends Api
{
    public function __construct()
    {
        $this->service = new SignalService();
        $this->validation = new SignalValidation();
        parent::__construct();
    }

    /**
     * 信号列表
     * @param string $event_name 赛事名称
     * @method GET
     * @url /admin/arbitrage/signal
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
     * 信号详情
     * @method GET
     * @url /admin/arbitrage/signal/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $signalObj = $this->service->get($id);
            if(empty($signalObj)){
                throw new VerifyException('执行失败');
            }
            $data = $signalObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
