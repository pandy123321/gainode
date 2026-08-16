<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id 用户等级ID
 * @property string $icon 等级图片
 * @property integer $user_type 用户类型(0:普通用户,1:代理商,2:员工)
 * @property string $name 用户等级名称
 * @property integer $grade 级别
 * @property integer $discount 折扣/收益率百分比
 * @property float $amount 业绩额度
 * @property integer $invite_cnt 邀请人数
 * @property string $descr 等级说明
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:隐藏,-1:删除)
 */
class LevelModel extends Model
{
    public $table = 'member_level';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"icon",
		"user_type",
		"name",
		"grade",
		"discount",
		"amount",
		"invite_cnt",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];
}
