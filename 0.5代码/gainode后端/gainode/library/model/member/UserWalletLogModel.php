<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $user_id 用户 ID
 * @property integer $wallet_id 关联 member_wallet.id
 * @property string $wallet_type 账户类型快照:Funding/Arbitrage/Integral
 * @property string $event_type 事件类型
 * @property string $ref_table 来源表名
 * @property integer $ref_id 来源记录 ID
 * @property integer $direction 1=收入 -1=支出 0=冻结变动
 * @property string $amount 发生金额（正值）
 * @property string $balance_before 变动前余额
 * @property string $balance_after 变动后余额
 * @property string $frozen_before 变动前冻结余额
 * @property string $frozen_after 变动后冻结余额
 * @property string $remark 备注
 * @property integer $admin_id 操作人
 * @property integer $created_time 创建时间
 * @property integer $status 状态(1:正常，0:隐藏)
 */
class UserWalletLogModel extends Model
{
    public $table = 'member_user_wallet_log';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $fields=[
		"id",
		"user_id",
		"wallet_id",
		"wallet_type",
		"event_type",
		"ref_table",
		"ref_id",
		"direction",
		"amount",
		"balance_before",
		"balance_after",
		"frozen_before",
		"frozen_after",
		"remark",
		"admin_id",
		"created_time",
		"status",
    ];

    protected $append = ['event_text'];

    public function getEventTextAttribute($value, $data): string
    {
        // 语义化事件名直接返回可读描述
        $map = [
            'recharge.confirmed'  => '充值到账',
            'recharge.reward'     => '充值奖励',
            'withdraw.requested'  => '提现申请',
            'withdraw.confirmed'  => '提现到账',
            'withdraw.rejected'   => '提现驳回',
            'transfer.in'         => '划转收入',
            'transfer.out'        => '划转转出',
            'reward.bonus'        => '奖励发放',
            'reward.rebate'       => '返佣',
            'account.frozen'      => '账户冻结',
            'account.adjusted'    => '后台调整',
        ];
        $event = $data['event_type'] ?? '';
        return $map[$event] ?? $event;
    }

    public function getAccountAttribute(){
        if(!empty($this->user_id) && $this->relationLoaded('user')){
            return $this->user->account ?? null;
        }
        if(!empty($this->user_id)){
            return $this->user()->value('account');
        }
        return null;
    }

    public function user()
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }

    public function wallet()
    {
        return $this->hasOne(UserWalletModel::class, 'id', 'wallet_id');
    }

    public function toM($wallet_type = null): array
    {
        $cdata = $this->toArray();
        return [
            'id'            =>$cdata['id'],
            'wallet_type'   => $wallet_type,
            'user_id'       => $cdata['user_id'],
            'event_type'    => $cdata['event_type'],
            'direction'     => $cdata['direction'] ?? ($cdata['type'] === 'add' ? 1 : -1),
            'amount'        => $cdata['amount'],
            'ref_table'      => $cdata['ref_table'],
            'ref_id'        => $cdata['ref_id'],
            'balance_before' => $cdata['before_money'],
            'balance_after'  => $cdata['after_money'],
            'frozen_before' => $cdata['frozen_before'] ?? 0,
            'frozen_after'  => $cdata['frozen_after'] ?? 0,
            'admin_id'      => $cdata['admin_id'],
            'remark'         => $cdata['remark'],
            'status'        =>$cdata['status']
        ];
    }
}
