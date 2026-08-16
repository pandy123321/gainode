<?php

namespace library\response\api;
use support\extend\Response;

class RechargeResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],
        'report'     => ['type' => 'array',  'description' => '统计数据'],

		"id"       => ["type" => "integer",   "description"=>""],
		"order_no"       => ["type" => "string",   "description"=>"充值流水号"],
		"user_id"       => ["type" => "integer",   "description"=>"用户ID"],
		"network"       => ["type" => "string",   "description"=>"充值网络: TRC20/ERC20/BEP20"],
		"address"       => ["type" => "string",   "description"=>"充值地址"],
		"from_address"       => ["type" => "string",   "description"=>"用户发币钱包地址"],
		"currency"       => ["type" => "string",   "description"=>"充值币种"],
		"money"       => ["type" => "string",   "description"=>"充值金额"],
		"reward_money"       => ["type" => "string",   "description"=>"充值赠送"],
		"fee"       => ["type" => "string",   "description"=>"手续费"],
		"tx_hash"       => ["type" => "string",   "description"=>"交易hash"],
		"confirmations"       => ["type" => "integer",   "description"=>"当前链上确认数"],
		"required_confirmations"       => ["type" => "integer",   "description"=>"所需确认数"],
		"chain_data"       => ["type" => "string",   "description"=>"链上原始回执数据"],
		"actual_amount"       => ["type" => "string",   "description"=>"实际到账"],
		"order_status"       => ["type" => "string",   "description"=>"状态: submitted/confirming/completed/failed/rejected/closed"],
		"admin_id"       => ["type" => "integer",   "description"=>"后台操作人员"],
		"source"       => ["type" => "integer",   "description"=>"来源(0:后台新增,1:用户提交,2:链上监听)"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"credited_time"       => ["type" => "integer",   "description"=>"实际到账时间"],
		"retry_count"       => ["type" => "integer",   "description"=>"调用API次数"],
		"status"       => ["type" => "integer",   "description"=>"状态(-1:已删除,0:隐藏,1:待处理,2:已完成)"],
		"is_open"       => ["type" => "integer",   "description"=>"是否开启充值(0:不开启,1:开启)"],
		"min_money"       => ["type" => "float",   "description"=>"最低充值金额(0:不开启,1:开启)"],
		"max_money"       => ["type" => "float",   "description"=>"最高充值金额(0:不开启,1:开启)"],
    ];


    protected array $children = [
        'listItem' => ["id","order_no","user_id","network","address","from_address","currency","money","reward_money","fee","tx_hash","confirmations","required_confirmations","chain_data","actual_amount","order_status","admin_id","source","descr","created_time","updated_time","credited_time","retry_count","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data','report'],
        'lists'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail'  =>  ["id","order_no","user_id","network","address","from_address","currency","money","reward_money","fee","tx_hash","confirmations","required_confirmations","chain_data","actual_amount","order_status","admin_id","source","descr","created_time","updated_time","credited_time","retry_count","status"],
        'config'  =>  ["is_open","min_money","max_money","descr"],
    ];
}
