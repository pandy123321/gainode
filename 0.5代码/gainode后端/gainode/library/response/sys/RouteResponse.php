<?php

namespace library\response\sys;
use support\extend\Response;

class RouteResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>""],
		"key"       => ["type" => "string",   "description"=>"路由KEY"],
		"module"       => ["type" => "string",   "description"=>"模块"],
		"controller"       => ["type" => "string",   "description"=>"控制器"],
		"action"       => ["type" => "string",   "description"=>"操作"],
		"method"       => ["type" => "string",   "description"=>"请求类型"],
		"plugins"       => ["type" => "string",   "description"=>"插件"],
		"url"       => ["type" => "string",   "description"=>"URL地址"],
		"path"       => ["type" => "string",   "description"=>"文件类路径"],
		"middleware"       => ["type" => "string",   "description"=>"应用的中间件"],
		"verify"       => ["type" => "integer",   "description"=>"验证权限(0:不需要登陆,1:需要登陆,2:需要登陆和权限,3:仅限超管访问)"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"status"       => ["type" => "integer",   "description"=>"是否加入菜单表(0:未加入,1:已加入)"],
    ];


    protected array $children = [
        'listItem' => ["id","key","module","controller","action","method","plugins","url","path","middleware","verify","created_time","updated_time","descr","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'all'  =>  ["key","url","descr"],
        'detail'  =>  ["id","key","module","controller","action","method","plugins","url","path","middleware","verify","created_time","updated_time","descr","status"],
    ];
}
