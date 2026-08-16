<?php

namespace library\validator\arbitrage;
use support\extend\Validator;

class SignalValidation extends Validator{

    // 定义规则
    public $rules =   [
		"event_id"=>"required|integer",
		"fixture_id"=>"required|integer",
		"arb_hash"=>"required|string",
		"event_name"=>"required|string",
		"is_live"=>"required|integer",
		"started_at"=>"required|integer",
		"betburger_pct"=>"required|numeric",
		"profit_rate"=>"required|numeric",
		"leg1_bookmaker_id"=>"required|integer",
		"leg1_bookmaker"=>"required|string",
		"leg1_market"=>"required|string",
		"leg1_odds"=>"required|numeric",
		"leg1_market_param"=>"required|numeric",
		"leg1_market_type"=>"required|integer",
		"leg2_bookmaker_id"=>"required|integer",
		"leg2_bookmaker"=>"required|string",
		"leg2_market"=>"required|string",
		"leg2_odds"=>"required|numeric",
		"leg2_market_param"=>"required|numeric",
		"leg2_market_type"=>"required|integer",
		"preview_stake"=>"required|numeric",
		"current_score"=>"required|string",
		"first_seen_at"=>"required|integer",
		"last_seen_at"=>"required|integer",
		"status"=>"required|integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"event_id"=>"BetBurger event_id(标识一场比赛)",
		"fixture_id"=>"关联比赛ID(arbitrage_fixture.id; 0=尚未匹配/未创建占位)",
		"arb_hash"=>"套利组合唯一标识(同一event_id内唯一,用于幂等upsert)",
		"event_name"=>"赛事名称(采集时快照,展示用)",
		"is_live"=>"是否滚球: 0=赛前 1=滚球",
		"started_at"=>"比赛开赛时间戳(Unix秒,来自BetBurger)",
		"betburger_pct"=>"BetBurger原始收益率(百分比口径,如1.20表示1.2%)",
		"profit_rate"=>"理论利润率(小数口径,如0.0120表示1.20%; 由两腿赔率数学计算)",
		"leg1_bookmaker_id"=>"Leg1博彩公司ID(BetBurger bookmaker_id)",
		"leg1_bookmaker"=>"Leg1博彩公司名称(展示名)",
		"leg1_market"=>"Leg1玩法名称(如 Over2.5 / Home Win)",
		"leg1_odds"=>"Leg1赔率(>1.00)",
		"leg1_market_param"=>"Leg1市场参数(如让球/大小球线,无则为NULL)",
		"leg1_market_type"=>"Leg1市场类型(BetBurger market_and_bet_type)",
		"leg2_bookmaker_id"=>"Leg2博彩公司ID(BetBurger bookmaker_id)",
		"leg2_bookmaker"=>"Leg2博彩公司名称(展示名)",
		"leg2_market"=>"Leg2玩法名称(与Leg1对立结果)",
		"leg2_odds"=>"Leg2赔率(>1.00)",
		"leg2_market_param"=>"Leg2市场参数(如让球/大小球线,无则为NULL)",
		"leg2_market_type"=>"Leg2市场类型(BetBurger market_and_bet_type)",
		"preview_stake"=>"预览总注额(采集时用固定本金计算两腿分配,非真实下单金额)",
		"current_score"=>"滚球当前比分(如 1-0; 赛前为NULL)",
		"first_seen_at"=>"首次采集时间戳(Unix秒)",
		"last_seen_at"=>"最近采集时间戳(Unix秒; 用于过期清理)",
		"status"=>"信号状态: -1=删除 1=有效 2=已过期 3=已用尽(已成交) 4=已关闭 5=无效(数学校验不通过)",
    ];

    //定义场景
    protected $scenes = [
        'add'  =>  [],
    ];
}
