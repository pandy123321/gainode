<?php

namespace library\response\sys;
use support\extend\Response;

class LangResponse extends Response{

    // 定义信息
    protected array $fields  =   [
		"id"       => ["type" => "integer",   "description"=>"ID"],
		"name"       => ["type" => "string",   "description"=>"名称"],
		"code"       => ["type" => "string",   "description"=>"编码"],
		"locale"       => ["type" => "string",   "description"=>"浏览器语言标识"],
		"image"       => ["type" => "string",   "description"=>"语言图标"],
		"is_default"       => ["type" => "integer",   "description"=>"是否默认"],
		"sort"       => ["type" => "integer",   "description"=>"排序"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:启用,0:停用,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","name","code","locale","image","is_default","sort","created_time","updated_time","status"]
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","name","code","locale","image","is_default","sort","created_time","updated_time","status"],
        'update'  =>  ["id","name","code","locale","image","is_default","sort","created_time","updated_time","status"],
        'detail'  =>  ["id","name","code","locale","image","is_default","sort","created_time","updated_time","status"],
    ];
}
