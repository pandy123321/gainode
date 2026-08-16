<?php

namespace app\admin\controller\member;

use library\service\member\UserService;
use library\service\member\UserWalletService;
use library\service\member\WithdrawOrderService;
use library\validator\member\WithdrawOrderValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;
use support\utils\Data;

/**
 * 提现订单管理
 */
class WithdrawOrderController extends Admin
{
    public function __construct()
    {
        $this->service = new WithdrawOrderService();
        $this->validation = new WithdrawOrderValidation();
        parent::__construct();
    }

    /**
     * 提现订单列表
     * @method GET
     * @url /admin/member/withdrawOrder
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            if(!empty($params['order_status']) && $params['order_status'] == 'all') unset($params['order_status']);
            $data = $this->service->paginateArray($params);
            $data['report'] = $this->service->getGroupAllStatusCnt($params);
            $userService = new UserService();
            $ids = Data::toFlatArray($data['data'],'user_id');
            if(!empty($ids)){
                $userList = $userService->getUserFieldsByIds($ids,['user_no']);
                foreach($data['data'] as $k=>$v){
                    $data['data'][$k]['user_no'] = $userList[$v['user_id']]['user_no']??'';
                }
            }
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 提现订单详情
     * @method GET
     * @url /admin/member/withdrawOrder/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $withdrawOrderObj = $this->service->get($id);
            if(empty($withdrawOrderObj)){
                throw new VerifyException('执行失败');
            }
            $data = $withdrawOrderObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 审核提现订单
     * @method PUT
     * @url /admin/member/withdrawOrder/verify/{id}
     * @return Response
     */
    public function verify(int $id): Response
    {
        try {
            $order_status = $this->getPost('order_status');
            $descr = $this->getPost('descr');
            $res = $this->service->verifyOrder($id,$order_status,$descr);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除提现订单
     * @method DELETE
     * @url /admin/member/withdrawOrder/{id}
     * @return Response
     */
    public function delete(int $id): Response
    {
        try {
            $res = $this->service->delete($id);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'删除成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
