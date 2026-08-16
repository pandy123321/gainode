<?php

namespace library\validator\member;
use support\extend\Validator;

class RedPacketValidation extends Validator{

    // 定义规则
    public $rules =   [
		"packet_no"=>"required|string",
		"title"=>"required|string",
		"total_amount"=>"required|numeric",
		"packet_count"=>"required|integer",
		"remain_count"=>"required|integer",
		"remain_amount"=>"required|numeric",
		"packet_type"=>"required|integer",
		"status"=>"required|integer",
		"start_time"=>"string",
		"expire_time"=>"string",
		"admin_id"=>"required|integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"packet_no"=>"红包编号",
		"title"=>"红包标题",
		"total_amount"=>"红包总金额",
		"packet_count"=>"红包数量",
		"remain_count"=>"剩余数量",
		"remain_amount"=>"剩余金额",
		"packet_type"=>"1随机红包 2固定红包",
		"status"=>"0待领取,1领取中,2已领取完,3过期,4关闭",
		"start_time"=>"开始时间",
		"expire_time"=>"过期时间",
		"admin_id"=>"后台管理员",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ['title','total_amount','packet_count','packet_type','start_time','expire_time'],
        'update'  =>  ['title','start_time','expire_time'],
    ];
}
