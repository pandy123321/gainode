<?php

namespace app\admin\controller;

use support\controller\Admin;
use support\Response;

/**
 * 控制台管理
 */
class AccountController extends Admin
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取用户信息
     * @method GET
     * @url /admin/account/getUserInfo
     */
    public function getUserInfo()
    {
        try{
            $userInfo = $this->request->getTokenUser();
            return $this->json($userInfo);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取工作台信息
     * @method GET
     * @url /admin/account/console
     * @return Response
     */
    public function console()
    {
        return $this->json([]);
    }

    /**
     * 数据分析
     * @method GET
     * @url /admin/account/analysis
     * @return Response
     */
    public function analysis()
    {
        return $this->json([]);
    }


}
