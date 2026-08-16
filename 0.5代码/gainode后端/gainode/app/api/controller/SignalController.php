<?php

namespace app\api\controller;

use library\service\arbitrage\SignalService;
use library\validator\arbitrage\SignalValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 套利信号管理
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
     * 信号数据
     * @method GET
     * @url /api/signal/list
     * @return Response
     */
    public function list(){
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $page = (int)$this->getParams('page',1);
            $size = (int)$this->getParams('size',10);
            $rows = $this->service->paginateArray([
                'page'=>$page,
                'size'=>$size
            ],['id'=>'desc']);
            return $this->json($rows);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 套利信号详情
     * @method GET
     * @url /api/signal/detail/{id}
     * @return Response
     */
    public function detail(int $id): Response
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
