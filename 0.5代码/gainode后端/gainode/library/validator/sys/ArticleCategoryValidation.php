<?php

namespace library\validator\sys;
use support\extend\Validator;

class ArticleCategoryValidation extends Validator{

    // 定义规则
    public $rules =   [
		"eid"=>"required|integer",
		"name"=>"required|string",
		"pid"=>"required|integer",
		"sort"=>"required|integer",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"eid"=>"企业ID(0:平台)",
		"name"=>"分类名称",
		"pid"=>"父分类",
		"sort"=>"排序",
		"status"=>"状态(1:正常,0:不显示,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["name","pid","sort"],
        'update'  =>  ["name","pid","sort"],
        "setStatus"=>['status']
    ];
}
