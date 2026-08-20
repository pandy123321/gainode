<?php

namespace library\model\arbitrage;

use Illuminate\Database\Eloquent\Builder;
use support\extend\Model;

/**
 * @property integer $id 主键ID(内部自增,业务层统一引用此字段)
 * @property integer $source 数据来源: 1=API-Football真实比赛 2=BetBurger占位比赛(未匹配到真实赛事时)
 * @property integer $source_id 来源侧唯一ID: source=1时为API-Football fixture_id; source=2时为BetBurger event_id
 * @property integer $betburger_event_id BetBurger event_id; 真实比赛匹配成功后回填; 占位比赛创建时等于source_id
 * @property integer $is_placeholder 是否占位比赛: 0=真实可结算 1=占位(不可结算/不可领取,待升级为真实比赛)
 * @property string $league 联赛名称
 * @property string $home 主队名称
 * @property string $away 客队名称
 * @property string $timezone 比赛时区(如 UTC / Europe/London)
 * @property integer $kickoff_at 开赛时间戳(Unix秒,用于排序/窗口筛选/结算时机判断)
 * @property string $status_short 比赛状态短码: NS=未开赛 LIVE=进行中 FT=完赛 CANC=取消 PST=延期 ABD=腰斩 等
 * @property string $status_long 比赛状态描述(来自API-Football status.long)
 * @property integer $score_home 主队当前比分
 * @property integer $score_away 客队当前比分
 * @property integer $is_finished 是否已完赛: 0=未完赛 1=已完赛(FT/AET/PEN/AWD等终态)
 * @property integer $created_time 创建时间戳(Unix秒)
 * @property integer $updated_time 更新时间戳(Unix秒)
 * @property integer $status 记录状态: 1=正常 -1=删除(软删)
 */
class FixtureModel extends Model
{
    public const SOURCE_API = 1;
    public const SOURCE_PLACEHOLDER = 2;
    public const IS_PLACEHOLDER = 1;
    public const NOT_PLACEHOLDER = 0;
    public const FINISHED = 1;
    public const NOT_FINISHED = 0;
    public const STATUS_NORMAL = 1;
    public const STATUS_DELETED = -1;

    /** 完赛短码 */
    public const FINISHED_SHORT = ['FT', 'AET', 'PEN', 'AWD', 'WO'];
    /** 作废短码 */
    public const VOID_SHORT = ['CANC', 'PST', 'ABD'];

    public $table = 'arbitrage_fixture';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    
    public $fields=[
		"id",
		"source",
		"source_id",
		"betburger_event_id",
		"is_placeholder",
		"league",
		"home",
		"away",
		"timezone",
		"kickoff_at",
		"status_short",
		"status_long",
		"score_home",
		"score_away",
		"is_finished",
		"created_time",
		"updated_time",
		"status",
    ];

    /**
     * 关键词模糊搜索：联赛/主队/客队任一命中。
     */
    public function searchKeywordAttr(Builder $selector, $value)
    {
        $value = (string) $value;
        if ($value === '') {
            return $selector;
        }
        return $selector->where(function (Builder $q) use ($value) {
            $q->where('league', 'like', '%' . $value . '%')
              ->orWhere('home', 'like', '%' . $value . '%')
              ->orWhere('away', 'like', '%' . $value . '%');
        });
    }

    /**
     * 比赛状态友好映射：scheduled=待开赛 live=进行中 finished=已完赛。
     */
    public function searchStatusAttr(Builder $selector, $value)
    {
        $value = strtolower((string) $value);
        $selector = match ($value) {
            'scheduled' => $selector->whereIn('status_short', ['NS', 'TBD']),
            'live'      => $selector->whereIn('status_short', [
                'LIVE', '1H', 'HT', '2H', 'ET', 'BT', 'P', 'SUSP', 'INT',
                'Q1', 'Q2', 'Q3', 'Q4', 'OT',
            ]),
            'finished'  => $selector->where('is_finished', self::FINISHED),
            default     => $selector,
        };
        // 一旦 status 参数参与查询，selector 会跳过软删过滤，这里显式排除已删除记录
        return $selector->where('status', self::STATUS_NORMAL);
    }

    /**
     * 数据时间范围：'YYYY-MM-DD~YYYY-MM-DD'（~ 分隔），映射到 kickoff_at 区间。
     */
    public function searchTimeAttr(Builder $selector, $value)
    {
        $value = (string) $value;
        $parts = preg_split('/\s*~\s*/', trim($value));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            $start = strtotime($parts[0] . ' 00:00:00');
            $end   = strtotime($parts[1] . ' 23:59:59');
            if ($start !== false && $end !== false && $start <= $end) {
                return $selector->whereBetween('kickoff_at', [$start, $end]);
            }
        }
        return $selector;
    }
}
