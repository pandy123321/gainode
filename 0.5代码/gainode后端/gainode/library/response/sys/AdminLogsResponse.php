<?php

namespace library\response\sys;
use support\extend\Response;

class AdminLogsResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>""],
		"eid"       => ["type" => "integer",   "description"=>"企业ID(0:平台)"],
		"admin_id"    => ["type" => "integer",   "description"=>"管理员ID"],
		"account"       => ["type" => "string",   "description"=>"用户账号"],
		"token"       => ["type" => "string",   "description"=>"用户token"],
		"action"       => ["type" => "string",   "description"=>"用户行为"],
		"os"       => ["type" => "string",   "description"=>"操作系统"],
		"browser"       => ["type" => "string",   "description"=>"浏览器类型"],
		"client_ip"       => ["type" => "string",   "description"=>"客户端ip"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
    ];


    protected array $children = [
        'listItem' => ["id","eid","admin_id","account","token","action","os","browser","client_ip","descr","created_time"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail'  =>  ["id","eid","admin_id","account","token","action","os","browser","client_ip","descr","created_time"],
    ];
}
