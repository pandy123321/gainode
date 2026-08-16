<?php

namespace library\model\arbitrage;

use support\extend\Model;

/**
 * @property integer $id 矿机项目ID
 * @property string $name 矿机项目名称
 * @property string $image 矿机项目图片
 * @property integer $project_day 投资总天数
 * @property string $project_rate 总收益率
 * @property string $project_price 投资金额
 * @property string $min_day_rate 最低日收益率
 * @property string $max_day_rate 最高日收益率
 * @property string $user_amount 购买时用户业绩
 * @property string $start_date 矿机项目开始时间
 * @property integer $user_invite 购买时用户邀请人数
 * @property integer $total_cnt 总库存数量
 * @property integer $limit_num 限购数量
 * @property integer $sales_cnt 销售数量
 * @property integer $position_cnt 购买仓位记录数
 * @property integer $sort 排序
 * @property string $descr 商品描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 项目状态(1:已上架,0:已关闭、-1:已删除)
 */
class ProjectModel extends Model
{
    public const STATUS_ONLINE = 1;
    public const STATUS_OFFLINE = 0;

    public $table = 'arbitrage_project';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"name",
		"image",
		"project_day",
		"project_rate",
		"project_price",
		"min_day_rate",
		"max_day_rate",
		"user_amount",
		"start_date",
		"user_invite",
		"total_cnt",
		"limit_num",
		"sales_cnt",
		"position_cnt",
		"sort",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];
}
