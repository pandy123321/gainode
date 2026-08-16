<?php

namespace library\validator\member;
use support\extend\Validator;

class WithdrawOrderValidation extends Validator{

    // 定义规则
    public $rules =   [
		"id"=>"required|integer",
		"order_no"=>"string",
		"user_id"=>"integer",
		"type"=>"required|string",
		"currency"=>"required|string",
		"money"=>"required|numeric",
		"fee"=>"required|string",
		"actual_amount"=>"required|numeric",
		"address"=>"required|string",
		"risk_score"=>"required|integer",
		"tx_hash"=>"required|string",
		"retry_count"=>"required|integer",
		"order_status"=>"required|string",
		"descr"=>"string",
		"review_admin_id"=>"required|integer",
		"approved_time"=>"required|integer",
		"broadcasted_time"=>"required|integer",
		"confirmed_time"=>"required|integer",
		"status"=>"required|integer",
		"user_no"=>"string",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"id"=>"提现ID",
		"order_no"=>"提现流水号",
		"user_id"=>"用户ID",
		"type"=>"提现类型",
		"currency"=>"提现币种",
		"money"=>"申请提现金额",
		"fee"=>"手续费",
		"actual_amount"=>"实际到账 = money - fee",
		"address"=>"目标收款地址",
		"risk_score"=>"风控评分 0-100，>70 需人工审核",
		"tx_hash"=>"交易hash",
		"retry_count"=>"调用次数",
		"order_status"=>"状态: requested/approved/rejected/broadcasting/completed/failed/closed",
		"descr"=>"描述",
		"review_admin_id"=>"审核管理员ID",
		"approved_time"=>"审核通过时间",
		"broadcasted_time"=>"链上广播时间",
		"confirmed_time"=>"链上确认时间",
		"status"=>"状态(-1:已删除,0:隐藏,1:待处理,2:已完成)",
        "user_no"=>"用户编号",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'list'  =>  ["order_status","order_no","user_id","user_no"],
        'create'  =>  ['type','money','currency','address','descr'],
        'verify' => ['order_status','descr']
    ];
}
