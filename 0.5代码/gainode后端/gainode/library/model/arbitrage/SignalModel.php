<?php

namespace library\model\arbitrage;

use Illuminate\Database\Eloquent\Builder;
use support\extend\Model;

/**
 * @property integer $id 主键ID
 * @property integer $event_id BetBurger event_id(标识一场比赛)
 * @property integer $fixture_id 关联比赛ID(arbitrage_fixture.id; 0=尚未匹配/未创建占位)
 * @property string $arb_hash 套利组合唯一标识(同一event_id内唯一,用于幂等upsert)
 * @property string $event_name 赛事名称(采集时快照,展示用)
 * @property integer $is_live 是否滚球: 0=赛前 1=滚球
 * @property integer $started_at 比赛开赛时间戳(Unix秒,来自BetBurger)
 * @property string $betburger_pct BetBurger原始收益率(百分比口径,如1.20表示1.2%)
 * @property string $profit_rate 理论利润率(小数口径,如0.0120表示1.20%; 由两腿赔率数学计算)
 * @property integer $leg1_bookmaker_id Leg1博彩公司ID(BetBurger bookmaker_id)
 * @property string $leg1_bookmaker Leg1博彩公司名称(展示名)
 * @property string $leg1_market Leg1玩法名称(如 Over2.5 / Home Win)
 * @property string $leg1_odds Leg1赔率(>1.00)
 * @property string $leg1_market_param Leg1市场参数(如让球/大小球线,无则为NULL)
 * @property integer $leg1_market_type Leg1市场类型(BetBurger market_and_bet_type)
 * @property integer $leg2_bookmaker_id Leg2博彩公司ID(BetBurger bookmaker_id)
 * @property string $leg2_bookmaker Leg2博彩公司名称(展示名)
 * @property string $leg2_market Leg2玩法名称(与Leg1对立结果)
 * @property string $leg2_odds Leg2赔率(>1.00)
 * @property string $leg2_market_param Leg2市场参数(如让球/大小球线,无则为NULL)
 * @property integer $leg2_market_type Leg2市场类型(BetBurger market_and_bet_type)
 * @property string $preview_stake 预览总注额(采集时用固定本金计算两腿分配,非真实下单金额)
 * @property string $current_score 滚球当前比分(如 1-0; 赛前为NULL)
 * @property integer $first_seen_at 首次采集时间戳(Unix秒)
 * @property integer $last_seen_at 最近采集时间戳(Unix秒; 用于过期清理)
 * @property integer $created_time 创建时间戳(Unix秒)
 * @property integer $updated_time 更新时间戳(Unix秒)
 * @property integer $status 信号状态: -1=删除 1=有效 2=已过期 3=已用尽(已成交) 4=已关闭 5=无效(数学校验不通过)
 */
class SignalModel extends Model
{
    public const STATUS_DELETED = -1;
    public const STATUS_VALID = 1;
    public const STATUS_EXPIRED = 2;
    public const STATUS_USED = 3;
    public const STATUS_CLOSED = 4;
    public const STATUS_INVALID = 5;

    public $table = 'arbitrage_signal';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"event_id",
		"fixture_id",
		"arb_hash",
		"event_name",
		"is_live",
		"started_at",
		"betburger_pct",
		"profit_rate",
		"leg1_bookmaker_id",
		"leg1_bookmaker",
		"leg1_market",
		"leg1_odds",
		"leg1_market_param",
		"leg1_market_type",
		"leg2_bookmaker_id",
		"leg2_bookmaker",
		"leg2_market",
		"leg2_odds",
		"leg2_market_param",
		"leg2_market_type",
		"preview_stake",
		"current_score",
		"first_seen_at",
		"last_seen_at",
		"created_time",
		"updated_time",
		"status",
    ];

    public function searchEventNameAttr(Builder $selector,$value){
        return $selector->where('event_name','like','%'.$value.'%');
    }

    public function toM(){
        return [
            'id'                => (int) $this->id,
            'event_id'          => (int) $this->event_id,
            'fixture_id'        => (int) $this->fixture_id,
            'arb_hash'          => (string) $this->arb_hash,
            'event_name'        => (string) $this->event_name,
            'is_live'           => (int) $this->is_live,
            'started_at'        => (int) $this->started_at,
            'betburger_pct'     => (float) $this->betburger_pct,
            'profit_rate'       => (float) $this->profit_rate,
            'leg1_bookmaker_id' => (int) $this->leg1_bookmaker_id,
            'leg1_bookmaker'    => (string) $this->leg1_bookmaker,
            'leg1_market'       => (string) $this->leg1_market,
            'leg1_odds'         => (float) $this->leg1_odds,
            'leg2_bookmaker_id' => (int) $this->leg2_bookmaker_id,
            'leg2_bookmaker'    => (string) $this->leg2_bookmaker,
            'leg2_market'       => (string) $this->leg2_market,
            'leg2_odds'         => (float) $this->leg2_odds,
            'preview_stake'     => (float) $this->preview_stake,
            'current_score'     => (string) $this->current_score,
        ];
    }
}
