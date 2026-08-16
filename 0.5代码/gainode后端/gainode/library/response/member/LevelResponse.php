<?php

namespace library\response\member;
use support\extend\Response;

class LevelResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"用户等级ID"],
		"icon"       => ["type" => "string",   "description"=>"等级图片"],
		"user_type"       => ["type" => "integer",   "description"=>"用户类型(0:普通用户,1:代理商,2:员工)"],
		"name"       => ["type" => "string",   "description"=>"用户等级名称"],
		"grade"       => ["type" => "integer",   "description"=>"级别"],
		"discount"       => ["type" => "integer",   "description"=>"折扣/收益率百分比"],
		"amount"       => ["type" => "float",   "description"=>"业绩额度"],
		"invite_cnt"       => ["type" => "integer",   "description"=>"邀请人数"],
		"descr"       => ["type" => "string",   "description"=>"等级说明"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:可用,0:隐藏,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","icon","user_type","name","grade","discount","amount","invite_cnt","descr","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","icon","user_type","name","grade","discount","amount","invite_cnt","descr","created_time","updated_time","status"],
        'update'  =>  ["id","icon","user_type","name","grade","discount","amount","invite_cnt","descr","created_time","updated_time","status"],
        'detail'  =>  ["id","icon","user_type","name","grade","discount","amount","invite_cnt","descr","created_time","updated_time","status"],
    ];
}
