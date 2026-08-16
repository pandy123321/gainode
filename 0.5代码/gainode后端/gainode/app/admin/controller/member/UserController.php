<?php

namespace app\admin\controller\member;

use library\service\member\UserService;
use library\service\member\UserWalletService;
use library\validator\member\UserValidation;
use support\controller\Admin;
use support\exception\VerifyException;
use support\Response;

/**
 * 用户管理
 */
class UserController extends Admin
{
    public function __construct()
    {
        $this->service = new UserService();
        $this->validation = new UserValidation();
        parent::__construct();
    }

    /**
     * 用户列表
     * @param int $id  用户ID
     * @param string $user_no 用户编号
     * @param int $level_id 会员等级ID
     * @param int $is_arbitrage 是否开启套利(0:否,1:是)
     * @method GET
     * @url /admin/member/user
     * @return Response
     */
    public function list(): Response
    {
        try {
            $params = $this->getAllRequest();
            $data = $this->service->paginateArray($params);
            $walletService = new UserWalletService();
            foreach($data['data'] as $k=>$v){
                $data['data'][$k]['wallet'] = $walletService->getWalletList($v['id']);
            }
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 用户详情
     * @method GET
     * @url /admin/member/user/{id}
     * @return Response
     */
    public function detail(int $id): Response
    {
        try {
            $userObj = $this->service->get($id);
            if(empty($userObj)){
                throw new VerifyException('执行失败');
            }
            $data = $userObj->toArray();
            return $this->json($data);
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加用户
     * @method POST
     * @url /admin/member/user
     * @return Response
     */
    public function add(): Response
    {
        try {
            $post = $this->getPost();
            $userObj = $this->service->createUser($post);
            if(empty($userObj)){
                throw new VerifyException('执行失败');
            }
            $data = $userObj->toArray();
            return $this->json($data,'添加成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改用户
     * @method PUT
     * @url /admin/member/user/{id}
     * @return Response
     */
    public function update(int $id): Response
    {
        try {
            $post = $this->getPost();
            $userObj = $this->service->updateUser($id,$post);
            if(empty($userObj)){
                throw new VerifyException('执行失败');
            }
            $data = $userObj->toArray();
            return $this->json($data,'修改成功');
        }
        catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置用户状态
     * @method PUT
     * @url /admin/member/user/setStatus/{id}
     * @return Response
     */
    public function setStatus(int $id): Response
    {
        try {
            $status = $this->getPost('status');
            $res = $this->service->update($id,['status'=>$status]);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置用户备注
     * @method PUT
     * @url /admin/member/user/setRemark/{id}
     * @return Response
     */
    public function setRemark(int $id): Response
    {
        try {
            $remark = $this->getPost('remark');
            $res = $this->service->update($id,['descr'=>$remark]);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 添加用户余额
     * @method POST
     * @url /admin/member/user/addMoney
     * @return Response
     */
    public function addMoney(): Response
    {
        try {
            $id = $this->getPost('id');
            $money = $this->getPost('money');
            $remark = $this->getPost('remark');
            $is_show = $this->getPost('is_show',1);
            $walletService = new UserWalletService();
            if($money>0){
                $res = $walletService->addUserWallet($id,'Funding',$money,UserWalletService::EVENT_ACCOUNT_ADJUSTED,$remark,'',0,$this->request->getUserID(),($is_show?0:1));
            }
            else{
                $res = $walletService->minusUserWallet($id,'Funding',abs($money),UserWalletService::EVENT_ACCOUNT_ADJUSTED,$remark,'',0,$this->request->getUserID(),($is_show?0:1));
            }
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'操作成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 删除用户
     * @method DELETE
     * @url /admin/member/user/{id}
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
