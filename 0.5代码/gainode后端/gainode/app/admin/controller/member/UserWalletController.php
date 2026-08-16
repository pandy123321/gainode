<?php

namespace app\admin\controller\member;

use library\service\member\UserWalletService;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 钱包账户管理
 */
class UserWalletController extends Api
{
    public function __construct()
    {
        $this->service = new UserWalletService();
        parent::__construct();
    }

    /**
     * 钱包账户列表
     * @param int $user_id
     * @param string $user_no
     * @param string $wallet_type
     * @method GET
     * @url /admin/member/userWallet
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
     * 钱包账户详情
     * @method GET
     * @url /admin/member/userWallet/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $userWalletObj = $this->service->get($id);
            if(empty($userWalletObj)){
                throw new VerifyException('执行失败');
            }
            $data = $userWalletObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
