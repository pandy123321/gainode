<?php

namespace library\response\api;
use support\extend\Response;

class ProjectOrderResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>""],
		"order_no"       => ["type" => "string",   "description"=>"订单编号"],
		"user_id"       => ["type" => "integer",   "description"=>"用户ID"],
		"project_id"       => ["type" => "integer",   "description"=>"项目ID"],
		"project_name"       => ["type" => "string",   "description"=>"项目名称"],
		"min_day_rate"       => ["type" => "string",   "description"=>"最低日利率"],
		"max_day_rate"       => ["type" => "string",   "description"=>"最高日利率"],
		"amount"       => ["type" => "string",   "description"=>"订单金额"],
		"fee"       => ["type" => "string",   "description"=>"交易税费"],
		"order_status"       => ["type" => "string",   "description"=>"订单状态(unpaid,pending,paid,refunded,completed,closed)"],
		"pay_method"       => ["type" => "string",   "description"=>"支付方式"],
		"pay_amount"       => ["type" => "string",   "description"=>"已付款金额"],
		"paid_at"       => ["type" => "string",   "description"=>"支付时间"],
		"tx_hash"       => ["type" => "string",   "description"=>"交易Hash"],
		"settle_amount"       => ["type" => "string",   "description"=>"结算金额"],
		"settle_cnt"       => ["type" => "integer",   "description"=>"累计结算次数"],
		"last_settle_time"       => ["type" => "string",   "description"=>"上次结息时间"],
		"is_default"       => ["type" => "integer",   "description"=>"是否默认"],
		"is_lock"       => ["type" => "integer",   "description"=>"是否锁住赎回"],
		"is_calc_money"       => ["type" => "integer",   "description"=>"是否计算用户业绩"],
		"descr"       => ["type" => "string",   "description"=>"备注"],
		"sort"       => ["type" => "integer",   "description"=>"排序"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"expires_at"       => ["type" => "string",   "description"=>"过期时间"],
		"cancel_at"       => ["type" => "string",   "description"=>"取消时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(4:已赎回, 3:已到期, 2:运营中, 1:待审核、0:已取消, -1:失败)"],
        "incomeMoney" => ["type"=>"object","description"=>'收益金额(all:所有累计,0:待领取,1:待执行,2已结算)']
    ];


    protected array $children = [
        'listItem' => ["id","order_no","user_id","project_id","project_name","min_day_rate","max_day_rate","amount","fee","order_status","pay_method","pay_amount","paid_at","settle_amount","settle_cnt","last_settle_time","is_default","descr","sort","created_time","updated_time","expires_at","cancel_at","status","incomeMoney"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'create'  =>  ["id","order_no","user_id","project_id","project_name","min_day_rate","max_day_rate","amount","fee","order_status","pay_method","pay_amount","paid_at","settle_amount","settle_cnt","last_settle_time","is_default","descr","sort","created_time","updated_time","expires_at","cancel_at","status"],
        'detail'  =>  ["id","order_no","user_id","project_id","project_name","min_day_rate","max_day_rate","amount","fee","order_status","pay_method","pay_amount","paid_at","settle_amount","settle_cnt","last_settle_time","is_default","descr","sort","created_time","updated_time","expires_at","cancel_at","status","incomeMoney"],
    ];
}
