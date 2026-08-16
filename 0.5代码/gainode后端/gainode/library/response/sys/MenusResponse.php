<?php

namespace library\response\sys;
use support\extend\Response;

class MenusResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],
        'children'  => ['type' => 'array',  'description' => '子集列表', 'children' => 'childItem'],

		"id"       => ["type" => "integer",   "description"=>""],
		"platform"       => ["type" => "string",   "description"=>"所属平台"],
		"name"       => ["type" => "string",   "description"=>"菜单名称"],
		"type"       => ["type" => "integer",   "description"=>"模块类型(0:导航,1:目录,2:菜单,3:按钮,4:接口)"],
		"pid"       => ["type" => "integer",   "description"=>"上级菜单ID"],
		"path"       => ["type" => "string",   "description"=>"菜单路径"],
		"icon"       => ["type" => "string",   "description"=>"图标"],
		"btn_style"       => ["type" => "string",   "description"=>"按钮颜色标识"],
		"route_key"       => ["type" => "string",   "description"=>"接口路由标识符"],
		"route_url"       => ["type" => "string",   "description"=>"前端路由地址"],
		"params"       => ["type" => "string",   "description"=>"参数"],
		"choice_ids"       => ["type" => "integer",   "description"=>"选择数据操作(0:不需选择,1:只能选择一个,2:可选择多个)"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"sort"       => ["type" => "integer",   "description"=>"排序值"],
		"is_show"       => ["type" => "integer",   "description"=>"是否显示(1:显示,0:隐藏)"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,0:停用,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","platform","name","type","pid","path","icon","btn_style","route_key","route_url","component","choice_ids","descr","sort","is_show","created_time","updated_time","status"],
        'childItem' => ['id','pid','name','icon','route_url'],
    ];

    //定义场景
    protected array $scenes = [
        'all'=>['id','name','pid'],
        'parent'=>['id','name','pid'],
        'tree'=>['id','pid','name','icon','route_url','children'],
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","platform","name","type","pid","path","icon","btn_style","route_key","route_url","component","choice_ids","descr","sort","is_show","created_time","updated_time","status"],
        'update'  =>  ["id","platform","name","type","pid","path","icon","btn_style","route_key","route_url","component","choice_ids","descr","sort","is_show","created_time","updated_time","status"],
        'detail'  =>  ["id","platform","name","type","pid","path","icon","btn_style","route_key","route_url","component","choice_ids","descr","sort","is_show","created_time","updated_time","status"],
    ];
}
