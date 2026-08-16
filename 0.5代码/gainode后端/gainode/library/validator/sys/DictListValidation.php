<?php

namespace library\validator\sys;
use support\extend\Validator;

class DictListValidation extends Validator{

    // 定义规则
    public $rules =   [
		"dict_code"=>"required|string",
		"field_code"=>"required|string",
		"field_name"=>"required|string",
		"field_type"=>"required|string",
		"field_value"=>"required|string",
		"field_required"=>"required|string",
		"field_tips"=>"required|string",
		"field_sort"=>"required|integer",
		"value_range_txt"=>"string",
		"value_range"=>"string",
		"addon"=>"string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"dict_code"=>"字典标识码",
		"field_code"=>"字段代码",
		"field_name"=>"字段名称",
		"field_type"=>"字段类型",
		"field_value"=>"字段值",
		"field_required"=>"是否必填",
		"field_tips"=>"字段提示",
		"field_sort"=>"字段排序",
		"value_range_txt"=>"范围值名称",
		"value_range"=>"范围值",
		"addon"=>"扩展符号",
		"status"=>"状态(1:正常,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["dict_code","field_code","field_name","field_type","field_value","field_required","field_tips","field_sort","value_range_txt","value_range"],
        'update'  =>  ["dict_code","field_code","field_name","field_type","field_value","field_required","field_tips","field_sort","value_range_txt","value_range"],
        "setStatus"=>['status']
    ];
}
