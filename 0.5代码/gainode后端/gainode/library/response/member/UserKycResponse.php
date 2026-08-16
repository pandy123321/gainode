<?php

namespace library\response\member;
use support\extend\Response;

class UserKycResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],
        'report'     => ['type' => 'array',  'description' => '统计数据'],

		"id"       => ["type" => "integer",   "description"=>"主键ID"],
		"user_id"       => ["type" => "integer",   "description"=>"会员ID"],
		"real_name"       => ["type" => "string",   "description"=>"真实姓名"],
		"country"       => ["type" => "string",   "description"=>"国家/地区"],
		"id_type"       => ["type" => "string",   "description"=>"证件类型：(身份证:id_card,护照:passport,驾驶证:driver)"],
		"id_number"       => ["type" => "string",   "description"=>"证件号码"],
		"phone"       => ["type" => "string",   "description"=>"认证手机号"],
		"front_image"       => ["type" => "string",   "description"=>"证件正面图片"],
		"back_image"       => ["type" => "string",   "description"=>"证件反面图片"],
		"hand_image"       => ["type" => "string",   "description"=>"手持证件图片"],
		"reject_reason"       => ["type" => "string",   "description"=>"拒绝原因"],
		"review_admin_id"       => ["type" => "integer",   "description"=>"审核管理员ID"],
		"review_time"       => ["type" => "integer",   "description"=>"审核时间"],
		"review_status"       => ["type" => "string",   "description"=>"审核状态(未审核:created,审核通过:approved,已拒绝:rejected)"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间"],
		"updated_time"       => ["type" => "integer",   "description"=>"更新时间"],
		"deleted_time"       => ["type" => "integer",   "description"=>"软删除时间"],
		"status"       => ["type" => "integer",   "description"=>"状态:(0:隐藏,1:正常,-1:删除)"],
        'user_no' => ["type" => "string",   "description"=>"用户编号"],
        'account' => ["type" => "string",   "description"=>"用户账号"],

    ];


    protected array $children = [
        'listItem' => ["id","user_id","real_name","country","id_type","id_number","phone","front_image","back_image","hand_image","reject_reason","review_admin_id","review_time","review_status","created_time","updated_time","deleted_time","status","user_no","account"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data','report'],
        'detail'  =>  ["id","user_id","real_name","country","id_type","id_number","phone","front_image","back_image","hand_image","reject_reason","review_admin_id","review_time","review_status","created_time","updated_time","deleted_time","status"],
    ];
}
