<?php

namespace app\api\controller;

use library\service\arbitrage\ProjectOrderService;
use library\service\arbitrage\ProjectService;
use library\validator\arbitrage\ProjectValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 矿机项目管理
 */
class ProjectController extends Api
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
     * @url /api/project/list
     * @return Response
     */
    public function list(): Response
    {
        try {
            $userData = $this->request->getTokenUser(false);
            $params = $this->getAllRequest();
            $data = $this->service->paginateArray($params);
            $projectOrderSvc = new ProjectOrderService();
            foreach($data['data'] as $k=>$v){
                if(empty($userData)){
                    $data['data'][$k]['can_buy'] = false;
                }
                else{
                    $res = $projectOrderSvc->verifyUserCanBuyProject($userData['id'],$v['id']);
                    $data['data'][$k]['can_buy'] = (!empty($res)?true:false);
                }
            }
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 验证能否购买矿机
     * @method GET
     * @url /api/project/verify/{id}
     * @return Response
     */
    public function verify(int $id): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $projectOrderSvc = new ProjectOrderService();
            $res = $projectOrderSvc->verifyUserCanBuyProject($userData['id'],$id);
            return $this->json($res);
        }
        catch (\Throwable $e) {
            return $this->failJson([],$e->getMessage(),(int)$e->getCode());
        }
    }

    /**
     * 矿机项目详情
     * @method GET
     * @url /api/project/detail/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $projectObj = $this->service->get($id);
            if(empty($projectObj)){
                throw new VerifyException('矿机项目不存在');
            }
            $data = $projectObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
