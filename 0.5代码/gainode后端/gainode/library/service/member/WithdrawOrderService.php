<?php

namespace library\service\member;

use library\model\member\OrderRecordModel;
use library\model\member\WithdrawOrderModel;
use library\dao\member\WithdrawOrderDao;
use library\service\sys\DictService;
use library\service\sys\FlowNumbersService;
use support\exception\VerifyException;
use support\extend\Log;
use support\extend\Service;
use support\web3\BscTransactionApi;
use support\web3\TronTransactionApi;

/**
 * Service
 * @method WithdrawOrderModel create($data)
 * @method WithdrawOrderModel updateOrCreate(array $params,array $data)
 * @method WithdrawOrderModel update($id,array $data){
 * @method WithdrawOrderModel get($id,string $field = null)
 * @method WithdrawOrderModel find($id)
 * @method WithdrawOrderModel findOrFail($id)
 * @method WithdrawOrderModel firstOrCreate(array $params,array $data)
 * @method WithdrawOrderModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class WithdrawOrderService extends Service
{

    /** 提现来源账户（从资金账户扣减） */
    const DEBIT_WALLET = 'Funding';
    public function __construct()
    {
        $this->dao = WithdrawOrderDao::class;
        parent::__construct();
    }

    /**
     * 获取订单编号
     * @param string $suffix
     * @return mixed
     */
    public function getOrderNo($suffix=''){
        $flowNumberServer = new FlowNumbersService();
        $order_no = $flowNumberServer->getFlowOrderNo($this->getNewDao()->getTable(),$suffix);
        $orderObj = $this->get($order_no,'order_no');
        if(empty($orderObj)){
            return $order_no;
        }
        return $this->getOrderNo();
    }

    public function getOrderStatusList($num=null){
        $data = [
            'all'=> '全部',
            'requested'=> '已请求',
            'approved'=> '已批准',
            'rejected'=>'已拒绝',
            'broadcasting'=>'正在广播',
            'completed'=>'已完成',
            'failed'=>'失败',
            'closed'=>'已关闭'
        ];
        if(!empty($num) && isset($data[$num])){
            return $data[$num];
        }
        return $data;
    }

    public function getOrderByNo(string $order_no): ?WithdrawOrderModel
    {
        return $this->fetch(['order_no'=>$order_no]);
    }

    public function getGroupAllStatusCnt(array $params = []): array
    {
        if(isset($params['size'])) unset($params['size']);
        if(isset($params['page'])) unset($params['page']);
        $rows = $this->groupBySelector(['order_status'],$params)->select($this->raw('order_status, COUNT(*) AS ct, SUM(money) AS money'))->get()->toArray();
        $data = ['all' => ['ct' => 0, 'money' => 0]];
        foreach ($rows as $v) {
            $data[$v['order_status']] = $v;
            $data['all']['ct']    += $v['ct'];
            $data['all']['money'] += $v['money'];
        }
        return $data;
    }

    public function getUserWithdrawOrderList(int $user_id, ?array $params=[])
    {
        $where = ['user_id'=>$user_id];
        if(!empty($params['order_status'])){
            if(is_string($params['order_status'])){
                $where['order_status'] = $params['order_status'];
            }
            elseif(is_array($params['order_status'])){
                $where = ['in',$params['order_status']];
            }
        }
        return $this->paginateArray($where,['id'=>'desc']);
    }

    // ══════════════════════════════════════════════════════════
    //  用户申请提现
    // ══════════════════════════════════════════════════════════

    /**
     * 创建提现订单（用户发起）
     * @param int   $user_id
     * @param array $data {
     *   type        string  提现网络 TRC20/ERC20/BEP20/BANK
     *   money       float   提现金额
     *   address     string  收款地址
     *   currency    string  提现币种
     *   bank_name   string  可选（BANK 类型）
     *   bank_card   string  可选
     *   real_name   string  可选
     *   descr       string  可选
     * }
     */
    public function createOrder(int $user_id, array $data): WithdrawOrderModel
    {
        if (!is_numeric($data['money']) || (float)$data['money'] <= 0) {
            throw new VerifyException('金额格式异常');
        }
        // 金额范围校验
        $dictService = new DictService();
        $config = $dictService->getDictConfigs('withdraw');
        if (!empty($config)) {
            if (!empty($config['min_money']) && (float)$data['money'] < (float)$config['min_money']) {
                throw new VerifyException('提现金额不能低于最小限制');
            }
            elseif (!empty($config['max_money']) && (float)$data['money'] > (float)$config['max_money']) {
                throw new VerifyException('提现金额不能超过最大限制');
            }
        }
        // 余额校验
        $walletService = new UserWalletService();
        $available = $walletService->getUserWalletValue($user_id, self::DEBIT_WALLET);
        if ($available < (float)$data['money']) {
            throw new VerifyException('余额不足');
        }

        $userService = new UserService();
        $userObj = $userService->get($user_id);
        if (empty($userObj)) {
            throw new VerifyException('用户不存在');
        }
        if ($userObj->is_frozen_withdraw == 1) {
            throw new VerifyException('您的账户已被冻结，无法提现');
        }

        // 计算手续费
        $fee    = 0.0;
        $actual = (float)$data['money'];
        if (!empty($config) && !empty($config['withdraw_rate'])) {
            $fee    = round((float)$data['money'] * (float)$config['withdraw_rate'] / 100, 8);
            $actual = round((float)$data['money'] - $fee, 8);
        }
        elseif (!empty($config) && !empty($config['withdraw_fee'])) {
            $fee    = (float)$config['withdraw_fee'];
            $actual = round((float)$data['money'] - $fee, 8);
        }
        if($actual<=0){
            throw new VerifyException('提现金额过低');
        }
        elseif($fee>$actual){
            throw new VerifyException('提现手续费过高');
        }
//        if($data['type']=='BANK'){
//            UserService::saveUserCardData($user_id,[
//                'bank_name'  => $data['bank_name'],
//                'bank_card'  => $data['bank_card'],
//                'name'  => $data['real_name'],
//                'phone'=>$userObj->phone
//            ]);
//            $data['address'] = $data['bank_name'].'-'.$data['bank_card'].'-'.$data['real_name'];
//        }
//        elseif($data['type']=='ERC20'){
//            UserService::saveUserCardData($user_id,[
//                'erc20_address'=>$data['address']
//            ]);
//        }
//        elseif($data['type']=='TRC20'){
//            UserService::saveUserCardData($user_id,[
//                'trc20_address'=>$data['address']
//            ]);
//        }
//        elseif($data['type']=='BEP20'){
//            UserService::saveUserCardData($user_id,[
//                'bep20_address'=>$data['address']
//            ]);
//        }
        $orderData = [
            'user_id'       => $user_id,
            'order_no'      => $this->getOrderNo(),
            'type'          => $data['type'],       // 保持 type 字段兼容
            'money'         => (float)$data['money'],
            'fee'           => $fee,
            'actual_amount' => $actual,
            'address'    => $data['address'],
            'currency'      => $data['currency'],
            'order_status'  => WithdrawOrderModel::STATUS_REQUESTED,
            'status'        => 1,
            'descr'         => $data['descr'] ?? '',
        ];

        $conn = $this->connection();
        try {
            $conn->beginTransaction();
            $withdrawOrderObj = $this->create($orderData);
            if (!empty($withdrawOrderObj)) {
                // 冻结提现金额（从可用余额移至冻结）
                $walletService->addUserFrozen($user_id, self::DEBIT_WALLET, (float)$data['money']);
                // 写入汇总记录（待审核）
                $orderRecordService = new OrderRecordService();
                $orderRecordService->writeWithdraw($withdrawOrderObj);
            }
            $conn->commit();
            return $withdrawOrderObj;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 链上确认提现到账（broadcasting → completed）
     * 调用方：BusinessWorker confirmWithdrawOrder 定时任务（每分钟轮询）。
     * 逻辑：取所有 broadcasting 状态、有 tx_hash 的订单，调链上 API 查询 receipt，
     *       状态 success 则推进到 completed，并记录 confirmed_at。
     *
     * @return int 本次确认数
     */
    public function confirmBroadcasted(): int
    {
        $rows = $this->selector(['order_status'=>WithdrawOrderModel::STATUS_BROADCASTING])
            ->whereNotNull('tx_hash')
            ->where('tx_hash', '<>', '')
            ->limit(50)
            ->get();
        if (empty($rows)) {
            return 0;
        }
        $confirmed = 0;
        foreach ($rows as $orderObj) {
            try {
                $network = strtoupper((string)($orderObj->type ?? ''));
                $success = false;
                if ($network === 'BEP20') {
                    $api = new BscTransactionApi();
                    $res = $api->getTransactionStatus($orderObj->tx_hash);
                    $success = ($res['status'] ?? '') === 'success';
                }
                elseif ($network === 'TRC20') {
                    $api = new TronTransactionApi();
                    $info = $api->extractDepositInfo($orderObj->tx_hash);
                    $success = ($info['status'] ?? '') === 'success';
                }
                else {
                    // ERC20 复用 BSC RPC 客户端（同 JSON-RPC 协议）
                    $api = new BscTransactionApi();
                    $res = $api->getTransactionStatus($orderObj->tx_hash);
                    $success = ($res['status'] ?? '') === 'success';
                }

                if ($success) {
                    $orderObj->update([
                        'order_status' => WithdrawOrderModel::STATUS_COMPLETED,
                        'confirmed_at' => date('Y-m-d H:i:s'),
                    ]);
                    // 更新汇总记录 → 已完成
                    $orderRecordService = new OrderRecordService();
                    $orderRecordService->updateByRef(
                        'member_withdraw_order',
                        (int)$orderObj->id,
                        OrderRecordModel::STATUS_SUCCESS,
                        "链上确认到账 #{$orderObj->order_no}"
                    );
                    // 提现手续费记到平台账户（addSafe 失败不阻塞主流程）
                    $fee = (float)$orderObj->fee;
                    if ($fee > 0) {
//                        PlatformWalletService::addSafe(
//                            'withdraw.fee',
//                            'member_withdraw_order',
//                            (int)$orderObj->id,
//                            (int)$orderObj->user_id,
//                            $fee,
//                            (string)($orderObj->currency ?: 'USDT'),
//                            "提现手续费 #{$orderObj->order_no}"
//                        );
                    }
                    $confirmed++;
                    Log::info("[Withdraw] 链上确认 order={$orderObj->order_no} tx={$orderObj->tx_hash}");
                }
            } catch (\Throwable $e) {
                Log::warning("[Withdraw] confirmBroadcasted 异常 order={$orderObj->order_no}: " . $e->getMessage());
            }
        }
        return $confirmed;
    }


    /**
     * 审核提现订单
     * @param int|string $order_id
     * @param string   $status   approved=批准 rejected=拒绝
     * @param string  $descr
     */
    public function verifyOrder($order_id, string $status, string $descr = ''): WithdrawOrderModel
    {
        $orderObj = $this->get($order_id);
        if (empty($orderObj) || $orderObj->order_status !== WithdrawOrderModel::STATUS_REQUESTED) {
            throw new VerifyException('提现订单不存在或状态不可审核');
        }
        $conn = $this->connection();
        try {
            $conn->beginTransaction();
            $walletService = new UserWalletService();
            $orderRecordService = new OrderRecordService();
            if ($status === WithdrawOrderModel::STATUS_APPROVED) {
                // 批准：按提现类型分发处理
                $network = strtoupper(($orderObj->type ?? ''));
                $toAddress = $orderObj->to_address ?? $orderObj->address ?? '';
                $txHash    = null;
                $newStatus = WithdrawOrderModel::STATUS_BROADCASTING;
                if ($network === 'BANK') {
                    // 银行卡提现：不上链，状态直接 → completed，等待财务线下打款
                    $newStatus = WithdrawOrderModel::STATUS_COMPLETED;
                }
                elseif ($network === 'BEP20' || $network === 'BSC') {
                    // BSC 链上提现
//                    $api = new BscTransactionApi();
//                    $txHash = $api->submitWithdrawOrder($toAddress, $orderObj->order_no, (float)$orderObj->actual_amount,$network);
                }
                elseif ($network === 'TRC20' || $network === 'TRON') {
                    // TRC20：暂未对接 Tron 自动转账服务（需独立部署 Tron broadcaster）
                    // 临时方案：进 broadcasting 状态，由人工线下打款并填写 tx_hash
                    Log::warning("[Withdraw] TRC20 自动转账未实现 order={$orderObj->order_no}，需人工处理");
                }
                elseif ($network === 'ERC20' || $network === 'ETHEREUM') {
                    // ERC20：同上，需独立部署 Ethereum broadcaster
                    Log::warning("[Withdraw] ERC20 自动转账未实现 order={$orderObj->order_no}，需人工处理");
                }
                else {
                    throw new \Exception("不支持的提现网络: {$network}");
                }
                $orderObj->saveData([
                    'status'         => 2,
                    'order_status'   => $newStatus,
                    'tx_hash'        => $txHash,
                    'descr'          => $descr,
                    'approved_at'    => date('Y-m-d H:i:s'),
                    'broadcasted_at' => $txHash ? date('Y-m-d H:i:s') : null,
                    // BANK 直接完成时同步填 confirmed_at
                    'confirmed_at'   => $newStatus === WithdrawOrderModel::STATUS_COMPLETED ? date('Y-m-d H:i:s') : null,
                ]);
                // 扣减冻结金额（链上广播后结算）
                $walletService->minusUserWallet(
                    (int)$orderObj->user_id,
                    self::DEBIT_WALLET,
                    (float)$orderObj->money,
                    UserWalletService::EVENT_WITHDRAW_CONFIRMED,
                    "Withdrawal received #{$orderObj->order_no}",
                    'member_withdraw_order', (int)$orderObj->id
                );
                // 更新汇总记录 → BANK立即成功，其他链上广播中（pending）
                $orderRecordService->updateByRef(
                    'member_withdraw_order',
                    (int)$orderObj->id,
                    ($newStatus === WithdrawOrderModel::STATUS_COMPLETED
                        ? OrderRecordModel::STATUS_SUCCESS
                        : OrderRecordModel::STATUS_PENDING),
                    ($newStatus === WithdrawOrderModel::STATUS_COMPLETED ? "Withdrawal received #{$orderObj->order_no}" : 'In on-chain broadcast')
                );
            }
            else {
                // 拒绝：解冻退回
                $orderObj->saveData([
                    'status'       => 2,
                    'order_status' => WithdrawOrderModel::STATUS_REJECTED,
                    'descr'        => $descr,
                ]);
                $walletService->minuUserFrozen(
                    (int)$orderObj->user_id,
                    self::DEBIT_WALLET,
                    (float)$orderObj->money
                );
                // 更新汇总记录 → 失败
                $orderRecordService->updateByRef(
                    'member_withdraw_order',
                    (int)$orderObj->id,
                    OrderRecordModel::STATUS_FAILED,
                    $descr ?: 'Withdrawal application rejected'
                );
            }
            $conn->commit();
            return $orderObj;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}
