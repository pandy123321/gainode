<?php

namespace library\service\member;

use library\model\arbitrage\ProjectOrderModel;
use library\model\member\OrderRecordModel;
use library\dao\member\OrderRecordDao;
use library\model\member\RechargeOrderModel;
use library\model\member\TransferOrderModel;
use library\model\member\WithdrawOrderModel;
use support\extend\Log;
use support\extend\Service;

/**
 * Service
 * @method OrderRecordModel create($data)
 * @method OrderRecordModel updateOrCreate(array $params,array $data)
 * @method OrderRecordModel update($id,array $data){
 * @method OrderRecordModel get($id,string $field = null)
 * @method OrderRecordModel find($id)
 * @method OrderRecordModel findOrFail($id)
 * @method OrderRecordModel firstOrCreate(array $params,array $data)
 * @method OrderRecordModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class OrderRecordService extends Service
{
    public function __construct()
    {
        $this->dao = OrderRecordDao::class;
        parent::__construct();
    }

    /**
     * 写入一条订单记录
     * @param int    $user_id
     * @param string $type       recharge / withdraw / transfer / profit
     * @param float  $amount     金额（正值）
     * @param string $currency   USDT / BTC ...
     * @param string $from_acc   来源端标识（网络/钱包类型/地址）
     * @param string $to_acc     目标端标识
     * @param string $descr      描述
     * @param string $ref_table  来源业务表（member_recharge_order ...）
     * @param int    $ref_id     来源记录主键
     * @param int    $status     初始状态，默认 pending
     * @return OrderRecordModel|null
     */
    public function writeRecord(
        int    $user_id,
        string $type,
        float  $amount,
        string $currency  = 'USDT',
        string $from_acc  = '',
        string $to_acc    = '',
        string $descr     = '',
        string $ref_table = '',
        int    $ref_id    = 0,
        string  $status    = OrderRecordModel::STATUS_PENDING
    ): ?OrderRecordModel {
        try {
            return $this->create([
                'user_id'   => $user_id,
                'type'      => $type,
                'amount'    => $amount,
                'currency'  => $currency,
                'from_acc'  => $from_acc,
                'to_acc'    => $to_acc,
                'descr'     => $descr,
                'ref_table' => $ref_table,
                'ref_id'    => $ref_id,
                'status'    => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error("[OrderRecord] write 失败: user={$user_id} type={$type} err=" . $e->getMessage());
            return null;
        }
    }

    /**
     * 通过 ref_table + ref_id 更新订单记录状态
     * @param string $ref_table  来源业务表
     * @param int    $ref_id     来源记录主键
     * @param int    $status     新状态
     * @param string $descr      可选：更新描述
     * @return bool
     */
    public function updateByRef(string $ref_table, int $ref_id,string $status,string $descr = ''): bool
    {
        try {
            $update = ['status' => $status];
            if ($descr !== '') {
                $update['descr'] = $descr;
            }
            return $this->updateAll(['ref_table'=>$ref_table,'ref_id'=>$ref_id],$update);
        }
        catch (\Throwable $e) {
            Log::error("[OrderRecord] updateByRef 失败: {$ref_table}#{$ref_id} err=" . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取用户订单记录（分页）
     * @param int   $user_id
     * @param array $params {
     *   type?    string  recharge / withdraw / transfer / profit
     *   status?  string  状态(0:待处理,1:已完成,2:失败,-1:删除)
     * }
     */
    public function getUserRecords(int $user_id, array $params = []): array
    {
        $where = ['user_id'=>$user_id];
        if (!empty($params['type'])) {
            $where['type'] = $params['type'];
        }
        if (isset($params['status']) && is_numeric($params['status'])) {
            $where['status'] = $params['status'];
        }
        return $this->paginateArray($where);
    }

    public function writeRecharge(RechargeOrderModel $orderObj,$status=null): ?OrderRecordModel {
        return $this->writeRecord(
            $orderObj->user_id,
            (string)OrderRecordModel::TYPE_RECHARGE,
            (float)$orderObj->money,
            (string)$orderObj->currency,
            $orderObj->from_address,
            $orderObj->address,
            ($orderObj->descr ?: "{$orderObj->network} 充值待确认"),
            'member_recharge_order',
            (int)$orderObj->id,
            $status ?? OrderRecordModel::STATUS_PENDING
        );
    }

    /** 提现 — 创建 pending 记录 */
    public function writeWithdraw(WithdrawOrderModel $orderObj,$status=null): ?OrderRecordModel {
        return $this->writeRecord(
            $orderObj->user_id,
            (string)OrderRecordModel::TYPE_WITHDRAW,
            (float)$orderObj->money,
            (string)$orderObj->currency,
            (string)$orderObj->type,
            $orderObj->address,
            $orderObj->descr ?: "{$orderObj->type} 提现申请",
            'member_withdraw_order',
            (int)$orderObj->id,
            $status ?? OrderRecordModel::STATUS_PENDING
        );
    }

    public function writeProject(ProjectOrderModel $orderObj,$status=null): ?OrderRecordModel {
        return $this->writeRecord(
            (int)$orderObj->user_id,
            (string)OrderRecordModel::TYPE_PROJECT,
            (float)$orderObj->pay_amount,
            "USDT",
            '',
            '',
            (string)($orderObj->descr ?? ''),
            'arbitrage_project_order',
            (int)$orderObj->id,
            $status ?? OrderRecordModel::STATUS_SUCCESS
        );
    }

    public function writeTransfer(TransferOrderModel $orderObj,$status=null): ?OrderRecordModel {
        return self::writeRecord(
            $orderObj->user_id,
            (string)OrderRecordModel::TYPE_TRANSFER,
            (float)$orderObj->amount,
            (string)$orderObj->currency,
            $orderObj->from_wallet,
            $orderObj->to_wallet,
            ($orderObj->remark ?: "{$orderObj->from_wallet} → {$orderObj->to_wallet}"),
            'member_transfer_order',
            (int)$orderObj->id,
            $status ?? OrderRecordModel::STATUS_PENDING
        );
    }

    /** 收益 — 直接成功记录 */
    public function writeProfit(
        int    $user_id,
        float  $amount,
        string $wallet_type,
        string $currency = 'USDT',
        string $descr    = '',
        string $ref_table = '',
        int    $ref_id    = 0
    ): ?OrderRecordModel {
        return self::writeRecord(
            $user_id,
            OrderRecordModel::TYPE_PROFIT,
            $amount,
            $currency,
            $wallet_type,
            $wallet_type,
            $descr,
            $ref_table,
            $ref_id,
            OrderRecordModel::STATUS_SUCCESS
        );
    }
}
