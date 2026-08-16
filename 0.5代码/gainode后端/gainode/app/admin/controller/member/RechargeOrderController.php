<?php

namespace app\admin\controller\member;

use library\service\member\RechargeOrderService;
use library\service\member\UserService;
use library\validator\member\RechargeOrderValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;
use support\utils\Data;

/**
 * 充值订单管理
 */
class RechargeOrderController extends Admin
{
    public function __construct()
    {
        $this->service = new RechargeOrderService();
        $this->validation = new RechargeOrderValidation();
        parent::__construct();
    }

    /**
     * 充值订单列表
     * @param string $order_status 状态: submitted/confirming/completed/failed/rejected/closed
     * @param string $user_id 会员ID
     * @param string $network 充值网络: TRC20/ERC20/BEP20
     * @param string $address 充值地址
     * @param string $tx_hash 交易hash
     * @method GET
     * @url /admin/member/rechargeOrder
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
     * 充值订单详情
     * @method GET
     * @url /admin/member/rechargeOrder/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $rechargeOrderObj = $this->service->get($id);
            if(empty($rechargeOrderObj)){
                throw new VerifyException('执行失败');
            }
            $data = $rechargeOrderObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 审核充值订单
     * @method PUT
     * @url /admin/member/rechargeOrder/verify/{id}
     * @return Response
     */
    public function verify(int $id): Response
    {
        try {
            $order_status = $this->getPost('order_status');
            $descr = $this->getPost('descr');
            $tx_hash = $this->getPost('tx_hash');
            $res = $this->service->verifyOrder($id,$order_status,$descr,true,$tx_hash);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除充值订单
     * @method DELETE
     * @url /admin/member/rechargeOrder/{id}
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
