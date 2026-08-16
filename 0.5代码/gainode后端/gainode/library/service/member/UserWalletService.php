<?php

namespace library\service\member;

use library\dao\member\UserWalletLogDao;
use library\model\member\UserWalletModel;
use library\dao\member\UserWalletDao;
use library\service\arbitrage\ProjectOrderLogsService;
use support\exception\VerifyException;
use support\extend\Log;
use support\extend\Service;

/**
 * Service
 * @method UserWalletModel create($data)
 * @method UserWalletModel updateOrCreate(array $params,array $data)
 * @method UserWalletModel update($id,array $data){
 * @method UserWalletModel get($id,string $field = null)
 * @method UserWalletModel find($id)
 * @method UserWalletModel findOrFail($id)
 * @method UserWalletModel firstOrCreate(array $params,array $data)
 * @method UserWalletModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 * @method UserWalletModel getUserWallet(int $user_id, string $wallet_type = 'Funding', bool $isThrow = true)
 */
class UserWalletService extends Service
{
    public function __construct()
    {
        $this->dao = UserWalletDao::class;
        parent::__construct();
    }

    const WALLET_TYPES = [
        'Funding'   => '资金账户',
        'Arbitrage' => '套利账户',
        'Integral'  => '积分账户'
    ];

    // ── 语义化事件名 ──────────────────────────────────────────
    const EVENT_RECHARGE_CONFIRMED  = 'recharge.confirmed';
    const EVENT_RECHARGE_REWARD  = 'recharge.reward';
    const EVENT_ARBITRAGE_LOCK    = 'arbitrage.lock';
    const EVENT_ARBITRAGE_SETTLE  = 'arbitrage.settle';
    const EVENT_ARBITRAGE_UNLOCK  = 'arbitrage.unlock';
    const EVENT_ARBITRAGE_PROFIT  = 'arbitrage.profit';
    const EVENT_ARBITRAGE_VOID    = 'arbitrage.void';
    const EVENT_WITHDRAW_REQUESTED = 'withdraw.requested';
    const EVENT_WITHDRAW_CONFIRMED = 'withdraw.confirmed';
    const EVENT_WITHDRAW_REJECTED  = 'withdraw.rejected';
    const EVENT_PROJECT_PAYMENT = 'project.payment';
    const EVENT_TRANSFER_IN        = 'transfer.in';
    const EVENT_TRANSFER_OUT       = 'transfer.out';
    const EVENT_REWARD_BONUS       = 'reward.bonus';
    const EVENT_REWARD_REBATE      = 'reward.rebate';
    const EVENT_ACCOUNT_FROZEN     = 'account.frozen';
    const EVENT_ACCOUNT_ADJUSTED   = 'account.adjusted';
    const EVENT_ACCOUNT_PACKET   = 'account.packet';

    // ── 事件方向映射（1=收入 -1=支出 0=冻结变动） ──────────────
    const EVENT_DIRECTIONS = [
        'recharge.confirmed'  =>  1,
        'recharge.reward'     =>  1,
        'arbitrage.lock'      => -1,
        'arbitrage.settle'    =>  1,
        'arbitrage.unlock'    =>  1,
        'arbitrage.profit'    =>  1,
        'arbitrage.void'      => -1,
        'withdraw.requested'  => -1,
        'withdraw.confirmed'  => -1,
        'withdraw.rejected'   =>  1,
        'project.payment'     => -1,
        'transfer.in'         =>  1,
        'transfer.out'        => -1,
        'reward.bonus'        =>  1,
        'reward.rebate'       =>  1,
        'account.frozen'      =>  0,
        'account.adjusted'    =>  0,
        'account.packet'      =>  1,
    ];

    public static function getWalletTypeList($type=null)
    {
        if(!empty($type)){
            if(isset(self::WALLET_TYPES[$type])){
                return self::WALLET_TYPES[$type];
            }
            else{
                return null;
            }
        }
        return self::WALLET_TYPES;
    }

    public static function getEventList(?string $event_type = null)
    {
        $map = [
            'recharge.confirmed'  => '充值到账',
            'recharge.reward'     => '充值奖励',
            'arbitrage.lock'      => '套利开仓锁定',
            'arbitrage.settle'    => '套利结算到账',
            'arbitrage.unlock'    => '套利作废退本',
            'arbitrage.profit'    => '套利利润',
            'arbitrage.void'      => '套利利润回退',
            'withdraw.requested'  => '提现申请',
            'withdraw.confirmed'  => '提现到账',
            'withdraw.rejected'   => '提现驳回',
            'project.payment'     => '矿机支付',
            'transfer.in'         => '划转收入',
            'transfer.out'        => '划转转出',
            'reward.bonus'        => '奖励发放',
            'reward.rebate'       => '返佣',
            'account.frozen'      => '账户冻结',
            'account.adjusted'    => '后台调整',
            'account.packet'      => '领取红包',
        ];
        if (!is_null($event_type)) {
            return $map[$event_type] ?? $event_type;
        }
        return $map;
    }

    /**
     * 为新用户创建所有钱包账户
     */
    public function createUserWallet(int $user_id)
    {
        $data = [];
        $index = 1;
        foreach (self::WALLET_TYPES as $type => $name) {
            $data[] = [
                'user_id'     => $user_id,
                'wallet_type' => $type,
                'sort'        => $index,
            ];
            $index++;
        }
        return $this->insert($data);
    }

    public function getUserArbitrageMoneyByIds(array $user_ids){
        $rows = $this->fetchAll(['id'=>['in',$user_ids],'wallet_type'=>'Arbitrage'],[],['balance']);
        $data = [];
        foreach($rows as $v){
            $data[$v['id']] = $v['balance'];
        }
        return $data;
    }

    /**
     * 确保用户在指定账户类型下有钱包记录（不存在则创建）
     * @return UserWalletModel
     */
    public function ensureWallet(int $user_id, string $wallet_type): UserWalletModel
    {
        $walletObj = $this->getUserWallet($user_id, $wallet_type, false);
        if (!empty($walletObj)) {
            return $walletObj;
        }
        // 不存在则插入（依赖 uk_user_wallet_type 唯一索引兜底防并发）
        try {
            $newObj = $this->create([
                'user_id'     => $user_id,
                'wallet_type' => $wallet_type,
                'balance'     => 0,
                'frozen'      => 0,
                'status'      => 1,
            ]);
            return $newObj;
        }
        catch (\Throwable $e) {
            // 并发插入时被唯一索引拦截 → 重新查
            return $this->getUserWallet($user_id, $wallet_type, true);
        }
    }

    public function getTotalWalletValue(string $wallet_type = 'Funding'):?object
    {
        $field = $this->raw('SUM(balance) AS amount,SUM(frozen) AS frozen,SUM(total_deposit) as total_deposit,SUM(total_withdraw) AS total_withdraw');
        return $this->selector(['wallet_type'=>$wallet_type],[],[$field]);
    }

    public function getWalletList(int $user_id): array
    {
        $field = ['wallet_type','balance','frozen','total_in','total_out'];
        $rows = $this->fetchAll(['user_id'=>$user_id],['sort'=>'asc'],$field)->toArray();
        $data = [];
        foreach ($rows as $v) {
            $v['available'] = max(0.0, round((float)$v['balance'] - (float)$v['frozen'], 4));
//            $v['wallet_name'] = self::getWalletTypeList($v['wallet_type']);
            if($v['wallet_type']=='Funding'){
                $v['receipt_amount'] = $this->getArbitrageReceiptAmount($user_id);
            }
            $data[$v['wallet_type']] = $v;
        }
        return $data;
    }

    private function getArbitrageReceiptAmount(int $user_id):float{
        $orderLogSvc = new ProjectOrderLogsService();
        return $orderLogSvc->sum('income_amount',['income_userid'=>$user_id,'status'=>['in',[0,1]]]);
    }

    public function getUserWalletValue(int $user_id, string $wallet_type = 'Funding'): float
    {
        $walletObj = $this->getUserWallet($user_id, $wallet_type, false);
        if (empty($walletObj)) {
            return 0.0;
        }
        return $walletObj->getAvailable();
    }

    // ══════════════════════════════════════════════════════════
    //  核心资金操作
    // ══════════════════════════════════════════════════════════

    /**
     * 增加余额
     *
     * @param int    $userid      用户ID
     * @param string $wallet_type 账户类型
     * @param float  $money       金额（必须 > 0）
     * @param string $event_type  语义事件名（默认 recharge.confirmed）
     * @param string $descr       备注
     * @param int    $admin_id    管理员ID（后台操作时传入）
     * @param bool   $is_record_hide 是否隐藏流水
     * @param string $ref_table   来源表名
     * @param int    $ref_id      来源记录ID
     */
    public function addUserWallet(
        int    $userid,
        string $wallet_type,
        float  $money,
        string $event_type = self::EVENT_RECHARGE_CONFIRMED,
        string $descr = '',
        string $ref_table = '',
        int    $ref_id = 0,
        ?int    $admin_id = 0,
        ?bool   $is_record_hide = false,
    ): bool {
        if ($money <= 0) {
            Log::error("钱包加钱失败：金额必须>0, user={$userid} wallet={$wallet_type} money={$money}");
            throw new VerifyException('金额必须大于0');
        }
        $conn = $this->connection();
        $ownTx = $conn->transactionLevel() === 0;
        try {
            if ($ownTx) {
                $conn->beginTransaction();
            }
            $walletObj     = $this->getUserWallet($userid, $wallet_type, true);
            $beforeBalance = (float)$walletObj->balance;
            $beforeFrozen  = (float)$walletObj->frozen;
            $afterBalance  = $beforeBalance + $money;
            $update = [
                'balance'  => $afterBalance,
                'total_in' => $this->raw('total_in + ' . $money),
            ];
            // 充值事件才累加 total_deposit
            if ($event_type === self::EVENT_RECHARGE_CONFIRMED) {
                $update['total_deposit'] = $this->raw('total_deposit + ' . $money);
            }
            $update['version'] = $this->raw('version+1');
            $res = $this->selector(['id'=>$walletObj->id,'version'=>$walletObj->version])->update($update);
            if(empty($res)){
                throw new VerifyException('更新用户钱包失败');
            }
            // 写入资金总账
            self::writeWalletLogs(
                $walletObj, $money, $event_type,
                $beforeBalance, $afterBalance, $beforeFrozen, $beforeFrozen,
                $ref_table, $ref_id, $descr,$admin_id, $is_record_hide
            );
            if ($ownTx) {
                $conn->commit();
            }
            Log::info("加钱成功: user={$userid} wallet={$wallet_type} event={$event_type} money={$money} before={$beforeBalance} after={$afterBalance}");
            return true;
        }
        catch (\Throwable $e) {
            if ($ownTx && $conn->transactionLevel() > 0) {
                $conn->rollBack();
            }
            Log::error("加钱失败: user={$userid} wallet={$wallet_type} money={$money} err=" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 扣减余额
     *
     * @param int    $userid
     * @param string $wallet_type
     * @param float  $money
     * @param string $event_type        语义事件名（默认 withdraw.confirmed）
     * @param string $descr
     * @param int    $admin_id
     * @param bool   $is_record_hide
     * @param string $ref_table
     * @param int    $ref_id
     */
    public function minusUserWallet(
        int    $userid,
        string $wallet_type,
        float  $money,
        string $event_type = self::EVENT_WITHDRAW_CONFIRMED,
        string $descr = '',
        string $ref_table = '',
        int    $ref_id = 0,
        ?int    $admin_id = 0,
        ?bool   $is_record_hide = false
    ): bool {
        if ($money <= 0) {
            Log::error("钱包扣钱失败：金额必须>0, user={$userid} wallet={$wallet_type} money={$money}");
            throw new VerifyException('金额必须大于0');
        }
        $conn = $this->connection();
        $ownTx = $conn->transactionLevel() === 0;
        try {
            if ($ownTx) {
                $conn->beginTransaction();
            }
            $walletObj     = $this->getUserWallet($userid, $wallet_type, true);
            $beforeBalance = (float)$walletObj->balance;
            $beforeFrozen  = (float)$walletObj->frozen;

            if ($beforeBalance < $money) {
                throw new VerifyException('钱包余额不足');
            }

            $afterBalance = $beforeBalance - $money;
            $afterFrozen  = $beforeFrozen;

            $update = [
                'balance'   => $afterBalance,
                'total_out' => $this->raw('total_out + ' . $money),
            ];
            // 提现确认：解除冻结 + 累加 total_withdraw
            if ($event_type === self::EVENT_WITHDRAW_CONFIRMED) {
                if ($beforeFrozen < $money) {
                    throw new VerifyException('冻结金额不足，请先冻结');
                }
                $afterFrozen = $beforeFrozen - $money;
                $update['frozen'] = $afterFrozen;
                $update['total_withdraw'] = $this->raw('total_withdraw + ' . $money);
            }
            $res = $this->selector(['id'=>$walletObj->id,'version'=>$walletObj->version])->update($update);
            if(empty($res)){
                throw new VerifyException('更新用户钱包失败');
            }
            // 写入资金总账
            self::writeWalletLogs(
                $walletObj, $money, $event_type,
                $beforeBalance, $afterBalance, $beforeFrozen, $afterFrozen,
                $ref_table, $ref_id, $descr,$admin_id,$is_record_hide
            );
            if ($ownTx) {
                $conn->commit();
            }
            Log::info("扣钱成功: user={$userid} wallet={$wallet_type} event={$event_type} money={$money} before={$beforeBalance} after={$afterBalance}");
            return true;
        }
        catch (\Throwable $e) {
            if ($ownTx && $conn->transactionLevel() > 0) {
                $conn->rollBack();
            }
            Log::error("扣钱失败: user={$userid} wallet={$wallet_type} money={$money} err=" . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 冻结余额（挂单/提现申请时调用）
     */
    public function addUserFrozen(
        int $userid,
        string $wallet_type,
        float $money,
        string $event_type = null,
        string $descr = '',
        string $ref_table = '',
        int    $ref_id = 0,
        ?int    $admin_id = 0,
        ?bool   $is_record_hide = false,
    ): bool
    {
        $walletObj = $this->getUserWallet($userid, $wallet_type, true);
        $available = (float)$walletObj->balance - (float)$walletObj->frozen;
        if ($available < $money) {
            throw new VerifyException('可用余额不足，无法冻结');
        }
        $beforeFrozen  = (float)$walletObj->frozen;
        $afterFrozen = $beforeFrozen + $money;
        $res = $walletObj->saveData(['frozen' => $this->raw('frozen + ' . $money)]);
        if($res && $event_type==self::EVENT_ARBITRAGE_LOCK){
            // 写入资金总账
            self::writeWalletLogs(
                $walletObj, $money, $event_type,
                $walletObj->balance, $walletObj->balance, $beforeFrozen, $afterFrozen,
                $ref_table, $ref_id, $descr,$admin_id,$is_record_hide
            );
        }
        return $res;
    }

    /**
     * 解冻余额（撤单/提现驳回时调用）
     */
    public function minuUserFrozen(
        int $userid,
        string $wallet_type,
        float $money,
        string $event_type = null,
        string $descr = '',
        string $ref_table = '',
        int    $ref_id = 0,
        ?int    $admin_id = 0,
        ?bool   $is_record_hide = false
    ): bool
    {
        $walletObj = $this->getUserWallet($userid, $wallet_type, true);
        $beforeFrozen  = (float)$walletObj->frozen;
        if ($beforeFrozen < $money) {
            throw new VerifyException('冻结金额不足，无法解冻');
        }
        $afterFrozen = $beforeFrozen - $money;
        $res = $walletObj->saveData(['frozen' => $this->raw('frozen - ' . $money)]);
        if($res && $event_type==self::EVENT_ARBITRAGE_UNLOCK){
            // 写入资金总账
            self::writeWalletLogs(
                $walletObj, $money, $event_type,
                $walletObj->balance, $walletObj->balance, $beforeFrozen, $afterFrozen,
                $ref_table, $ref_id, $descr,$admin_id,$is_record_hide
            );
        }
        return $res;
    }

    // ══════════════════════════════════════════════════════════
    //  资金流水
    // ══════════════════════════════════════════════════════════

    /**
     * 写资金总账（member_wallet_log）——仅追加，不可修改
     * addWalletLogs
     */
    private static function writeWalletLogs(
        UserWalletModel $walletObj,
        float        $money,
        string       $event_type,
        float        $balanceBefore,
        float        $balanceAfter,
        float        $frozenBefore,
        float        $frozenAfter,
        string       $refTable = '',
        int          $refId = 0,
        string       $remark = '',
        int          $admin_id=0,
        bool         $is_record_hide=false
    ): void {
        try {
            $walletLogDao = new UserWalletLogDao();
            $direction = self::EVENT_DIRECTIONS[$event_type] ?? 0;
            if($direction==0 && $balanceBefore!=$balanceAfter){
                if($balanceBefore<$balanceAfter){
                    $direction = 1;
                }
                else{
                    $direction = -1;
                }
            }
            $walletLogDao->create([
                'user_id'        => $walletObj->user_id,
                'wallet_id'      => $walletObj->id,
                'wallet_type'    => $walletObj->wallet_type,
                'event_type'     => $event_type,
                'ref_table'      => $refTable ?: '',
                'ref_id'         => $refId ?: 0,
                'direction'      => $direction,
                'amount'         => $money,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'frozen_before'  => $frozenBefore,
                'frozen_after'   => $frozenAfter,
                'remark'         => $remark,
                'admin_id'       =>$admin_id,
                'status'         => ($is_record_hide?0:1),
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // 总账写入失败不中断业务，但必须记录错误
            Log::error('[FundLedger] 写入失败: ' . $e->getMessage() . ' user=' . $walletObj->user_id . ' event_type=' . $event_type);
        }
    }

    public function getWalletLogList(int $user_id, string $wallet_type, array $params = [])
    {
        $walletObj = $this->getUserWallet($user_id, $wallet_type, false);
        if (empty($walletObj)) {
            return [];
        }
        $walletLogDao = new UserWalletLogDao();
        $where = [
            'user_id'=>$user_id,
            'wallet_id'=>$walletObj->id,
            'status'=>1,
        ];
        if (!empty($params['event_type'])) {
            $where['event_type'] = $params['event_type'];
        }
        if (!empty($params['ref_table'])) {
            $where['ref_table'] = $params['ref_table'];
        }
        $field = $walletLogDao->raw('id,direction,amount,balance_before,balance_after,frozen_before,frozen_after,event_type,create_at,status,remark');
        return $walletLogDao->paginateArray($where,['id'=>'desc'],[$field]);
    }
}
