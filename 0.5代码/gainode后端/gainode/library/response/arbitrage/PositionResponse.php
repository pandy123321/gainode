<?php

namespace library\response\arbitrage;
use support\extend\Response;

class PositionResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

		"id"       => ["type" => "integer",   "description"=>"主键ID"],
		"project_id"       => ["type" => "integer",   "description"=>"矿机项目ID"],
		"plan_id"       => ["type" => "integer",   "description"=>"关联日计划ID(arbitrage_day_plan.id)"],
		"signal_id"       => ["type" => "integer",   "description"=>"关联信号ID(arbitrage_signal.id)"],
		"fixture_id"       => ["type" => "integer",   "description"=>"关联比赛ID(arbitrage_fixture.id; 结算/完赛判断强依赖)"],
		"event_id"       => ["type" => "integer",   "description"=>"BetBurger event_id快照(下单时冻结,便于溯源)"],
		"event_name"       => ["type" => "string",   "description"=>"赛事名称快照(下单时冻结,不随行情变更)"],
		"phase"       => ["type" => "integer",   "description"=>"资金阶段: 1=开仓锁仓中 2=赛果待结算(已完赛待入账) 3=已结算入账 4=已作废回滚"],
		"league"       => ["type" => "string",   "description"=>"联赛名称快照(下单时冻结)"],
		"home"       => ["type" => "string",   "description"=>"主队名称快照(下单时冻结)"],
		"away"       => ["type" => "string",   "description"=>"客队名称快照(下单时冻结)"],
		"kickoff_at"       => ["type" => "integer",   "description"=>"开赛时间戳快照(Unix秒,用于展示与结算时机)"],
		"leg1_bookmaker"       => ["type" => "string",   "description"=>"Leg1博彩公司名称快照"],
		"leg1_bookmaker_id"       => ["type" => "integer",   "description"=>"Leg1博彩公司ID快照"],
		"leg1_market"       => ["type" => "string",   "description"=>"Leg1玩法快照"],
		"leg1_odds"       => ["type" => "string",   "description"=>"Leg1赔率快照(成交时)"],
		"leg1_stake"       => ["type" => "string",   "description"=>"Leg1实际投注额(锁仓本金的一部分)"],
		"leg2_bookmaker"       => ["type" => "string",   "description"=>"Leg2博彩公司名称快照"],
		"leg2_bookmaker_id"       => ["type" => "integer",   "description"=>"Leg2博彩公司ID快照"],
		"leg2_market"       => ["type" => "string",   "description"=>"Leg2玩法快照"],
		"leg2_odds"       => ["type" => "string",   "description"=>"Leg2赔率快照(成交时)"],
		"leg2_stake"       => ["type" => "string",   "description"=>"Leg2实际投注额(锁仓本金的一部分)"],
		"total_stake"       => ["type" => "string",   "description"=>"锁仓总本金"],
		"expected_rate"       => ["type" => "string",   "description"=>"理论利润率(小数,下单时按信号计算)"],
		"expected_profit"       => ["type" => "string",   "description"=>"理论利润"],
		"actual_rate"       => ["type" => "string",   "description"=>"实际利润率(小数,含滑点/模拟偏差后的最终值)"],
		"actual_profit"       => ["type" => "string",   "description"=>"实际利润(结算入账金额=actual_profit,本金另退)"],
		"locked_at"       => ["type" => "integer",   "description"=>"锁仓时间戳(Unix秒,开仓扣款时刻)"],
		"settled_at"       => ["type" => "integer",   "description"=>"结算入账时间戳(Unix秒,退本金+利润到Arbitrage钱包)"],
		"voided_at"       => ["type" => "integer",   "description"=>"作废时间戳(Unix秒; NULL=未作废)"],
		"void_reason"       => ["type" => "string",   "description"=>"作废原因: fixture_match_cancelled=取消 fixture_match_abandoned=腰斩 fixture_grace_expired=超时 grace"],
		"created_time"       => ["type" => "integer",   "description"=>"创建时间戳(Unix秒)"],
		"updated_time"       => ["type" => "integer",   "description"=>"更新时间戳(Unix秒)"],
		"status"       => ["type" => "integer",   "description"=>"状态(-1:删除,0:异常,1:待处理,2:已结算)"],
    ];

    protected array $children = [
        'listItem' => ["id","project_id","plan_id","signal_id","fixture_id","event_id","event_name","phase","league","home","away","kickoff_at","leg1_bookmaker","leg1_bookmaker_id","leg1_market","leg1_odds","leg1_stake","leg2_bookmaker","leg2_bookmaker_id","leg2_market","leg2_odds","leg2_stake","total_stake","expected_rate","expected_profit","actual_rate","actual_profit","locked_at","settled_at","voided_at","void_reason","created_time","updated_time","status"],
    ];

    //定义场景
    protected array $scenes = [
        'tradeLogs'   => ['page', 'size', 'count', 'total_page', 'data'],
        'list'  =>  ["id","project_id","plan_id","signal_id","fixture_id","event_id","event_name","phase","league","home","away","kickoff_at","leg1_bookmaker","leg1_bookmaker_id","leg1_market","leg1_odds","leg1_stake","leg2_bookmaker","leg2_bookmaker_id","leg2_market","leg2_odds","leg2_stake","total_stake","expected_rate","expected_profit","actual_rate","actual_profit","locked_at","settled_at","voided_at","void_reason","created_time","updated_time","status"],
        'detail'  =>  ["id","project_id","plan_id","signal_id","fixture_id","event_id","event_name","phase","league","home","away","kickoff_at","leg1_bookmaker","leg1_bookmaker_id","leg1_market","leg1_odds","leg1_stake","leg2_bookmaker","leg2_bookmaker_id","leg2_market","leg2_odds","leg2_stake","total_stake","expected_rate","expected_profit","actual_rate","actual_profit","locked_at","settled_at","voided_at","void_reason","created_time","updated_time","status"],
    ];
}
