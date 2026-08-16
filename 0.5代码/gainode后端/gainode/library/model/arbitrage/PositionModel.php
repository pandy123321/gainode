<?php

namespace library\model\arbitrage;

use Illuminate\Database\Eloquent\Builder;
use support\extend\Model;

/**
 * @property integer $id 主键ID
 * @property integer $project_id 矿机项目ID
 * @property integer $plan_id 关联日计划ID
 * @property integer $signal_id 关联信号ID
 * @property integer $fixture_id 关联比赛ID
 * @property integer $event_id BetBurger event_id快照
 * @property string $event_name 赛事名称快照
 * @property integer $phase 资金阶段: 1=锁仓中 2=待结算 3=已结算 4=已作废
 * @property string $league 联赛名称快照
 * @property string $home 主队名称快照
 * @property string $away 客队名称快照
 * @property integer $kickoff_at 开赛时间戳快照
 * @property string $leg1_bookmaker Leg1博彩公司
 * @property integer $leg1_bookmaker_id Leg1博彩公司ID
 * @property string $leg1_market Leg1玩法
 * @property float $leg1_odds Leg1赔率
 * @property float $leg1_stake Leg1投注额
 * @property string $leg2_bookmaker Leg2博彩公司
 * @property integer $leg2_bookmaker_id Leg2博彩公司ID
 * @property string $leg2_market Leg2玩法
 * @property float $leg2_odds Leg2赔率
 * @property float $leg2_stake Leg2投注额
 * @property float $total_stake 锁仓总本金
 * @property float $expected_rate 理论利润率
 * @property float $expected_profit 理论利润
 * @property float $actual_rate 实际利润率
 * @property float $actual_profit 实际利润
 * @property integer $locked_at 锁仓时间戳
 * @property integer $settled_at 结算时间戳
 * @property integer $voided_at 作废时间戳
 * @property string $void_reason 作废原因
 * @property integer $created_time 创建时间戳
 * @property integer $updated_time 更新时间戳
 * @property int $status 状态(-1:删除,0:异常,1:待处理,2:已结束,3:已结算)
 */
class PositionModel extends Model
{
    public const PHASE_LOCKED = 1;
    public const PHASE_PENDING_SETTLE = 2;
    public const PHASE_SETTLED = 3;
    public const PHASE_VOIDED = 4;

    public const STATUS_PENDING = 1;
    public const STATUS_FINISHED = 2;

    public $table = 'arbitrage_position';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields = [
        'id',
        'project_id',
        'plan_id',
        'signal_id',
        'fixture_id',
        'event_id',
        'event_name',
        'phase',
        'league',
        'home',
        'away',
        'kickoff_at',
        'leg1_bookmaker',
        'leg1_bookmaker_id',
        'leg1_market',
        'leg1_odds',
        'leg1_stake',
        'leg2_bookmaker',
        'leg2_bookmaker_id',
        'leg2_market',
        'leg2_odds',
        'leg2_stake',
        'total_stake',
        'expected_rate',
        'expected_profit',
        'actual_rate',
        'actual_profit',
        'locked_at',
        'settled_at',
        'voided_at',
        'void_reason',
        'created_time',
        'updated_time',
        'status'
    ];

    public function searchEventNameAttr(Builder $selector,$value){
        return $selector->where('event_name','like','%'.$value.'%');
    }

    public function plan(){
        return $this->hasOne(DayPlanModel::class,'id','plan_id');
    }

    public function fixture(){
        return $this->hasOne(FixtureModel::class,'id','fixture_id');
    }

    public function signal(){
        return $this->hasOne(SignalModel::class,'id','signal_id');
    }

    public function project(){
        return $this->hasOne(ProjectModel::class,'id','project_id');
    }
}
