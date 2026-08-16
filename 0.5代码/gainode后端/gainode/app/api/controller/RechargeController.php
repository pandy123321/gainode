<?php

namespace app\api\controller;

use library\service\member\RechargeOrderService;
use library\service\sys\DictService;
use library\validator\member\RechargeOrderValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 充值管理
 */
class RechargeController extends Api
{
    public function __construct()
    {
        $this->service = new RechargeOrderService();
        $this->validation = new RechargeOrderValidation();
        parent::__construct();
    }

    /**
     * 获取充值配置
     * @method GET
     * @url /api/recharge/config
     * @return Response
     */
    public function config(): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $dictService = new DictService();
            $data = $dictService->getDictConfigs('recharge');
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 充值订单列表
     * @method GET
     * @url /api/recharge/lists
     * @return Response
     */
    public function lists(): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $params = $this->getAllRequest();
            $params['user_id'] = $userData['id'];
            $data = $this->service->paginateArray($params);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
