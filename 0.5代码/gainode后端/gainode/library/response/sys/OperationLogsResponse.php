<?php

namespace library\response\sys;
use support\extend\Response;

class OperationLogsResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"ID"],
		"module"       => ["type" => "string",   "description"=>"模块类型"],
		"user_id"       => ["type" => "integer",   "description"=>"操作人"],
		"request_url"       => ["type" => "string",   "description"=>"访问URL"],
		"request_method"       => ["type" => "string",   "description"=>"请求类型"],
		"request_data"       => ["type" => "string",   "description"=>"请求的数据"],
		"request_date"       => ["type" => "string",   "description"=>"记录日期"],
		"refer_url"       => ["type" => "string",   "description"=>"来源URL"],
		"client_ip"       => ["type" => "string",   "description"=>"访问IP"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
    ];


    protected array $children = [
        'listItem' => ["id","module","user_id","request_url","request_method","request_data","request_date","refer_url","client_ip","created_time"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail'  =>  ["id","module","user_id","request_url","request_method","request_data","request_date","refer_url","client_ip","created_time"],
    ];
}
