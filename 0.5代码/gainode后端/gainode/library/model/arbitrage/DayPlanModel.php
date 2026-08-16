<?php

namespace library\model\arbitrage;

use support\extend\Model;

/**
 * @property integer $id 主键ID
 * @property integer $project_id 矿机项目ID
 * @property string $day 交易日
 * @property string $target_amount 当日投入的金额
 * @property string $target_rate 当日目标利率(小数)
 * @property string $target_profit 当日利润目标
 * @property string $realized_profit 已实现利润
 * @property integer $target_trades 计划成交笔数
 * @property integer $done_trades 已成功成交笔数
 * @property string $schedule 计划执行窗口时间戳数组(JSON)
 * @property integer $next_idx 当前窗口游标
 * @property integer $last_attempt_at 上次尝试下单时间戳
 * @property integer $bailout_count 已追加的补救窗口次数
 * @property integer $created_time 创建时间戳
 * @property integer $updated_time 更新时间戳
 * @property integer $status 计划状态: -1=删除 1=待执行 2=执行中 3=已完成 4=已关闭
 */
class DayPlanModel extends Model
{
    public const STATUS_DELETED = -1;
    public const STATUS_PENDING = 1;
    public const STATUS_RUNNING = 2;
    public const STATUS_DONE = 3;
    public const STATUS_CLOSED = 4;

    public $table = 'arbitrage_day_plan';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields = [
        'id',
        'project_id',
        'day',
        'target_amount',
        'target_rate',
        'target_profit',
        'realized_profit',
        'target_trades',
        'done_trades',
        'schedule',
        'next_idx',
        'last_attempt_at',
        'bailout_count',
        'created_time',
        'updated_time',
        'status',
    ];

    /** @return list<int> */
    public function getSchedule(): array
    {
        $raw = $this->schedule;
        if (is_array($raw)) {
            return array_values(array_map('intval', $raw));
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? array_values(array_map('intval', $decoded)) : [];
        }
        return [];
    }

    /**
     * 是否达成日目标：累计利润 ≥ target_profit。
     * 达标后应由引擎将 status 置为 STATUS_DONE(3)，不再进入执行队列。
     */
    public function goalMet(): bool
    {
        $target = round((float) $this->target_profit, 2);
        return $target > 0 && round((float) $this->realized_profit, 2) >= $target;
    }

    /**
     * 当日已实现收益率（小数，相对 target_amount）。
     */
    public function realizedRate(): float
    {
        $amount = (float) $this->target_amount;
        if ($amount <= 0) {
            return 0.0;
        }
        return round((float) $this->realized_profit / $amount, 6);
    }

    /**
     * 计划目标利率是否落在项目日收益区间内（入参为百分比，如 2.80）。
     */
    public function isTargetRateInRange(float $minDayRatePercent, float $maxDayRatePercent): bool
    {
        $min = min($minDayRatePercent, $maxDayRatePercent) / 100.0;
        $max = max($minDayRatePercent, $maxDayRatePercent) / 100.0;
        $rate = (float) $this->target_rate;
        return $rate + 1e-9 >= $min && $rate - 1e-9 <= $max;
    }

    /**
     * 已实现收益率是否仍在项目区间内（允许略低于 min，达标过程中；不得超过 max）。
     */
    public function isRealizedWithinMax(float $maxDayRatePercent): bool
    {
        $max = max(0.0, $maxDayRatePercent) / 100.0;
        return $this->realizedRate() <= $max + 1e-9;
    }

    public function project(){
        return $this->hasOne(ProjectModel::class,'id','project_id');
    }
}
