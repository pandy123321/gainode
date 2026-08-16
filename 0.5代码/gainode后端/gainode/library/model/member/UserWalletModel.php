<?php

namespace library\model\member;

use Illuminate\Database\Eloquent\Builder;
use support\extend\Model;

/**
 * @property integer $id ID
 * @property integer $user_id 用户id
 * @property string $wallet_type 账户类型: Funding/Arbitrage/Integral
 * @property string $balance 可用余额
 * @property string $frozen 冻结金额
 * @property string $total_deposit 累计充值入账
 * @property string $total_trade 累计交易划出
 * @property string $total_withdraw 累计提现
 * @property string $total_in 历史累计所有入账
 * @property string $total_out 历史累计所有出账
 * @property integer $sort 排序值
 * @property integer $version 版本号
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:冻结)
 */
class UserWalletModel extends Model
{
    public $table = 'member_user_wallet';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public const TYPE_FUNDING = 'Funding';
    public const TYPE_ARBITRAGE = 'Arbitrage';
    public const TYPE_INTEGRAL = 'Integral';

    public $fields=[
		"id",
		"user_id",
		"wallet_type",
		"balance",
		"frozen",
		"total_deposit",
		"total_trade",
		"total_withdraw",
		"total_in",
		"total_out",
		"sort",
		"version",
		"created_time",
		"updated_time",
		"status",
    ];

    /**
     * 可用余额 = balance - frozen
     */
    public function getAvailable(): float
    {
        $avail = (float)$this->balance - (float)$this->frozen;
        return max(0.0, round($avail, 8));
    }

    public function searchIsArbitrageAttr(Builder $selector,$value=1){
        return $selector->whereHas('user',function($query) use($value){
            $query->where('is_arbitrage',$value)->where('status',1);
        });
    }

    public function searchUserNoAttr(Builder $selector,$value){
        return $selector->whereHas('user',function($query) use($value){
            $query->where('user_no',$value);
        });
    }

    public function user()
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }

    /**
     * 序列化供队列/API 使用
     */
    public function toM(): array
    {
        $data = $this->toArray();
        $data['available'] = $this->getAvailable();
        return $data;
    }
}
