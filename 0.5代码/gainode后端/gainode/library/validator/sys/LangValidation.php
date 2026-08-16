<?php

namespace library\validator\sys;
use support\extend\Validator;

class LangValidation extends Validator{

    // 定义规则
    public $rules =   [
		"name"=>"required|string",
		"code"=>"required|string",
		"locale"=>"required|string",
		"image"=>"string",
		"is_default"=>"required|integer",
		"sort"=>"required|integer",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"name"=>"名称",
		"code"=>"编码",
		"locale"=>"浏览器语言标识",
		"image"=>"语言图标",
		"is_default"=>"是否默认",
		"sort"=>"排序",
		"status"=>"是否启用",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ['name','code','locale','image',"sort"],
        'update'  =>  ['name','code','locale','image',"sort"],
        'setStatus'  =>  ['status']
    ];
}
