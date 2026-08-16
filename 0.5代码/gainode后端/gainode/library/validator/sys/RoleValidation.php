<?php

namespace library\validator\sys;
use support\extend\Validator;

class RoleValidation extends Validator{

    // 定义规则
    public $rules =   [
        "id"=>"required|integer",
		"eid"=>"required|integer",
		"name"=>"required|string",
		"pid"=>"required|integer",
		"descr"=>"required|string",
		"sort"=>"required|integer",
		"menu_ids"=>"required|string",
		"deleted_time"=>"required|integer",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
        "id"=>"ID",
		"eid"=>"企业ID",
		"name"=>"角色名称",
		"pid"=>"父级角色",
		"descr"=>"描述",
		"sort"=>"排序",
		"menu_ids"=>"权限菜单",
		"deleted_time"=>"删除时间",
		"status"=>"状态(1:正常,0:停用,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ['name','pid','sort'],
        'update'  =>  ['name','pid','sort'],
        'setStatus'  =>  ['status'],
        'setMenuIds'  =>  ['menu_ids'],
    ];
}
