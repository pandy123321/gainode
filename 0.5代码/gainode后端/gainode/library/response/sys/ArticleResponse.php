<?php

namespace library\response\sys;
use support\extend\Response;

class ArticleResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"文章id"],
		"eid"       => ["type" => "integer",   "description"=>"企业ID(0:平台)"],
		"title"       => ["type" => "string",   "description"=>"文章标题"],
		"content"       => ["type" => "string",   "description"=>"文章内容"],
		"category_id"       => ["type" => "integer",   "description"=>"分类id"],
		"image_url"       => ["type" => "string",   "description"=>"文章图片"],
		"link_url"       => ["type" => "string",   "description"=>"链接地址"],
		"author"       => ["type" => "string",   "description"=>"作者"],
		"is_rec"       => ["type" => "integer",   "description"=>"是否推荐(1:推荐,0:不推荐)"],
		"visit_num"       => ["type" => "integer",   "description"=>"阅读量"],
		"sort"       => ["type" => "integer",   "description"=>"排序"],
		"descr"       => ["type" => "string",   "description"=>"描述"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"最后修改时间"],
		"status"       => ["type" => "integer",   "description"=>"状态(1:正常,0:不显示,-1:删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","eid","title","content","category_id","image_url","link_url","author","is_rec","visit_num","sort","descr","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ["id","eid","title","content","category_id","image_url","link_url","author","is_rec","visit_num","sort","descr","created_time","updated_time","status"],
        'update'  =>  ["id","eid","title","content","category_id","image_url","link_url","author","is_rec","visit_num","sort","descr","created_time","updated_time","status"],
        'detail'  =>  ["id","eid","title","content","category_id","image_url","link_url","author","is_rec","visit_num","sort","descr","created_time","updated_time","status"],
    ];
}
