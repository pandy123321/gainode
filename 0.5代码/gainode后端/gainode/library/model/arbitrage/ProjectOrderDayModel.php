<?php

namespace library\model\arbitrage;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $user_id 用户ID
 * @property string $income_day 收益日期
 * @property float $project_amount 矿机收益金额
 * @property float $team_amount 团队动态收益
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 0:待结算,1:已结算
 */
class ProjectOrderDayModel extends Model
{
    public $table = 'arbitrage_project_order_day';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"user_id",
		"income_day",
		"project_amount",
		"team_amount",
		"created_time",
		"updated_time",
		"status",
    ];
}
