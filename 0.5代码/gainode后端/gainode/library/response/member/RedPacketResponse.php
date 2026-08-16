<?php

namespace library\response\member;
use support\extend\Response;

class RedPacketResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"红包ID"],
		"packet_no"       => ["type" => "string",   "description"=>"红包编号"],
		"title"       => ["type" => "string",   "description"=>"红包标题"],
		"total_amount"       => ["type" => "string",   "description"=>"红包总金额"],
		"packet_count"       => ["type" => "integer",   "description"=>"红包数量"],
		"remain_count"       => ["type" => "integer",   "description"=>"剩余数量"],
		"remain_amount"       => ["type" => "string",   "description"=>"剩余金额"],
		"packet_type"       => ["type" => "integer",   "description"=>"1随机红包 2固定红包"],
		"status"       => ["type" => "integer",   "description"=>"0待领取,1领取中,2已领取完,3过期,4关闭"],
		"start_time"       => ["type" => "string",   "description"=>"开始时间"],
		"expire_time"       => ["type" => "string",   "description"=>"过期时间"],
		"admin_id"       => ["type" => "integer",   "description"=>"后台管理员"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间戳(Unix秒)"],
		"updated_time"       => ["type" => "integer",   "description"=>"更新时间戳(Unix秒)"],
    ];


    protected array $children = [
        'listItem' => ["id","packet_no","title","total_amount","packet_count","remain_count","remain_amount","packet_type","status","start_time","expire_time","admin_id","created_time","updated_time"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","packet_no","title","total_amount","packet_count","remain_count","remain_amount","packet_type","status","start_time","expire_time","admin_id","created_time","updated_time"],
        'update'  =>  ["id","packet_no","title","total_amount","packet_count","remain_count","remain_amount","packet_type","status","start_time","expire_time","admin_id","created_time","updated_time"],
        'detail'  =>  ["id","packet_no","title","total_amount","packet_count","remain_count","remain_amount","packet_type","status","start_time","expire_time","admin_id","created_time","updated_time"],
    ];
}
