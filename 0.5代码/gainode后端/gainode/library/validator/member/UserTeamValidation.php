<?php

namespace library\validator\member;
use support\extend\Validator;

class UserTeamValidation extends Validator{

    // 定义规则
    public $rules =   [
        'user_id' => 'required|integer',
		"account"=>"required|string",
		"invite_code"=>"required|string",
		"parent_id"=>"required|integer",
		"parent_level"=>"required|integer",
		"parent_path"=>"required|string",
		"invite_path"=>"required|string",
		"invite_cnt"=>"required|integer",
		"invite_income_money"=>"required|string",
		"invite_money"=>"required|string",
		"invite_paid_money"=>"required|string",
		"team_cnt"=>"required|integer",
		"team_income_money"=>"required|string",
		"team_money"=>"required|string",
		"team_paid_money"=>"required|string",
		"order_cnt"=>"required|integer",
		"order_money"=>"required|string",
		"team_order_money"=>"required|string",
		"total_fee"=>"required|string",
		"team_income_fee"=>"required|string",
		"reward"=>"required|string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"user_id"=>"用户ID",
		"account"=>"用户账号",
		"invite_code"=>"邀请码",
		"parent_id"=>"上级邀请人ID",
		"parent_level"=>"上级层级",
		"parent_path"=>"上级邀请节点",
		"invite_path"=>"下级邀请节点",
		"invite_cnt"=>"直推人数",
		"invite_income_money"=>"直推收益金额",
		"invite_money"=>"直推业绩",
		"invite_paid_money"=>"直推支付金额",
		"team_cnt"=>"团队人数",
		"team_income_money"=>"团队收益金额",
		"team_money"=>"团队业绩",
		"team_paid_money"=>"团队支付金额",
		"order_cnt"=>"订单数量",
		"order_money"=>"消费金额",
		"team_order_money"=>"团队消费金额",
		"total_fee"=>"累计手续费",
		"team_income_fee"=>"团队手续费收益",
		"reward"=>"邀请奖励金",
		"status"=>"状态(1:可用)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [];
}
