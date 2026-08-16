<?php

namespace app\api\controller;

use library\service\member\UserService;
use library\service\member\UserTeamService;
use library\service\member\UserWalletService;
use library\validator\member\UserTeamValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;
use support\utils\Data;

/**
 * 团队管理
 */
class TeamController extends Api
{
    public function __construct()
    {
        $this->service = new UserTeamService();
        $this->validation = new UserTeamValidation();
        parent::__construct();
    }

    /**
     * 获取我的团队数据
     * @method GET
     * @url /api/team/detail
     * @return Response
     */
    public function detail(): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $data = $this->service->get($userData['id']);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取我的团队列表
     * @param int $type 类型(0:团队,1:直推,2:间推)
     * @method GET
     * @url /api/team/list
     * @return Response
     */
    public function list(int $type): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $params = $this->getAllRequest();
            if($type==1){
                $params['parent_id'] = $userData['id'];
            }
            elseif($type==2){
                $teamObj = $this->service->get($userData['id']);
                $params['parent_path'] = ['like_left',$teamObj->parent_path.','];
                $params['parent_level'] = ($teamObj->parent_level+2);
            }
            else{
                $teamObj = $this->service->get($userData['id']);
                $params['parent_path'] = ['like_left',$teamObj->parent_path.','];
            }
            $rows = $this->service->paginateArray($params,['user_id'=>'asc']);
            $userService = new UserService();
            $ids = Data::toFlatArray($rows['data'],'user_id');
            if(!empty($ids)){
                $userList = $userService->getUserFieldsByIds($ids,['user_no','is_arbitrage','is_verify']);
                $walletService =new UserWalletService();
                $walletList = $walletService->getUserArbitrageMoneyByIds($ids);
                foreach($rows['data'] as $k=>$v){
                    $rows['data'][$k]['user_no'] = $userList[$v['user_id']]['user_no']??'';
                    $rows['data'][$k]['is_arbitrage'] = $userList[$v['user_id']]['is_arbitrage']??0;
                    $rows['data'][$k]['is_verify'] = $userList[$v['user_id']]['is_verify']??0;
                    $rows['data'][$k]['arbitrage_balance'] = $walletList[$v['user_id']]??0;
                }
            }
            return $this->json($rows);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
