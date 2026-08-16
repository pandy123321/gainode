<?php

namespace library\response\api;
use support\extend\Response;

class AccountResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"用户ID"],
		"eid"       => ["type" => "integer",   "description"=>"企业ID(0:平台)"],
		"user_no"       => ["type" => "string",   "description"=>"用户编号"],
		"account_type"       => ["type" => "string",   "description"=>"注册账号类型(email、mobile)"],
		"account"       => ["type" => "string",   "description"=>"登陆账号"],
		"password"       => ["type" => "string",   "description"=>"密码"],
		"pay_password"       => ["type" => "string",   "description"=>"支付密码"],
		"encrypt"       => ["type" => "string",   "description"=>"密钥"],
		"nickname"       => ["type" => "string",   "description"=>"昵称"],
		"first_name"       => ["type" => "string",   "description"=>"姓氏"],
		"last_name"       => ["type" => "string",   "description"=>"名字"],
		"email"       => ["type" => "string",   "description"=>"邮箱"],
		"phone"       => ["type" => "string",   "description"=>"手机号码"],
		"google_secret"       => ["type" => "string",   "description"=>"google验证码"],
		"sex"       => ["type" => "string",   "description"=>"性别(Male:男，Female:女，Other:其他)"],
		"avatar"       => ["type" => "string",   "description"=>"头像地址"],
		"birthday"       => ["type" => "string",   "description"=>"生日"],
		"country"       => ["type" => "string",   "description"=>"归属国家"],
		"user_type"       => ["type" => "integer",   "description"=>"用户类型(0:普通用户,1:代理商 )"],
		"is_verify"       => ["type" => "integer",   "description"=>"是否认证(0:未提交,1:待验证审核,2:审核通过,3:已拒绝)"],
		"is_agent"       => ["type" => "integer",   "description"=>"是否代理商"],
		"agent_id"       => ["type" => "integer",   "description"=>"所属代理商ID"],
		"telegram_id"       => ["type" => "integer",   "description"=>"飞机ID"],
		"login_cnt"       => ["type" => "integer",   "description"=>"登陆次数"],
		"client_ip"       => ["type" => "string",   "description"=>"IP地址"],
		"last_login_time"       => ["type" => "integer",   "description"=>"最后登陆时间"],
		"modify_pwd_time"       => ["type" => "integer",   "description"=>"修改密码时间"],
		"pwd_strong"       => ["type" => "integer",   "description"=>"密码强度(1:弱 2:中 3:强)"],
		"is_multiple_login"       => ["type" => "integer",   "description"=>"是否支持多端登录(0:不支持 1:支持)"],
		"is_frozen_withdraw"   => ["type" => "integer",   "description"=>"是否冻结提现(0:否,1:是)"],
		"is_arbitrage"   => ["type" => "integer",   "description"=>"是否开启套利任务(0:否,1:是)"],
		"level_id"       => ["type" => "integer",   "description"=>"会员等级ID"],
		"level_grade"       => ["type" => "integer",   "description"=>"等级序号"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"admin_id"       => ["type" => "integer",   "description"=>"所属员工ID"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:可用,0:停用,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","eid","user_no","account_type","account","password","pay_password","nickname","first_name","last_name","email","phone","google_secret","sex","avatar","birthday","country","user_type","is_verify","is_agent","agent_id","telegram_id","login_cnt","client_ip","last_login_time","modify_pwd_time","pwd_strong","is_multiple_login","level_id","level_grade","descr","admin_id","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'getUserInfo'  =>  ["id","user_no","account_type","account","password","pay_password","nickname","first_name","last_name","email","phone","google_secret","sex","avatar","birthday","country","user_type","is_verify","is_agent","agent_id","telegram_id","login_cnt","client_ip","last_login_time","modify_pwd_time","pwd_strong","is_multiple_login","is_frozen_withdraw","level_id","level_grade","descr","admin_id","created_time","updated_time","status"],
        'updateAvatar'=>  ["id","user_no","account_type","account","password","pay_password","nickname","first_name","last_name","email","phone","google_secret","sex","avatar","birthday","country","user_type","is_verify","is_agent","agent_id","telegram_id","login_cnt","client_ip","last_login_time","modify_pwd_time","pwd_strong","is_multiple_login","is_frozen_withdraw","level_id","level_grade","descr","admin_id","created_time","updated_time","status"],
    ];
}
