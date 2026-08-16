<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $user_id 用户ID
 * @property string $account 用户账号
 * @property string $invite_code 邀请码
 * @property integer $parent_id 上级邀请人ID
 * @property integer $parent_level 上级层级
 * @property string $parent_path 上级邀请节点
 * @property string $invite_path 下级邀请节点
 * @property integer $invite_cnt 直推人数
 * @property string $invite_income_money 直推收益金额
 * @property string $invite_money 直推业绩
 * @property string $invite_paid_money 直推支付金额
 * @property integer $team_cnt 团队人数
 * @property string $team_income_money 团队收益金额
 * @property string $team_money 团队业绩
 * @property string $team_paid_money 团队支付金额
 * @property integer $order_cnt 订单数量
 * @property string $order_money 消费金额
 * @property string $invite_order_money 直退消费金额
 * @property string $team_order_money 团队消费金额
 * @property string $total_fee 累计手续费
 * @property string $team_income_fee 团队手续费收益
 * @property string $reward 邀请奖励金
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 */
class UserTeamModel extends Model
{
    public $table = 'member_user_team';
    public $primaryKey = 'user_id';
    public $connection = 'mysql';

    public $fields=[
		"user_id",
		"account",
		"invite_code",
		"parent_id",
		"parent_level",
		"parent_path",
		"invite_path",
		"invite_cnt",
		"invite_income_money",
		"invite_money",
		"invite_paid_money",
		"team_cnt",
		"team_income_money",
		"team_money",
		"team_paid_money",
		"order_cnt",
		"order_money",
		"invite_order_money",
		"team_order_money",
		"total_fee",
		"team_income_fee",
		"reward",
		"created_time",
		"updated_time",
    ];

    protected $appends = ['user_no'];

    public function getUserNoAttribute(){
        if(!empty($this->user_id) && $this->relationLoaded('user')){
            return $this->user->user_no ?? null;
        }
        if(!empty($this->user_id)){
            return $this->user()->value('user_no');
        }
        return null;
    }

    public function user()
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }
}
