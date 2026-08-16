<?php

namespace library\model\arbitrage;

use support\extend\Model;

/**
 * @property integer $id
 * @property string $order_no 订单编号
 * @property integer $user_id 用户ID
 * @property integer $project_id 项目ID
 * @property string $project_name 项目名称
 * @property string $min_day_rate 最低日利率
 * @property string $max_day_rate 最高日利率
 * @property string $amount 订单金额
 * @property string $fee 交易税费
 * @property string $order_status 订单状态(unpaid,pending,partial,paid,refunded,completed,closed)
 * @property string $pay_method 支付方式
 * @property string $pay_amount 已付款金额
 * @property string $paid_at 支付时间
 * @property string $tx_hash 交易Hash
 * @property string $settle_amount 结算金额
 * @property integer $settle_cnt 累计结息次数
 * @property string $last_settle_time 上次结息时间
 * @property integer $is_default 是否默认
 * @property integer $is_lock 是否锁住赎回
 * @property integer $is_calc_money 是否计算用户业绩
 * @property string $descr 备注
 * @property integer $sort 排序
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property string $expires_at 过期时间
 * @property string $cancel_at 取消时间
 * @property integer $status 状态(4:已赎回, 3:已到期, 2:运营中, 1:待审核、0:已取消, -1:失败)
 */
class ProjectOrderModel extends Model
{
    public $table = 'arbitrage_project_order';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    /** 运营中 */
    public const STATUS_PENDING = 1;
    public const STATUS_RUNNING = 2;
    public const STATUS_CLOSED = 3;

    public $fields=[
		"id",
		"order_no",
		"user_id",
		"project_id",
		"project_name",
		"min_day_rate",
		"max_day_rate",
		"amount",
		"fee",
		"order_status",
		"pay_method",
		"pay_amount",
		"paid_at",
		"tx_hash",
		"settle_amount",
		"settle_cnt",
		"last_settle_time",
		"is_default",
		"is_lock",
		"is_calc_money",
		"descr",
		"sort",
		"created_time",
		"updated_time",
		"expires_at",
		"cancel_at",
		"status",
    ];
}
