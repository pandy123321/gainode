<?php

namespace library\validator\arbitrage;
use support\extend\Validator;

class PositionValidation extends Validator{

    // 定义规则
    public $rules =   [
		"project_id"=>"required|integer",
		"plan_id"=>"required|integer",
		"signal_id"=>"required|integer",
		"fixture_id"=>"required|integer",
		"event_id"=>"required|integer",
		"event_name"=>"required|string",
		"phase"=>"integer",
		"league"=>"required|string",
		"home"=>"required|string",
		"away"=>"required|string",
		"kickoff_at"=>"required|integer",
		"leg1_bookmaker"=>"required|string",
		"leg1_bookmaker_id"=>"required|integer",
		"leg1_market"=>"required|string",
		"leg1_odds"=>"required|numeric",
		"leg1_stake"=>"required|numeric",
		"leg2_bookmaker"=>"required|string",
		"leg2_bookmaker_id"=>"required|integer",
		"leg2_market"=>"required|string",
		"leg2_odds"=>"required|numeric",
		"leg2_stake"=>"required|numeric",
		"total_stake"=>"required|numeric",
		"expected_rate"=>"required|numeric",
		"expected_profit"=>"required|numeric",
		"actual_rate"=>"required|numeric",
		"actual_profit"=>"required|numeric",
		"locked_at"=>"required|integer",
		"settled_at"=>"required|integer",
		"voided_at"=>"required|integer",
		"void_reason"=>"required|string",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
        "start_date"=>"string",
        "end_date"=>"string",
    ];

    // 定义信息
    protected $attributes  =   [
		"project_id"=>"矿机项目ID",
		"plan_id"=>"关联日计划ID(arbitrage_day_plan.id)",
		"signal_id"=>"关联信号ID(arbitrage_signal.id)",
		"fixture_id"=>"关联比赛ID(arbitrage_fixture.id; 结算/完赛判断强依赖)",
		"event_id"=>"BetBurger event_id快照(下单时冻结,便于溯源)",
		"event_name"=>"赛事名称快照(下单时冻结,不随行情变更)",
		"phase"=>"资金阶段: 1=开仓锁仓中 2=赛果待结算(已完赛待入账) 3=已结算入账 4=已作废回滚",
		"league"=>"联赛名称快照(下单时冻结)",
		"home"=>"主队名称快照(下单时冻结)",
		"away"=>"客队名称快照(下单时冻结)",
		"kickoff_at"=>"开赛时间戳快照(Unix秒,用于展示与结算时机)",
		"leg1_bookmaker"=>"Leg1博彩公司名称快照",
		"leg1_bookmaker_id"=>"Leg1博彩公司ID快照",
		"leg1_market"=>"Leg1玩法快照",
		"leg1_odds"=>"Leg1赔率快照(成交时)",
		"leg1_stake"=>"Leg1实际投注额(锁仓本金的一部分)",
		"leg2_bookmaker"=>"Leg2博彩公司名称快照",
		"leg2_bookmaker_id"=>"Leg2博彩公司ID快照",
		"leg2_market"=>"Leg2玩法快照",
		"leg2_odds"=>"Leg2赔率快照(成交时)",
		"leg2_stake"=>"Leg2实际投注额(锁仓本金的一部分)",
		"total_stake"=>"锁仓总本金",
		"expected_rate"=>"理论利润率(小数,下单时按信号计算)",
		"expected_profit"=>"理论利润",
		"actual_rate"=>"实际利润率(小数,含滑点/模拟偏差后的最终值)",
		"actual_profit"=>"实际利润(结算入账金额=actual_profit,本金另退)",
		"locked_at"=>"锁仓时间戳(Unix秒,开仓扣款时刻)",
		"settled_at"=>"结算入账时间戳(Unix秒,退本金+利润到Arbitrage钱包)",
		"voided_at"=>"作废时间戳(Unix秒; NULL=未作废)",
		"void_reason"=>"作废原因: fixture_match_cancelled=取消 fixture_match_abandoned=腰斩 fixture_grace_expired=超时 grace",
		"status"=>"状态(-1:删除,0:异常,1:待处理,2:已结算)",
        "page"=>"分页",
        "size"=>"显示条数",
        "start_date"=>"开始时间",
        "end_date"=>"结束时间",
    ];

    //定义场景
    protected $scenes = [
        'tradeLogs'  =>  ['project_id','phase','page','size','start_date','end_date'],
        'signal'=>['page','size']
    ];
}
