<?php

namespace app\api\controller;

use library\service\member\WithdrawOrderService;
use library\service\sys\DictService;
use library\validator\member\WithdrawOrderValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 提现管理
 */
class WithdrawController extends Api
{
    public function __construct()
    {
        $this->service = new WithdrawOrderService();
        $this->validation = new WithdrawOrderValidation();
        parent::__construct();
    }

    /**
     * 获取提现配置
     * @method GET
     * @url /api/withdraw/config
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
            $data = $dictService->getDictConfigs('withdraw');
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 提现订单列表
     * @method GET
     * @url /api/withdraw/lists
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

    /**
     * 创建提现订单
     * @method POST
     * @url /api/withdraw/create
     * @return Response
     */
    public function create(): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $post = $this->getPost();
            $data = $this->service->createOrder($userData['id'],$post);
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
