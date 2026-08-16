<?php

namespace library\response\sys;
use support\extend\Response;

class NoticeResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>""],
		"eid"       => ["type" => "integer",   "description"=>"企业ID(0:平台)"],
		"admin_id"       => ["type" => "integer",   "description"=>"用户ID(0:所有)"],
		"category_id"       => ["type" => "integer",   "description"=>"公告分类"],
		"title"       => ["type" => "string",   "description"=>"标题"],
		"content"       => ["type" => "string",   "description"=>"内容"],
		"sort"       => ["type" => "integer",   "description"=>"排序值"],
		"is_rec"       => ["type" => "integer",   "description"=>"是否推荐"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"最后修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,0:不显示,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","eid","admin_id","category_id","title","content","sort","is_rec","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","eid","admin_id","category_id","title","content","sort","is_rec","created_time","updated_time","status"],
        'update'  =>  ["id","eid","admin_id","category_id","title","content","sort","is_rec","created_time","updated_time","status"],
        'detail'  =>  ["id","eid","admin_id","category_id","title","content","sort","is_rec","created_time","updated_time","status"],
    ];
}
