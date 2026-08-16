<?php

namespace library\response\api;
use support\extend\Response;

class SignalResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"主键ID"],
		"event_id"       => ["type" => "integer",   "description"=>"BetBurger event_id(标识一场比赛)"],
		"fixture_id"       => ["type" => "integer",   "description"=>"关联比赛ID(arbitrage_fixture.id; 0=尚未匹配/未创建占位)"],
		"arb_hash"       => ["type" => "string",   "description"=>"套利组合唯一标识(同一event_id内唯一,用于幂等upsert)"],
		"event_name"       => ["type" => "string",   "description"=>"赛事名称(采集时快照,展示用)"],
		"is_live"       => ["type" => "integer",   "description"=>"是否滚球: 0=赛前 1=滚球"],
		"started_at"       => ["type" => "integer",   "description"=>"比赛开赛时间戳(Unix秒,来自BetBurger)"],
		"betburger_pct"       => ["type" => "string",   "description"=>"BetBurger原始收益率(百分比口径,如1.20表示1.2%)"],
		"profit_rate"       => ["type" => "string",   "description"=>"理论利润率(小数口径,如0.0120表示1.20%; 由两腿赔率数学计算)"],
		"leg1_bookmaker_id"       => ["type" => "integer",   "description"=>"Leg1博彩公司ID(BetBurger bookmaker_id)"],
		"leg1_bookmaker"       => ["type" => "string",   "description"=>"Leg1博彩公司名称(展示名)"],
		"leg1_market"       => ["type" => "string",   "description"=>"Leg1玩法名称(如 Over2.5 / Home Win)"],
		"leg1_odds"       => ["type" => "string",   "description"=>"Leg1赔率(>1.00)"],
		"leg1_market_param"       => ["type" => "string",   "description"=>"Leg1市场参数(如让球/大小球线,无则为NULL)"],
		"leg1_market_type"       => ["type" => "integer",   "description"=>"Leg1市场类型(BetBurger market_and_bet_type)"],
		"leg2_bookmaker_id"       => ["type" => "integer",   "description"=>"Leg2博彩公司ID(BetBurger bookmaker_id)"],
		"leg2_bookmaker"       => ["type" => "string",   "description"=>"Leg2博彩公司名称(展示名)"],
		"leg2_market"       => ["type" => "string",   "description"=>"Leg2玩法名称(与Leg1对立结果)"],
		"leg2_odds"       => ["type" => "string",   "description"=>"Leg2赔率(>1.00)"],
		"leg2_market_param"       => ["type" => "string",   "description"=>"Leg2市场参数(如让球/大小球线,无则为NULL)"],
		"leg2_market_type"       => ["type" => "integer",   "description"=>"Leg2市场类型(BetBurger market_and_bet_type)"],
		"preview_stake"       => ["type" => "string",   "description"=>"预览总注额(采集时用固定本金计算两腿分配,非真实下单金额)"],
		"current_score"       => ["type" => "string",   "description"=>"滚球当前比分(如 1-0; 赛前为NULL)"],
		"first_seen_at"       => ["type" => "integer",   "description"=>"首次采集时间戳(Unix秒)"],
		"last_seen_at"       => ["type" => "integer",   "description"=>"最近采集时间戳(Unix秒; 用于过期清理)"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间戳(Unix秒)"],
		"updated_time"       => ["type" => "integer",   "description"=>"更新时间戳(Unix秒)"],
		"status"       => ["type" => "integer",   "description"=>"信号状态: -1=删除 1=有效 2=已过期 3=已用尽(已成交) 4=已关闭 5=无效(数学校验不通过)"],
    ];


    protected array $children = [
        'listItem' => ["id","event_id","fixture_id","arb_hash","event_name","is_live","started_at","betburger_pct","profit_rate","leg1_bookmaker_id","leg1_bookmaker","leg1_market","leg1_odds","leg1_market_param","leg1_market_type","leg2_bookmaker_id","leg2_bookmaker","leg2_market","leg2_odds","leg2_market_param","leg2_market_type","preview_stake","current_score","first_seen_at","last_seen_at","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail'  =>  ["id","event_id","fixture_id","arb_hash","event_name","is_live","started_at","betburger_pct","profit_rate","leg1_bookmaker_id","leg1_bookmaker","leg1_market","leg1_odds","leg1_market_param","leg1_market_type","leg2_bookmaker_id","leg2_bookmaker","leg2_market","leg2_odds","leg2_market_param","leg2_market_type","preview_stake","current_score","first_seen_at","last_seen_at","created_time","updated_time","status"],
    ];
}
