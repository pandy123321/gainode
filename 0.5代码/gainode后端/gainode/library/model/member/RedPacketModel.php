<?php

namespace library\model\member;

use Illuminate\Database\Eloquent\Relations\HasMany;
use support\extend\Model;

/**
 * @property integer $id 红包ID
 * @property string $packet_no 红包编号
 * @property string $title 红包标题
 * @property string $total_amount 红包总金额
 * @property integer $packet_count 红包数量
 * @property integer $remain_count 剩余数量
 * @property string $remain_amount 剩余金额
 * @property integer $packet_type 1随机红包 2固定红包
 * @property integer $status 0待领取,1领取中,2已领取完,3过期,4关闭
 * @property string $start_time 开始时间
 * @property string $expire_time 过期时间
 * @property integer $admin_id 后台管理员
 * @property integer $created_time 创建时间戳(Unix秒)
 * @property integer $updated_time 更新时间戳(Unix秒)
 */
class RedPacketModel extends Model
{
    public $table = 'member_red_packet';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public const STATUS_PENDING = 0;
    public const STATUS_CLAIMING = 1;
    public const STATUS_FINISHED = 2;
    public const STATUS_EXPIRED = 3;
    public const STATUS_CLOSED = 4;

    public $fields=[
		"id",
		"packet_no",
		"title",
		"total_amount",
		"packet_count",
		"remain_count",
		"remain_amount",
		"packet_type",
		"status",
		"start_time",
		"expire_time",
		"admin_id",
		"created_time",
		"updated_time",
    ];

    public function items(){
        return $this->hasMany(RedPacketItemModel::class,'packet_id','id');
    }
}
