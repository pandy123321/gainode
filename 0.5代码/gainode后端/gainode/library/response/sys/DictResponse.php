<?php

namespace library\response\sys;
use support\extend\Response;

class DictResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"自增ID"],
		"name"       => ["type" => "string",   "description"=>"字典名称"],
		"code"       => ["type" => "string",   "description"=>"字典标识码"],
		"type"       => ["type" => "integer",   "description"=>"字典类型"],
		"sort"       => ["type" => "integer",   "description"=>"排序值"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,0:隐藏,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","name","code","type","sort","descr","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","name","code","type","sort","descr","created_time","updated_time","status"],
        'update'  =>  ["id","name","code","type","sort","descr","created_time","updated_time","status"],
        'detail'  =>  ["id","name","code","type","sort","descr","created_time","updated_time","status"],
    ];
}
