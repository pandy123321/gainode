<?php

namespace library\validator\member;
use support\extend\Validator;

class LevelValidation extends Validator{

    // 定义规则
    public $rules =   [
		"icon"=>"required|string",
		"user_type"=>"required|integer",
		"name"=>"required|string",
		"grade"=>"required|integer",
		"discount"=>"required|integer",
		"amount"=>"required|integer",
		"invite_cnt"=>"required|integer",
		"descr"=>"required|string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"icon"=>"等级图片",
		"user_type"=>"用户类型(0:普通用户,1:代理商,2:员工)",
		"name"=>"用户等级名称",
		"grade"=>"级别",
		"discount"=>"折扣/收益率百分比",
		"amount"=>"业绩额度",
		"invite_cnt"=>"邀请人数",
		"descr"=>"等级说明",
		"status"=>"状态(1:可用,0:隐藏,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ['user_type','name','grade','discount','amount','invite_cnt','descr'],
        'update'  =>  ['user_type','name','grade','discount','amount','invite_cnt','descr'],
    ];
}
