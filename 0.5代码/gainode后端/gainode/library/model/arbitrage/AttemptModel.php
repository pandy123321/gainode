<?php

namespace library\model\arbitrage;

use support\extend\Model;

/**
 * @property integer $id 主键ID
 * @property integer $user_id 审计字段(存 project_id, 与 plan 关联)
 * @property integer $plan_id 关联日计划ID(arbitrage_day_plan.id)
 * @property integer $signal_id 关联信号ID(arbitrage_signal.id; 0=选信号失败)
 * @property integer $fixture_id 关联比赛ID(arbitrage_fixture.id; 0=未关联)
 * @property integer $window_idx 对应计划窗口下标(=day_plan.schedule数组索引,非next_idx)
 * @property string $exec_status 执行结果: success=成功(成功时也会写position) slippage=滑点 delayed=延迟 market_closed=封盘 limited=限额 odds_reversed=赔率反转 signal_gone=信号消失
 * @property string $stake 本次尝试计算的注额(未成交也可能有值)
 * @property string $profit_rate 本次尝试时的理论利润率(小数)
 * @property string $detail 模拟细节/错误上下文(JSON,如原始模拟参数、失败原因码等)
 * @property integer $created_time 创建时间戳(Unix秒,尝试发生时刻)
 */
class AttemptModel extends Model
{
    public $table = 'arbitrage_attempt';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    /** 本表无软删字段 */
    public $delete_field = '';
    public $fields=[
		"id",
		"user_id",
		"plan_id",
		"signal_id",
		"fixture_id",
		"window_idx",
		"exec_status",
		"stake",
		"profit_rate",
		"detail",
		"created_time",
    ];
}
