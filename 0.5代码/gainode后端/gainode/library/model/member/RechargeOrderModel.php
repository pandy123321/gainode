<?php

namespace library\model\member;

use Illuminate\Database\Eloquent\Builder;
use support\extend\Model;

/**
 * @property integer $id
 * @property string $order_no 充值流水号
 * @property integer $user_id 用户ID
 * @property string $network 充值网络: TRC20/ERC20/BEP20
 * @property string $address 充值地址
 * @property string $from_address 用户发币钱包地址
 * @property string $currency 充值币种
 * @property string $money 充值金额
 * @property string $reward_money 充值赠送
 * @property string $fee 手续费
 * @property string $tx_hash 交易hash
 * @property integer $confirmations 当前链上确认数
 * @property integer $required_confirmations 所需确认数
 * @property string $chain_data 链上原始回执数据
 * @property string $actual_amount 实际到账
 * @property string $order_status 状态: submitted/confirming/completed/failed/rejected/closed
 * @property integer $admin_id 后台操作人员
 * @property integer $source 来源(0:后台新增,1:用户提交,2:链上监听)
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $credited_time 实际到账时间
 * @property integer $retry_count 调用API次数
 * @property integer $status 状态(-1:已删除,0:隐藏,1:待处理,2:已完成)
 */
class RechargeOrderModel extends Model
{
    public $table = 'member_recharge_order';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CONFIRMING = 'confirming';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLOSED = 'closed';

    public $fields=[
		"id",
		"order_no",
		"user_id",
		"network",
		"address",
		"from_address",
		"currency",
		"money",
		"reward_money",
		"fee",
		"tx_hash",
		"confirmations",
		"required_confirmations",
		"chain_data",
		"actual_amount",
		"order_status",
		"admin_id",
		"source",
		"descr",
		"created_time",
		"updated_time",
		"credited_time",
		"retry_count",
		"status",
    ];

    /**
     * @param Builder $selector
     * @param $value
     * @return Builder
     */
    public function searchUserNoAttr(Builder $selector,$value){
        return $selector->whereHas('user',function($query) use($value){
            $query->where('user_no',$value);
        });
    }

    public function user()
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }
}
