<?php

namespace library\validator\arbitrage;
use support\extend\Validator;

class ProjectValidation extends Validator{

    // 定义规则
    public $rules =   [
		"name"=>"required|string",
		"image"=>"string",
		"project_day"=>"required|integer",
		"project_rate"=>"required|numeric",
		"project_price"=>"required|numeric",
		"min_day_rate"=>"required|numeric",
		"max_day_rate"=>"required|numeric",
		"user_amount"=>"required|numeric",
		"user_invite"=>"required|integer",
		"total_cnt"=>"required|integer",
		"start_date"=>"string",
		"limit_num"=>"required|integer",
		"sales_cnt"=>"required|integer",
		"position_cnt"=>"required|integer",
		"sort"=>"required|integer",
		"descr"=>"string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"name"=>"矿机项目名称",
		"image"=>"矿机项目图片",
		"project_day"=>"投资总天数",
		"project_rate"=>"总收益率",
		"project_price"=>"投资金额",
		"min_day_rate"=>"最低日收益率",
		"max_day_rate"=>"最高日收益率",
		"user_amount"=>"购买时用户业绩",
		"user_invite"=>"购买时用户邀请人数",
		"total_cnt"=>"总库存数量",
		"start_date"=>"开始时间",
		"limit_num"=>"限购数量",
		"sales_cnt"=>"销售数量",
		"position_cnt"=>"购买记录数",
		"sort"=>"排序",
		"descr"=>"商品描述",
		"status"=>"项目状态(1:已上架,0:已关闭、-1:已删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["name","image","project_day","project_rate","project_price","min_day_rate","max_day_rate","user_amount","user_invite","total_cnt","start_date","limit_num","position_cnt","sort",'descr'],
        'update'  =>  ["name","image","project_day","project_rate","project_price","min_day_rate","max_day_rate","user_amount","user_invite","total_cnt","start_date","limit_num","position_cnt","sort",'descr'],
        'setStatus'  =>  ['status'],
    ];
}
