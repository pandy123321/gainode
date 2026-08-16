<?php

namespace library\validator\member;
use support\extend\Validator;

class RechargeOrderValidation extends Validator{

    // 定义规则
    public $rules =   [
		"order_no"=>"string",
		"user_id"=>"integer",
		"network"=>"required|string",
		"address"=>"required|string",
		"from_address"=>"required|string",
		"currency"=>"required|string",
		"money"=>"required|numeric",
		"reward_money"=>"required|numeric",
		"fee"=>"required|string",
		"tx_hash"=>"required|string",
		"confirmations"=>"required|integer",
		"required_confirmations"=>"required|integer",
		"chain_data"=>"required|string",
		"actual_amount"=>"required|numeric",
		"order_status"=>"required|string",
		"admin_id"=>"required|integer",
		"source"=>"required|integer",
		"descr"=>"required|string",
		"credited_time"=>"required|integer",
		"retry_count"=>"required|integer",
		"status"=>"required|integer",
        "user_no"=>"string",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"order_no"=>"充值流水号",
		"user_id"=>"用户ID",
		"network"=>"充值网络: TRC20/ERC20/BEP20",
		"address"=>"充值地址",
		"from_address"=>"用户发币钱包地址",
		"currency"=>"充值币种",
		"money"=>"充值金额",
		"reward_money"=>"充值赠送",
		"fee"=>"手续费",
		"tx_hash"=>"交易hash",
		"confirmations"=>"当前链上确认数",
		"required_confirmations"=>"所需确认数",
		"chain_data"=>"链上原始回执数据",
		"actual_amount"=>"实际到账",
		"order_status"=>"状态: submitted/confirming/completed/failed/rejected/closed",
		"admin_id"=>"后台操作人员",
		"source"=>"来源(0:后台新增,1:用户提交,2:链上监听)",
		"descr"=>"描述",
		"credited_time"=>"实际到账时间",
		"retry_count"=>"调用API次数",
		"status"=>"状态(-1:已删除,0:隐藏,1:待处理,2:已完成)",
		"user_no"=>"用户编号",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'list'  =>  ["order_status","order_no","user_id","user_no"],
        'create'  =>  ["network","address","money","currency","tx_hash"],
    ];
}
