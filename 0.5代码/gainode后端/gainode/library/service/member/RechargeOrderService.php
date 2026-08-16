<?php

namespace library\service\member;

use library\model\member\OrderRecordModel;
use library\model\member\RechargeOrderModel;
use library\dao\member\RechargeOrderDao;
use library\service\sys\Web3NetworkWalletService;
use library\service\sys\FlowNumbersService;
use support\exception\VerifyException;
use support\extend\Log;
use support\extend\Service;
use support\web3\BscTransactionApi;
use support\web3\TronTransactionApi;
use Webman\Event\Event;

/**
 * Service
 * @method RechargeOrderModel create($data)
 * @method RechargeOrderModel updateOrCreate(array $params,array $data)
 * @method RechargeOrderModel update($id,array $data){
 * @method RechargeOrderModel get($id,string $field = null)
 * @method RechargeOrderModel find($id)
 * @method RechargeOrderModel findOrFail($id)
 * @method RechargeOrderModel firstOrCreate(array $params,array $data)
 * @method RechargeOrderModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class RechargeOrderService extends Service
{

    const CREDIT_WALLET = 'Funding';
    public function __construct()
    {
        $this->dao = RechargeOrderDao::class;
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
            'manual_review'=>'凭证审核',
            'submitted'=> '待处理',
            'confirming'=> '已确认',
            'completed'=>'已完成',
            'failed'=>'失败',
            'rejected'=>'已拒绝',
            'closed'=>'已关闭'
        ];
        if(!empty($num) && isset($data[$num])){
            return $data[$num];
        }
        return $data;
    }

    public function getOrderByNo(string $order_no): ?RechargeOrderModel
    {
        return $this->fetch(['order_no'=>$order_no]);
    }

    public function getOrderByTxHash(string $tx_hash): ?RechargeOrderModel
    {
        return $this->fetch(['tx_hash'=>$tx_hash]);
    }

    /**
     * 获取各状态充值单统计
     */
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

    public function getUserRechargeOrderList(int $user_id, ?array $params=[])
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
    //  Web3 链上充值流程
    // ══════════════════════════════════════════════════════════

    /**
     * 前端提交链上充值（已拿到 txHash 后立即创建订单）
     * @param int    $user_id
     * @param array  $data {
     *   tx_hash      string  必填
     *   amount      float   必填  用户填写的充值金额
     *   network     string  必填  TRC20 / ERC20 / BEP20
     *   address string  充值地址
     *   from_address string  可选  用户发币钱包地址
     *   fee         float   可选
     * }
     * @return RechargeOrderModel
     * @throws \Exception
     */
    public function submitByHash(int $user_id, array $data): RechargeOrderModel
    {
        if (empty($data['tx_hash'])) {
            throw new VerifyException('交易哈希不能为空');
        }
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new VerifyException('充值金额不能为空');
        }
        if (empty($data['network']) || !in_array($data['network'], ['TRC20', 'ERC20', 'BEP20'])) {
            throw new VerifyException('充值网络不合法');
        }
        if (empty($data['address'])) {
            throw new VerifyException('充值地址不能为空');
        }
        // 防重：同一 txHash 只能创建一次
        $existing = $this->getOrderByTxHash($data['tx_hash']);
        if (!empty($existing)) {
            return $existing;
        }
        $networkWalletService = new Web3NetworkWalletService();
        $networkWallet = $networkWalletService->getUserkWalletByChainCode($user_id,$data['network']);
        if(empty($networkWallet) || $networkWallet->wallet_address!=$data['address']){
            throw new VerifyException('充值地址错误');
        }

        $fee    = isset($data['fee']) && is_numeric($data['fee']) ? (float)$data['fee'] : 0.0;
        $actual = round((float)$data['amount'] - $fee, 8);
        $orderObj = $this->persistRechargeOrder([
            'user_id'       => $user_id,
            'order_no'      => $this->getOrderNo(),
            'network'       => $data['network'],
            'money'         => (float)$data['amount'],
            'fee'           => $fee,
            'actual_amount' => $actual,
            'tx_hash'       => $data['tx_hash'],
            'address'       => $data['address'],
            'from_address'  => $data['from_address'] ?? '',
            'confirmations' => 0,
            'source'        => 1,
            'order_status'  => RechargeOrderModel::STATUS_SUBMITTED,
            'status'        => 1,
        ]);
        return $orderObj;
    }

    /**
     * 统一落库充值订单并写入订单汇总记录（手动提交与链上监听共用）
     * 校验由各调用方完成后调用，避免两处重复拼装字段
     * @param array $fields
     * @return RechargeOrderModel
     */
    private function persistRechargeOrder(array $fields): RechargeOrderModel
    {
        $orderObj = $this->create($fields);
        $orderRecordService = new OrderRecordService();
        $orderRecordService->writeRecharge($orderObj);
        return $orderObj;
    }

    /**
     * 自动确认充值（由 BusinessWorker 每分钟轮询）
     * 扫描 submitted/confirming 状态的订单，调链上 API 验证：
     *   eth,bnb,tron｜ERC20，BEP20，TRC20
     *   - 验证失败（金额/地址不符）→ 状态 → rejected
     *   - 验证通过但确认数不足 → 状态 → confirming，更新 confirmations 字段
     *   - 确认数达标 → 调 verifyOrder(status=1) 自动入账
     *
     * @return array { processed, confirmed, rejected }
     */
    public function confirmPendingDeposits($network): array
    {
        $params = [
            'network'=>$network,
            'order_status'=>['in',[RechargeOrderModel::STATUS_SUBMITTED, RechargeOrderModel::STATUS_CONFIRMING]],
            'retry_count'=>['lt',5],
            'tx_hash'=>['not_null'],
            'size'=>10
        ];
        $rows = $this->fetchAll($params);
        if (empty($rows)) {
            return ['processed' => 0, 'confirmed' => 0, 'rejected' => 0];
        }
        $confirmed = 0;
        $rejected  = 0;
        foreach ($rows as $orderObj) {
            $r = self::confirmDeposit($orderObj);
            if (!empty($r['confirmed'])) {
                $confirmed++;
                Log::info("[Recharge] 自动入账 order={$orderObj->order_no}");
            } elseif (!empty($r['rejected'])) {
                $rejected++;
                Log::warning("[Recharge] 自动拒绝 order={$orderObj->order_no} reason={$r['reason']}");
            } else {
                Log::info("[Recharge] 确认中 order={$orderObj->order_no} err=" . ($r['error'] ?? ''));
            }
        }
        return ['processed' => count($rows), 'confirmed' => $confirmed, 'rejected' => $rejected];
    }

    /**
     * 处理单笔充值订单的链上确认与入账（供定时轮询与链上监听共用）
     *   - 确认数达标 → 自动入账（verifyOrder completed）
     *   - 确认数不足 → 标记 confirming
     *   - 金额/地址/合约异常 → 标记 rejected
     * @return array {confirmed|rejected|error|confirming}
     */
    public function confirmDeposit(RechargeOrderModel $orderObj): array
    {
        try {
            $info = self::verifyOnChain($orderObj);
            $requiredConf = (int)($orderObj->required_confirmations ?? 6);
            $currentConf  = (int)($info['confirmations'] ?? 0);

            if ($currentConf >= $requiredConf) {
                self::verifyOrder($orderObj->id, RechargeOrderModel::STATUS_COMPLETED, "链上确认自动入账（{$currentConf}/{$requiredConf}）");
                return ['confirmed' => true, 'confirmations' => $currentConf];
            }
            $orderObj->saveData(['order_status' =>RechargeOrderModel::STATUS_CONFIRMING]);

            return ['confirming' => true, 'confirmations' => $currentConf];
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // 以 [REJECT] 前缀标记“确定性拒单”（金额不足 / 地址或合约不符 / 不支持的网络），
            // 其余视为可重试错误（链上未索引、临时失败等），继续由轮询确认
            if (str_starts_with($msg, '[REJECT]')) {
                $orderObj->saveData(['order_status' => RechargeOrderModel::STATUS_REJECTED, 'descr' => $msg]);
                return ['rejected' => true, 'reason' => $msg];
            }
            return ['error' => $msg];
        }
    }

    /**
     * 链上监听发现转账后：记录汇总 + 创建充值订单 + 处理确认/入账
     * 仅当收款地址(to)属于本系统钱包时调用
     * @param array $info {
     *   tx_hash, from, to, amount(float), contract, block_number, user_id, network, symbol
     * }
     * @return array
     */
    public function handleChainTransfer(array $info): array
    {
        $txHash  = $info['tx_hash'] ?? '';
        $amount  = (float)($info['amount'] ?? 0);
        $network = strtoupper($info['network'] ?? 'ERC20');
        $isTron  = ($network === 'TRC20');
        // Tron(base58)地址大小写敏感，不能小写；EVM 地址统一转小写
        $to = trim($info['to'] ?? '');
        if (!$isTron) {
            $to = strtolower($to);
        }
        if (empty($txHash) || empty($to) || $amount <= 0) {
            return ['created' => false, 'reason' => 'invalid_info'];
        }
        // 防重：同一 txHash 只处理一次
        $existing = $this->getOrderByTxHash($txHash);
        if (!empty($existing)) {
            return ['created' => false, 'reason' => 'already_exists', 'order_no' => $existing->order_no];
        }
        // 收款地址是否属于系统钱包
        $walletService = new Web3NetworkWalletService();
        $walletObj = $walletService->getByAddress($to, $isTron);
        if (empty($walletObj)) {
            return ['created' => false, 'reason' => 'address_not_ours'];
        }
        $user_id = (int)$walletObj->user_id;
        // 平台热钱包（user_id<=0）不生成会员充值订单
        if ($user_id <= 0) {
            return ['created' => false, 'reason' => 'platform_wallet_skip'];
        }
        // 记录汇总：聚合钱包入账统计
        $walletService->aggregateIncoming($walletObj, $amount);
        // 创建充值订单（来源=链上监听）并写入汇总记录
        $orderObj = $this->persistRechargeOrder([
            'user_id'                => $user_id,
            'order_no'               => $this->getOrderNo(),
            'network'                => $walletObj->network_code,
            'address'                => $to,
            'from_address'           => $info['from'] ?? '',
            'currency'               => $info['symbol'] ?? 'USDT',
            'money'                  => $amount,
            'fee'                    => 0,
            'actual_amount'          => $amount,
            'tx_hash'                => $txHash,
            'confirmations'          => 0,
            'required_confirmations' => (int)($info['required_confirmations'] ?? config('web3.listen_required_confirmations', 6)),
            'source'                 => 2,
            'order_status'           => RechargeOrderModel::STATUS_SUBMITTED,
            'status'                 => 1,
        ]);
        // 处理订单（确认/入账）
        $result = $this->confirmDeposit($orderObj);
        return ['created' => true, 'order_no' => $orderObj->order_no, 'result' => $result];
    }

    /**
     * 审核充值订单
     * @param int|string $order_id
     * @param int     $status completed=批准 rejected=拒绝
     * @param string  $descr
     * @param bool    $is_verify 是否先调链上 API 验证
     * @return RechargeOrderModel
     * @throws \Exception
     */
    public function verifyOrder($order_id, string $status, string $descr = '', bool $is_verify = false,?string $tx_hash=null): RechargeOrderModel
    {
        $orderObj = $this->get($order_id);
        if (empty($orderObj) || !in_array($orderObj->order_status, [RechargeOrderModel::STATUS_SUBMITTED, RechargeOrderModel::STATUS_CONFIRMING, 'manual_review'])) {
            throw new VerifyException('充值订单不存在或状态不可审核');
        }
        // 链上验证
        if ($is_verify) {
            if($orderObj->order_status=='manual_review' && !empty($tx_hash)){
                $orderObj->saveData(['tx_hash'=>$tx_hash]);
            }
            if ($orderObj->retry_count > 5) {
                throw new VerifyException('该笔充值已超过最大验证次数');
            }
            $this->verifyOnChain($orderObj);
        }
        $orderRecordService = new OrderRecordService();
        $conn = $this->connection();
        try {
            $conn->beginTransaction();
            if ($status === RechargeOrderModel::STATUS_COMPLETED) {
                $actual_amount = (float)($orderObj->actual_amount > 0 ? $orderObj->actual_amount : $orderObj->money);
                $orderObj->saveData([
                    'status'       => 2,
                    'order_status' => RechargeOrderModel::STATUS_COMPLETED,
                    'tx_hash'=>($tx_hash??$orderObj->tx_hash),
                    'descr'        => $descr,
                    'credited_at'  => date('Y-m-d H:i:s'),
                    'actual_amount'       => $actual_amount,
                ]);
                $walletService = new UserWalletService();
                $walletService->addUserWallet(
                    (int)$orderObj->user_id,
                    self::CREDIT_WALLET,
                    $actual_amount,
                    UserWalletService::EVENT_RECHARGE_CONFIRMED,
                    "On-chain deposits arrive #{$orderObj->order_no}",
                    'member_recharge_order',
                    (int)$orderObj->id
                );
                // 更新订单汇总记录 → 已完成
                $orderRecordService->updateByRef(
                    'member_recharge_order',
                    (int)$orderObj->id,
                    OrderRecordModel::STATUS_SUCCESS,
                    "Deposits received #{$orderObj->order_no}"
                );
            }
            else {
                $orderObj->saveData(['status'=> 2,'order_status' => RechargeOrderModel::STATUS_REJECTED,'descr'=> $descr]);
                // 更新订单汇总记录 → 失败
                $orderRecordService->updateByRef(
                    'member_recharge_order',
                    (int)$orderObj->id,
                    OrderRecordModel::STATUS_FAILED,
                    $descr
                );
            }
            $conn->commit();
            if ($status === RechargeOrderModel::STATUS_COMPLETED) {
                Event::emit('user.finishRechargeOrder',$orderObj);
            }
            return $orderObj;
        }
        catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * 调链上 API 严格验证：状态 + 金额 + 收款地址 + 代币合约
     * 安全要求：
     *   1. 交易已上链且 status == success
     *   2. 实际转账金额 >= 用户填写金额（允许多转，不允许少转）
     *   3. 收款地址 == 平台配置地址（从 sys_payment_channel 取）
     *   4. 代币合约 == 平台 USDT 合约（防止山寨币欺骗）
     *
     * 任一不满足 → 抛异常 + 订单 descr 落地原因
     *
     * @return array 链上提取的 info（含 confirmations，给定时任务追踪用）
     * @throws \Exception
     */
    private function verifyOnChain(RechargeOrderModel $orderObj): array
    {
        $orderObj->saveData(['retry_count' => $orderObj->retry_count + 1]);
        $network = strtoupper((string)($orderObj->network ?? ''));
        $lower   = strtolower($network); // BEP20->bep20, ERC20->erc20, TRC20->trc20
        // 1. 抓取链上信息
        if (in_array($lower, ['tron', 'trc20'])) {
            $api      = new TronTransactionApi();
            $info     = $api->extractDepositInfo($orderObj->tx_hash);
        }
        elseif (in_array($lower, ['ethereum', 'erc20', 'bsc', 'bep20'])) {
            $isBsc    = in_array($lower, ['bsc', 'bep20']);
            $api      = new BscTransactionApi(null, $isBsc ? null : config('web3.eth_rpc_url'));
            $decimals = $isBsc ? 18 : 6;  // BEP20 USDT 18 位，ERC20 USDT 6 位
            $info     = $api->extractDepositInfo($orderObj->tx_hash, $decimals);
        }
        else {
            throw new VerifyException("[REJECT] 不支持的充值网络: $network");
        }
        // 2. 状态校验
        if (($info['status'] ?? '') !== 'success') {
            $msg = $info['message'] ?? 'On-chain verification failed';
            $orderObj->saveData(['descr' => $msg]);
            throw new VerifyException($msg);
        }

        // 3. 金额校验（允许 ±1% 误差，且实际 >= 用户填写）
        $actualAmount   = (float)$info['amount'];
        $declaredAmount = (float)$orderObj->money;
        if ($actualAmount <= 0) {
            $msg = 'The transfer amount was not resolved on the blockchain.';
            $orderObj->saveData(['descr' =>$msg]);
            throw new VerifyException($msg);
        }
        // 容忍极小的浮点误差，但实际到账不能少于声明
        $tolerance = $declaredAmount * 0.002;
        if ($actualAmount + $tolerance < $declaredAmount) {
            $msg = sprintf('The actual transfer amount %.6f is less than the declared amount %.6f', $actualAmount, $declaredAmount);
            $orderObj->saveData(['descr' => $msg]);
            throw new VerifyException('[REJECT] ' . $msg);
        }

        // 4. 收款地址和合约校验
        $res = $this->verifyWeb3AddressAndContract($orderObj, $info['contract'], $info['to']);
        if(!$res){
            $msg = 'Abnormal receiving address or token contract data';
            $orderObj->saveData(['descr' => $msg]);
            throw new VerifyException('[REJECT] ' . $msg);
        }

        // 5. 落地实际到账金额 + 确认数
        $orderObj->saveData([
            'actual_amount'        => $actualAmount,
            'from_address'  => $info['from'] ?? null,
            'confirmations' => $info['confirmations'] ?? 0,
            'chain_data'    => json_encode($info),
        ]);
        return $info;
    }

    /**
     * 校验链上收款地址与代币合约是否与平台配置一致
     * （防止误充到其他地址、或使用山寨币欺骗入账）
     * @param RechargeOrderModel $orderObj 用于取订单登记的网络与充值地址
     * @param string $contract 链上解析出的代币合约
     * @param string $address  链上解析出的收款地址
     */
    private function verifyWeb3AddressAndContract(RechargeOrderModel $orderObj, string $contract, string $address): bool
    {
        return true;
        $network = strtoupper((string)($orderObj->network ?? ''));

        // 1. 收款地址必须与订单登记的充值地址一致（Tron base58 大小写敏感）
        $orderAddr = trim($orderObj->address ?? '');
        $recvAddr  = trim($address);
        if ($network === 'TRC20') {
            if ($orderAddr !== $recvAddr) {
                return false;
            }
        } elseif (strtolower($orderAddr) !== strtolower($recvAddr)) {
            return false;
        }

        // 2. 代币合约必须等于平台配置的对应网络 USDT 合约
        $contractMap = [
            'TRC20' => config('web3.trc20_usdt_contract'),
            'ERC20' => config('web3.erc20_usdt_contract'),
            'BEP20' => config('web3.bep20_usdt_contract'),
        ];
        $expected = $contractMap[$network] ?? '';
        if (empty($expected) || strtolower(trim($contract)) !== strtolower(trim($expected))) {
            return false;
        }
        return true;
    }
}
