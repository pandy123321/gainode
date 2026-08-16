<?php

namespace app\api\controller;

use library\service\member\RedPacketItemService;
use library\service\member\RedPacketService;
use library\service\member\UserKycService;
use library\service\member\UserService;
use library\service\member\UserWalletLogService;
use library\service\member\UserWalletService;
use library\service\sys\Web3NetworkService;
use library\service\sys\Web3NetworkTokenService;
use library\service\sys\Web3NetworkWalletService;
use library\validator\member\UserValidation;
use support\controller\Api;
use support\exception\VerifyException;
use support\Response;

/**
 * 账户管理
 */
class AccountController extends Api
{
    public function __construct()
    {
        $this->service = new UserService();
        $this->validation = new UserValidation();
        parent::__construct();
    }

    /**
     * 获取我的用户信息
     * @method GET
     * @url /api/account/getUserInfo
     * @return Response
     */
    public function getUserInfo(): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            return $this->json($userData);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取我的钱包数据
     * @method GET
     * @url /api/account/getWalletList
     * @responseField string $wallet_type 钱包类型
     * @responseField float $balance 账户余额
     * @responseField float $frozen 冻结金额
     * @responseField float $total_in 累计收入
     * @responseField float $total_out 累计支出
     * @responseField float $available 可用金额
     * @responseField float $receipt_amount 代入帐金额
     * @return Response
     */
    public function getWalletList(): Response{
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $walletService = new UserWalletService();
            $data = $walletService->getWalletList($userData['id']);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取我的钱包流水日志
     * @param string $wallet_type 钱包类型(Funding:现金,Arbitrage:套利金额,Integral:积分)
     * @method GET
     * @url /api/account/getWalletLogs
     * @responseField string $wallet_type 钱包类型
     * @responseField string $event_type 事件类型
     * @responseField int $direction 类型(1=收入 -1=支出 0=冻结变动)
     * @responseField float $amount 金额
     * @responseField float $balance_after 账户余额
     * @responseField float $frozen_after 冻结金额
     * @responseField string $remark 备注
     * @responseField string $created_time 创建时间
     * @return Response
     */
    public function getWalletLogs(): Response{
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $wallet_type = $this->getParams('wallet_type');
            $walletLogService = new UserWalletLogService();
            $params = ['user_id'=>$userData['id']];
            if(!empty($wallet_type)){
                $params['wallet_type'] = $wallet_type;
            }
            $fields = ['wallet_type','event_type','direction','amount','balance_after','frozen_after','remark','created_time'];
            $data = $walletLogService->paginateArray($params,['id'=>'desc'],$fields);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改账号密码
     * @method PUT
     * @url /api/account/modifyPassword
     * @return Response
     */
    public function modifyPassword(): Response
    {
        try {
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $new_password = $this->getPost('new_password');
            $old_password = $this->getPost('old_password');
            $userObj = $this->service->get($userData['id']);
            $res = $this->service->modifyPassword($userObj,$new_password,$old_password);
            if(empty($res)){
                throw new VerifyException('执行失败');
            }
            return $this->json([],'设置成功');
        } catch (\Exception $e) {
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 领取红包
     * @method POST
     * @url /api/account/receivePacket
     * @responseField string $item_no 红包编号
     * @responseField string $amount 金额
     * @responseField string $status 状态(0未领取、1已领取)
     * @responseField string $receive_user_id 领取人
     * @responseField string $receive_time 领取时间
     * @return Response
     */
    public function receivePacket(): Response{
        try{
            $item_no = $this->getPost('packet_item_no');
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $packetService = new RedPacketService();
            $data = $packetService->receivePacket($userData['id'],$item_no);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取我领取红包
     * @method POST
     * @url /api/account/getMyPackets
     * @responseField string $item_no 红包编号
     * @responseField string $amount 金额
     * @responseField string $status 状态(0未领取、1已领取)
     * @responseField string $receive_user_id 领取人ID
     * @responseField string $receive_user_name 领取人
     * @responseField string $receive_time 领取时间
     * @return Response
     */
    public function getMyPackets(): Response{
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $packetItemService = new RedPacketItemService();
            $data = $packetItemService->getUserPacketItems($userData['id']);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 设置套利开启状态
     * @method PUT
     * @url /api/account/setArbitrageStatus
     * @return Response
     */
    public function setArbitrageStatus(): Response{
        try{
            $status = $this->getPost('is_arbitrage',0);
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $userService = new UserService();
            $data = $userService->setUserArbitrageStatus($userData['id'],$status);
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取链路的币
     * @method GET
     * @param int $network_id 网络ID
     * @param string $type 类型(recharge:充值,withdraw:提现,transfer:转账)
     * @url /api/account/getNetworkToken
     * @responseField string $network_code 网络编码
     * @responseField string $symbol 代币符号
     * @responseField string $name 代币名称
     * @responseField string $standard 代币标准
     * @return Response
     */
    public function getNetworkToken(): Response
    {
        try{
            $network_id = $this->getParams('network_id');
            $type = $this->getParams('type','all');
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $networkTokenService = new Web3NetworkTokenService();
            if($type=='recharge'){
                $data = $networkTokenService->getRechargeTokenList($network_id);
            }
            elseif($type=='withdraw'){
                $data = $networkTokenService->getWithdrawTokenList($network_id);
            }
            elseif($type=='transfer'){
                $data = $networkTokenService->getTransferTokenList($network_id);
            }
            else{
                $data = $networkTokenService->getNetworkTokenList($network_id);
            }
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取链路钱包地址
     * @method GET
     * @url /api/account/getNetworkWallet
     * @responseField int $network_id 网络ID
     * @responseField string $network_name 网络名称
     * @responseField string $network_code 网络编码
     * @responseField string $network_token 代币符号
     * @responseField string $wallet_address 钱包地址
     * @return Response
     */
    public function getNetworkWallet(): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $networkService = new Web3NetworkService();
            $rows = $networkService->getSelectNetwork();
            $data = [];
            $networkWalletService = new Web3NetworkWalletService();
            $walletList = $networkWalletService->getUserNetworkWalletAddress($userData['id']);
            foreach($rows as $v){
                $data[] = [
                    'network_id' => $v['id'],
                    'network_name' => $v['name'],
                    'network_code' => $v['code'],
                    'network_token' => $v['native_symbol'],
                    'wallet_address' => $walletList[$v['id']] ?? '',
                ];
            }
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取KYC数据
     * @method GET
     * @url /api/account/getKycData
     * @responseField string $review_status 审核状态(未处理:created,审核通过:approved,已拒绝:rejected)
     * @responseField string $review_time 审核时间
     * @responseField string $reject_reason 拒绝原因
     * @responseField string $created_time 申请时间
     * @return Response
     */
    public function getKycData(): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $kycService = new UserKycService();
            $result = $kycService->getUserKycObj($userData['id']);
            $data = [
                'created_time' => $result['created_time'],
                'review_status' => $result['review_status'],
                'review_time' => $result['review_time'],
                'reject_reason' => $result['reject_reason'],
            ];
            return $this->json($data);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 提交KYC数据
     * @method POST
     * @url /api/account/submitKycData
     * @return Response
     */
    public function submitKycData(): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $post = $this->getPost(['real_name','country','id_type','id_number','front_image','back_image','hand_image','phone']);
            $kycService = new UserKycService();
            $result = $kycService->saveUserKycData($userData['id'],$post);
            return $this->json($result);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }

    /**
     * 修改用户信息
     * @param string $avatar 头像
     * @param string $nickname 昵称
     * @method PUT
     * @url /api/account/updateUserInfo
     * @return Response
     */
    public function updateUserInfo(): Response
    {
        try{
            $userData = $this->request->getTokenUser();
            if(empty($userData)){
                throw new VerifyException('用户暂未授权');
            }
            $update = $this->getPost(['avatar','nickname']);
            $userService = new UserService();
            $result = $userService->updateUser($userData['id'],$update);
            return $this->json($result);
        }
        catch (\Throwable $e){
            return $this->failJson([],$e->getMessage(),$e->getCode());
        }
    }
}
