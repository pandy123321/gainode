<?php

namespace library\response\sys;
use support\extend\Response;

class NoticeCategoryResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>""],
		"name"       => ["type" => "string",   "description"=>"分类名称"],
		"sort"       => ["type" => "integer",   "description"=>"排序值"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"最后修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","name","sort","descr","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'all'=>["id","name"],
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","name","sort","descr","created_time","updated_time","status"],
        'update'  =>  ["id","name","sort","descr","created_time","updated_time","status"],
        'detail'  =>  ["id","name","sort","descr","created_time","updated_time","status"],
    ];
}
