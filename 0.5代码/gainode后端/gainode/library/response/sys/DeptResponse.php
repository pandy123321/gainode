<?php

namespace library\response\sys;
use support\extend\Response;

class DeptResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"id"],
		"eid"       => ["type" => "integer",   "description"=>"企业ID(0:平台)"],
		"name"       => ["type" => "string",   "description"=>"部门名称"],
		"pid"       => ["type" => "integer",   "description"=>"上级部门id"],
		"admin_id"       => ["type" => "integer",   "description"=>"负责人ID"],
		"sort"       => ["type" => "integer",   "description"=>"排序"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"deleted_time"       => ["type" => "integer",   "description"=>"删除时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,0:停用,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","eid","name","pid","admin_id","sort","descr","created_time","updated_time","deleted_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'all'=>["id","name","pid"],
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","eid","name","pid","admin_id","sort","descr","created_time","updated_time","status"],
        'update'  =>  ["id","eid","name","pid","admin_id","sort","descr","created_time","updated_time","status"],
        'detail'  =>  ["id","eid","name","pid","admin_id","sort","descr","created_time","updated_time","status"],
    ];
}
