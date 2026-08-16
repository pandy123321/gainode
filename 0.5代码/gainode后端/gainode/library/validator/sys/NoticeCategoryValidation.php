<?php

namespace library\validator\sys;
use support\extend\Validator;

class NoticeCategoryValidation extends Validator{

    // 定义规则
    public $rules =   [
		"name"=>"required|string",
		"sort"=>"required|integer",
		"descr"=>"string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"name"=>"分类名称",
		"sort"=>"排序值",
		"descr"=>"描述",
		"status"=>"状态(1:正常,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["name","sort","descr"],
        'update'  =>  ["name","sort","descr"],
        'setStatus'  =>  ["status"],
    ];
}
