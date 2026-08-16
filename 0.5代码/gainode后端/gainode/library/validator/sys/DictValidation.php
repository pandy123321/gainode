<?php

namespace library\validator\sys;
use support\extend\Validator;

class DictValidation extends Validator{

    // 定义规则
    public $rules =   [
		"name"=>"required|string",
		"code"=>"required|string",
		"type"=>"required|integer",
		"sort"=>"required|integer",
		"descr"=>"string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"name"=>"字典名称",
		"code"=>"字典标识码",
		"type"=>"字典类型(0:系统配置,1:资金配置,2:套利配置,3:存储配置,4:支付配置,5:其他配置)",
		"sort"=>"排序值",
		"descr"=>"描述",
		"status"=>"状态(1:正常,0:隐藏,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["name","code","sort","descr"],
        'update'  =>  ["name","code","sort","descr"],
        "setStatus"=>['status']
    ];
}
