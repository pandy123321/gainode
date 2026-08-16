<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id 用户ID
 * @property integer $eid 企业ID(0:平台)
 * @property string $user_no 用户编号
 * @property string $account_type 注册账号类型(email:邮箱,mobile:手机)
 * @property string $account 登陆账号
 * @property string $password 密码
 * @property string $pay_password 支付密码
 * @property string $encrypt 密钥
 * @property string $nickname 昵称
 * @property string $first_name 姓氏
 * @property string $last_name 名字
 * @property string $email 邮箱
 * @property string $phone 手机号码
 * @property string $google_secret google验证码
 * @property string $sex 性别(Male:男，Female:女，Other:其他)
 * @property string $avatar 头像地址
 * @property string $birthday 生日
 * @property string $country 归属国家
 * @property integer $user_type 用户类型(0:普通用户,1:代理商 )
 * @property integer $is_verify 是否认证(0:未提交,1:待验证审核,2:审核通过,3:已拒绝)
 * @property integer $is_agent 是否代理商
 * @property integer $agent_id 所属代理商ID
 * @property integer $telegram_id 飞机ID
 * @property integer $login_cnt 登陆次数
 * @property string $client_ip IP地址
 * @property integer $last_login_time 最后登陆时间
 * @property integer $modify_pwd_time 修改密码时间
 * @property integer $pwd_strong 密码强度(1:弱 2:中 3:强)
 * @property integer $is_multiple_login 是否支持多端登录
 * @property integer $is_frozen_withdraw 是否冻结提现(0:否,1:是)
 * @property integer $is_arbitrage 是否开启套利任务(0:否,1:是)
 * @property integer $level_id 会员等级ID
 * @property integer $level_grade 等级序号
 * @property integer $rewards_cnt 奖励次数
 * @property string $descr 描述
 * @property integer $admin_id 所属员工ID
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:停用,-1:删除)
 */
class UserModel extends Model
{
    public $table = 'member_user';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"eid",
		"user_no",
		"account_type",
		"account",
		"password",
		"pay_password",
		"encrypt",
		"nickname",
		"first_name",
		"last_name",
		"email",
		"phone",
		"google_secret",
		"sex",
		"avatar",
		"birthday",
		"country",
		"user_type",
		"is_verify",
		"is_agent",
		"agent_id",
		"telegram_id",
		"login_cnt",
		"client_ip",
		"last_login_time",
		"modify_pwd_time",
		"pwd_strong",
		"is_multiple_login",
		"is_frozen_withdraw",
		"is_arbitrage",
		"level_id",
		"level_grade",
		"rewards_cnt",
		"descr",
		"admin_id",
		"created_time",
		"updated_time",
		"status",
    ];

    protected $hidden = [
        'password',
        'pay_password'
    ];

    public function team(){
        return $this->hasOne(UserTeamModel::class,'user_id','id');
    }

    public function auths(){
        return $this->hasMany(UserAuthModel::class,'user_id','id');
    }

    public function wallets(){
        return $this->hasMany(UserWalletModel::class,'user_id','id');
    }

    public function toM(){
        $data = [
            'id'=>$this->id,
            'user_no'=>$this->user_no,
            'account'=>$this->account,
            'nickname'=>$this->nickname,
            'first_name'=>$this->first_name,
            'last_name'=>$this->last_name,
            'email'=>$this->email,
            'phone'=>$this->phone,
            'sex'=>$this->sex,
            'avatar'=>$this->avatar,
            'birthday'=>$this->birthday,
            'country'=>$this->country,
            'user_type'=>$this->user_type,
            'is_verify'=>$this->is_verify,
            'is_agent'=>$this->is_agent,
            'telegram_id'=>$this->telegram_id,
            'client_ip'=>$this->client_ip,
            'last_login_time'=>$this->last_login_time,
            'modify_pwd_time'=>$this->modify_pwd_time,
            'pwd_strong'=>$this->pwd_strong,
            'is_arbitrage'=>$this->is_arbitrage,
            'level_id'=>$this->level_id,
            'level_grade'=>$this->level_grade,
            'descr'=>$this->descr,
            'created_time'=>$this->getDateTime('created_time')
        ];
        return $data;
    }
}
