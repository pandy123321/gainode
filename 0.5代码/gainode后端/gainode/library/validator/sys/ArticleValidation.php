<?php

namespace library\validator\sys;
use support\extend\Validator;

class ArticleValidation extends Validator{

    // 定义规则
    public $rules =   [
		"eid"=>"required|integer",
		"title"=>"required|string",
		"content"=>"required|string",
		"category_id"=>"required|integer",
		"image_url"=>"string",
		"link_url"=>"string",
		"author"=>"required|string",
		"is_rec"=>"required|integer",
		"visit_num"=>"required|integer",
		"sort"=>"required|integer",
		"descr"=>"required|string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"eid"=>"企业ID(0:平台)",
		"title"=>"文章标题",
		"content"=>"文章内容",
		"category_id"=>"分类id",
		"image_url"=>"文章图片",
		"link_url"=>"链接地址",
		"author"=>"作者",
		"is_rec"=>"是否推荐(1:推荐,0:不推荐)",
		"visit_num"=>"阅读量",
		"sort"=>"排序",
		"descr"=>"描述",
		"status"=>"状态(1:正常,0:不显示,-1:删除)",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  ["title","content","category_id","image_url","link_url"],
        'update'  =>  ["title","content","category_id","image_url","link_url"],
        "setStatus"=>['status']
    ];
}
