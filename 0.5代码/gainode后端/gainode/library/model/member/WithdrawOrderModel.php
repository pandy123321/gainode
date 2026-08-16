<?php

namespace library\model\member;

use Illuminate\Database\Eloquent\Builder;
use support\extend\Model;

/**
 * @property integer $id
 * @property string $order_no 提现流水号
 * @property integer $user_id 用户ID
 * @property string $type 提现类型
 * @property string $currency 提现币种(USDT)
 * @property string $money 申请提现金额
 * @property string $fee 手续费
 * @property string $actual_amount 实际到账 = money - fee
 * @property string $address 目标收款地址
 * @property integer $risk_score 风控评分 0-100，>70 需人工审核
 * @property string $tx_hash 交易hash
 * @property integer $retry_count 调用次数
 * @property string $order_status 状态: requested/approved/rejected/broadcasting/completed/failed/closed
 * @property string $descr 描述
 * @property integer $review_admin_id 审核管理员ID
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $approved_time 审核通过时间
 * @property integer $broadcasted_time 链上广播时间
 * @property integer $confirmed_time 链上确认时间
 * @property integer $status 状态(-1:已删除,0:隐藏,1:待处理,2:已完成)
 */
class WithdrawOrderModel extends Model
{
    public $table = 'member_withdraw_order';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BROADCASTING = 'broadcasting';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CLOSED = 'closed';

    public $fields=[
		"id",
		"order_no",
		"user_id",
		"type",
		"currency",
		"money",
		"fee",
		"actual_amount",
		"address",
		"risk_score",
		"tx_hash",
		"retry_count",
		"order_status",
		"descr",
		"review_admin_id",
		"created_time",
		"updated_time",
		"approved_time",
		"broadcasted_time",
		"confirmed_time",
		"status",
    ];

    protected $append = ['status_processing'];

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

    public function getStatusProcessingAttribute($value, $data): string
    {
        if(in_array($this->order_status,['rejected','failed','closed'])){
            return 'failed';
        }
        elseif($this->order_status=='completed'){
            return 'completed';
        }
        return 'pending';
    }

    public function getAccountAttribute($value, $data): string
    {
        return $this->user()->value('account');
    }

    public function getLinkUrlAttribute($value, $data){
        if($this->type=='TRC20'){
            return 'https://tronscan.org/#/transaction/'.$this->tx_hash;
        }
        elseif($this->type=='ERC20'){
            return 'https://etherscan.io/tx/'.$this->tx_hash;
        }
        else{
            return 'https://bscscan.com/tx/'.$this->tx_hash;
        }
    }

    public function getMaskHashAttribute($value){
        if(!empty($this->tx_hash) && preg_match('/^(\w{8}).*(\w{8})$/', $this->tx_hash,$match)){
            return $match[1].'******'.$match[2];
        }
        return $this->tx_hash;
    }

    public function user(){
        return $this->hasOne(UserModel::class,'id','user_id');
    }
}
