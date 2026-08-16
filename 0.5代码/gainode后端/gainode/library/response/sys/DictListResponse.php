<?php

namespace library\response\sys;
use support\extend\Response;

class DictListResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"自增ID"],
		"dict_code"       => ["type" => "string",   "description"=>"字典标识码"],
		"field_code"       => ["type" => "string",   "description"=>"字段代码"],
		"field_name"       => ["type" => "string",   "description"=>"字段名称"],
		"field_type"       => ["type" => "string",   "description"=>"字段类型"],
		"field_value"       => ["type" => "string",   "description"=>"字段值"],
		"field_required"       => ["type" => "string",   "description"=>"是否必填"],
		"field_tips"       => ["type" => "string",   "description"=>"字段提示"],
		"field_sort"       => ["type" => "integer",   "description"=>"字段排序"],
		"value_range_txt"       => ["type" => "string",   "description"=>"范围值名称"],
		"value_range"       => ["type" => "string",   "description"=>"范围值"],
		"addon"       => ["type" => "string",   "description"=>"扩展符号"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","dict_code","field_code","field_name","field_type","field_value","field_required","field_tips","field_sort","value_range_txt","value_range","addon","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","dict_code","field_code","field_name","field_type","field_value","field_required","field_tips","field_sort","value_range_txt","value_range","addon","created_time","updated_time","status"],
        'update'  =>  ["id","dict_code","field_code","field_name","field_type","field_value","field_required","field_tips","field_sort","value_range_txt","value_range","addon","created_time","updated_time","status"],
        'detail'  =>  ["id","dict_code","field_code","field_name","field_type","field_value","field_required","field_tips","field_sort","value_range_txt","value_range","addon","created_time","updated_time","status"],
    ];
}
