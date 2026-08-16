<?php

namespace library\model\arbitrage;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $order_id 订单ID
 * @property integer $project_id 项目ID
 * @property integer $user_id 购买人用户ID
 * @property integer $plan_id 套利计划ID
 * @property integer $position_id 套利仓位ID
 * @property integer $level 收益级别(0:自己,其他数字:代表第几级分销)
 * @property integer $to_day 第几天收益
 * @property float $money 计算的金额
 * @property float $income_rate 收益率
 * @property integer $income_userid 收益人
 * @property string $income_day 收益日期
 * @property float $income_amount 收益金额
 * @property string $descr 描述
 * @property float $platform_rate 平台手续费百分比
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(0:待执行,1已结算,2:待领取)
 */
class ProjectOrderLogsModel extends Model
{
    public $table = 'arbitrage_project_order_logs';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public const STATUS_PENDING = 0;
    public const STATUS_SETTLED = 1;

    public const STATUS_RECEIVED = 2;

    public $fields=[
		"id",
		"order_id",
		"project_id",
		"user_id",
		"plan_id",
		"position_id",
		"level",
		"to_day",
		"money",
		"income_rate",
		"income_userid",
		"income_day",
		"income_amount",
		"descr",
		"platform_rate",
		"created_time",
		"updated_time",
		"status",
    ];

    public function toM()
    {
        return [
            'order_id'=>$this->order_id,
            'project_id'=>$this->project_id,
            'user_id'=>$this->user_id,
            'plan_id'=>$this->plan_id,
            'position_id'=>$this->position_id,
            'level'=>$this->level,
            'to_day'=>$this->to_day,
            'money'=>$this->money,
            'income_rate'=>$this->income_rate,
            'income_userid'=>$this->income_userid,
            'income_day'=>$this->income_day,
            'income_amount'=>$this->income_amount,
            'descr'=>$this->descr,
        ];
    }
}
