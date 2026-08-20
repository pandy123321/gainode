<?php

namespace library\validator\arbitrage;
use support\extend\Validator;

class FixtureValidation extends Validator{

    // 定义规则
    public $rules =   [
		"source"=>"required|integer",
		"source_id"=>"required|integer",
		"betburger_event_id"=>"integer",
		"is_placeholder"=>"required|integer",
		"league"=>"string",
		"home"=>"string",
		"away"=>"string",
		"timezone"=>"string",
		"kickoff_at"=>"integer",
		"status_short"=>"string",
		"status_long"=>"string",
		"score_home"=>"integer",
		"score_away"=>"integer",
		"is_finished"=>"integer",
		"status"=>"integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"source"=>"数据来源: 1=API-Football真实比赛 2=BetBurger占位比赛",
		"source_id"=>"来源侧唯一ID",
		"betburger_event_id"=>"BetBurger event_id",
		"is_placeholder"=>"是否占位比赛: 0=真实可结算 1=占位",
		"league"=>"联赛名称",
		"home"=>"主队名称",
		"away"=>"客队名称",
		"timezone"=>"比赛时区",
		"kickoff_at"=>"开赛时间戳(Unix秒)",
		"status_short"=>"比赛状态短码",
		"status_long"=>"比赛状态描述",
		"score_home"=>"主队当前比分",
		"score_away"=>"客队当前比分",
		"is_finished"=>"是否已完赛: 0=未完赛 1=已完赛",
		"status"=>"记录状态: 1=正常 -1=删除",
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  [],
    ];
}
