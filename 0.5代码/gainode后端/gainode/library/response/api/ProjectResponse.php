<?php

namespace library\response\api;
use support\extend\Response;

class ProjectResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"矿机项目ID"],
		"name"       => ["type" => "string",   "description"=>"矿机项目名称"],
		"image"       => ["type" => "string",   "description"=>"矿机项目图片"],
		"project_day"       => ["type" => "integer",   "description"=>"投资总天数"],
		"project_rate"       => ["type" => "string",   "description"=>"总收益率"],
		"project_price"       => ["type" => "string",   "description"=>"投资金额"],
		"min_day_rate"       => ["type" => "string",   "description"=>"最低日收益率"],
		"max_day_rate"       => ["type" => "string",   "description"=>"最高日收益率"],
		"user_amount"       => ["type" => "string",   "description"=>"购买时用户业绩"],
		"user_invite"       => ["type" => "integer",   "description"=>"购买时用户邀请人数"],
		"total_cnt"       => ["type" => "integer",   "description"=>"总库存数量"],
		"start_date"       => ["type" => "string",   "description"=>"开始时间"],
		"limit_num"       => ["type" => "integer",   "description"=>"限购数量"],
		"sales_cnt"       => ["type" => "integer",   "description"=>"销售数量"],
		"position_cnt"       => ["type" => "integer",   "description"=>"购买记录数"],
		"sort"       => ["type" => "integer",   "description"=>"排序"],
		"descr"       => ["type" => "string",   "description"=>"商品描述"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
		"status"       => ["type" => "integer",   "description"=>"项目状态(1:已上架,0:已关闭、-1:已删除)"],
    ];


    protected array $children = [
        'listItem' => ["id","name","image","project_day","project_rate","project_price","min_day_rate","max_day_rate","user_amount","user_invite","total_cnt","start_date","limit_num","sales_cnt","position_cnt","sort","descr","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail'  =>  ["id","name","image","project_day","project_rate","project_price","min_day_rate","max_day_rate","user_amount","user_invite","total_cnt","start_date","limit_num","sales_cnt","position_cnt","sort","descr","created_time","updated_time","status"],
    ];
}
