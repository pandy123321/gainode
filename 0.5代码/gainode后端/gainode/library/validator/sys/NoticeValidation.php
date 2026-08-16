<?php

namespace library\validator\sys;
use support\extend\Validator;

class NoticeValidation extends Validator{

    // 定义规则
    public $rules =   [
		"eid"=>"required|integer",
		"admin_id"=>"required|integer",
		"category_id"=>"required|integer",
		"title"=>"required|string",
		"content"=>"required|string",
		"sort"=>"required|integer",
		"is_rec"=>"required|integer",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"eid"=>"企业ID(0:平台)",
		"admin_id"=>"用户ID(0:所有)",
		"category_id"=>"公告分类",
		"title"=>"标题",
		"content"=>"内容",
		"sort"=>"排序值",
		"is_rec"=>"是否推荐",
		"status"=>"状态(1:正常,0:不显示,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["category_id","title","content","sort"],
        'update'  =>  ["category_id","title","content","sort"],
        'setStatus'  =>  ["status"],
    ];
}
