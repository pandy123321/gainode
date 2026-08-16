<?php

namespace library\response\api;
use support\extend\Response;

class WithdrawResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],
        'report'     => ['type' => 'array',  'description' => '统计数据'],

		"id"       => ["type" => "integer",   "description"=>""],
		"order_no"       => ["type" => "string",   "description"=>"提现流水号"],
		"user_id"       => ["type" => "integer",   "description"=>"用户ID"],
		"type"       => ["type" => "string",   "description"=>"提现类型"],
		"currency"       => ["type" => "string",   "description"=>"提现币种(USDT)"],
		"money"       => ["type" => "string",   "description"=>"申请提现金额"],
		"fee"       => ["type" => "string",   "description"=>"手续费"],
		"actual_amount"       => ["type" => "string",   "description"=>"实际到账 = money - fee"],
		"address"       => ["type" => "string",   "description"=>"目标收款地址"],
		"risk_score"       => ["type" => "integer",   "description"=>"风控评分 0-100，>70 需人工审核"],
		"tx_hash"       => ["type" => "string",   "description"=>"交易hash"],
		"retry_count"       => ["type" => "integer",   "description"=>"调用次数"],
		"order_status"       => ["type" => "string",   "description"=>"状态: requested/approved/rejected/broadcasting/completed/failed/closed"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"review_admin_id"       => ["type" => "integer",   "description"=>"审核管理员ID"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"approved_time"       => ["type" => "integer",   "description"=>"审核通过时间"],
		"broadcasted_time"       => ["type" => "integer",   "description"=>"链上广播时间"],
		"confirmed_time"       => ["type" => "integer",   "description"=>"链上确认时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(-1:已删除,0:隐藏,1:待处理,2:已完成)"],
        "is_open"       => ["type" => "integer",   "description"=>"是否开启提现(0:不开启,1:开启)"],
        "min_money"       => ["type" => "float",   "description"=>"最低提现金额"],
        "max_money"       => ["type" => "float",   "description"=>"最高提现金额"],
        "withdraw_rate"       => ["type" => "integer",   "description"=>"提现手续费率"],
    ];


    protected array $children = [
        'listItem' => ["id","order_no","user_id","type","currency","money","fee","actual_amount","address","risk_score","tx_hash","retry_count","order_status","descr","review_admin_id","created_time","updated_time","approved_time","broadcasted_time","confirmed_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data','report'],
        'lists'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail'  =>  ["id","order_no","user_id","type","currency","money","fee","actual_amount","address","risk_score","tx_hash","retry_count","order_status","descr","review_admin_id","created_time","updated_time","approved_time","broadcasted_time","confirmed_time","status"],
        'config'  =>  ["is_open","min_money","max_money","withdraw_rate","descr"],
    ];
}
