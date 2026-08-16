<?php

namespace library\validator\sys;
use support\extend\Validator;

class MenusValidation extends Validator{

    // 定义规则
    public $rules =   [
		"platform"=>"required|string",
		"name"=>"required|string",
		"type"=>"required|integer",
		"pid"=>"required|integer",
		"path"=>"string",
		"icon"=>"string",
		"btn_style"=>"string",
		"route_key"=>"string",
		"route_url"=>"string",
		"component"=>"string",
		"choice_ids"=>"integer",
		"descr"=>"string",
		"sort"=>"required|integer",
		"is_show"=>"required|integer",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"platform"=>"所属平台",
		"name"=>"菜单名称",
		"type"=>"模块类型(1:目录,2:菜单,3:按钮,4:接口)",
		"pid"=>"上级菜单ID",
		"path"=>"菜单路径",
		"icon"=>"图标",
		"btn_style"=>"按钮颜色标识",
		"route_key"=>"接口路由标识符",
		"route_url"=>"前端路由地址",
		"choice_ids"=>"选择数据操作(0:不需选择,1:只能选择一个,2:可选择多个)",
		"descr"=>"描述",
		"sort"=>"排序值",
		"is_show"=>"是否显示(1:显示,0:隐藏)",
		"status"=>"状态(1:正常,0:停用,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["name","type","pid","icon","btn_style","route_key","route_url","choice_ids","sort","is_show","descr"],
        'update'  =>  ["name","type","pid","icon","btn_style","route_key","route_url","choice_ids","sort","is_show","descr"],
        'setStatus'  =>  ["status"],
    ];
}
