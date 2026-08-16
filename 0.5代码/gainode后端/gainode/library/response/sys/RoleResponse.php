<?php

namespace library\response\sys;
use support\extend\Response;

class RoleResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],
		"id"       => ["type" => "integer",   "description"=>"角色ID"],
		"eid"       => ["type" => "integer",   "description"=>"企业ID(0:平台)"],
		"name"       => ["type" => "string",   "description"=>"角色名称"],
		"pid"       => ["type" => "integer",   "description"=>"父级角色"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"sort"       => ["type" => "integer",   "description"=>"排序"],
		"menu_ids"       => ["type" => "string",   "description"=>"权限菜单"],
        'created_time'      => ['type' => 'string', 'description' => '创建时间'],
        'updated_time'      => ['type' => 'string', 'description' => '修改时间'],
		"deleted_time"       => ["type" => "integer",   "description"=>"删除时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,0:停用,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","name","pid","descr","sort","menu_ids","created_time","updated_time","deleted_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'all'=>["id","name","pid"],
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","name","pid","descr","sort","created_time","updated_time","status"],
        'update'  =>  ["id","name","pid","descr","sort","created_time","updated_time","status"],
        'detail'  =>  ["id","name","pid","descr","sort","created_time","updated_time","status"],
    ];
}
