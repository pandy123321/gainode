<?php

namespace library\validator\member;
use support\extend\Validator;

class UserKycValidation extends Validator{

    // 定义规则
    public $rules =   [
		"user_id"=>"required|integer",
		"real_name"=>"required|string",
		"country"=>"required|string",
		"id_type"=>"required|string",
		"id_number"=>"required|string",
		"phone"=>"required|string",
		"front_image"=>"required|string",
		"back_image"=>"required|string",
		"hand_image"=>"required|string",
		"reject_reason"=>"required|string",
		"review_admin_id"=>"required|integer",
		"review_time"=>"required|integer",
		"review_status"=>"required|string",
		"deleted_time"=>"required|integer",
		"status"=>"string",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"user_id"=>"会员ID",
		"real_name"=>"真实姓名",
		"country"=>"国家/地区",
		"id_type"=>"证件类型：(身份证:id_card,护照:passport,驾驶证:driver)",
		"id_number"=>"证件号码",
		"phone"=>"认证手机号",
		"front_image"=>"证件正面图片",
		"back_image"=>"证件反面图片",
		"hand_image"=>"手持证件图片",
		"reject_reason"=>"拒绝原因",
		"review_admin_id"=>"审核管理员ID",
		"review_time"=>"审核时间",
		"review_status"=>"审核状态（all:所有,未审核:created,审核通过:approved,已拒绝:rejected）",
		"deleted_time"=>"软删除时间",
		"status"=>"状态：(0:隐藏,1:正常,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'create' => ["real_name","country","id_type","id_number","front_image","back_image","hand_image"],
        'verify' => ['review_status','reject_reason'],
        'list'=>['review_status']
    ];
}
