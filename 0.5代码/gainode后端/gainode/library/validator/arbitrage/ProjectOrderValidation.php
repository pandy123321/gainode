<?php

namespace library\validator\arbitrage;
use support\extend\Validator;

class ProjectOrderValidation extends Validator{

    // 定义规则
    public $rules =   [
		"order_no"=>"required|string",
		"user_id"=>"required|integer",
		"project_id"=>"required|integer",
		"project_name"=>"required|string",
		"min_day_rate"=>"required|numeric",
		"max_day_rate"=>"required|numeric",
		"amount"=>"required|numeric",
		"fee"=>"required|numeric",
		"order_status"=>"required|string",
		"pay_method"=>"required|string",
		"pay_amount"=>"required|numeric",
		"paid_at"=>"required|string",
		"tx_hash"=>"required|string",
		"settle_amount"=>"required|numeric",
		"settle_cnt"=>"required|integer",
		"last_settle_time"=>"required|string",
		"is_lock"=>"required|integer",
		"is_calc_money"=>"required|integer",
		"descr"=>"required|string",
		"sort"=>"required|integer",
		"expires_at"=>"required|string",
		"cancel_at"=>"required|string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"order_no"=>"订单编号",
		"user_id"=>"用户ID",
		"project_id"=>"项目ID",
		"project_name"=>"项目名称",
		"min_day_rate"=>"最低日利率",
		"max_day_rate"=>"最高日利率",
		"amount"=>"订单金额",
		"fee"=>"交易税费",
		"order_status"=>"订单状态(unpaid,pending,partial,paid,refunded,completed,closed)",
		"pay_method"=>"支付方式",
		"pay_amount"=>"已付款金额",
		"paid_at"=>"支付时间",
		"tx_hash"=>"交易Hash",
		"settle_amount"=>"结算金额",
		"settle_cnt"=>"累计结算次数",
		"last_settle_time"=>"上次结息时间",
		"is_lock"=>"是否锁住赎回",
		"is_calc_money"=>"是否计算用户业绩",
		"descr"=>"备注",
		"sort"=>"排序",
		"expires_at"=>"过期时间",
		"cancel_at"=>"取消时间",
		"status"=>"状态(4:已赎回, 3:已到期, 2:运营中, 1:待审核、0:已取消, -1:失败)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'create'  =>  ['project_id'],
    ];
}
