<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $packet_id 红包ID
 * @property string $item_no 红包序号
 * @property string $amount 金额
 * @property integer $status 0未领取、1已领取
 * @property integer $receive_user_id 领取人用户ID
 * @property string $receive_time 领取时间
 * @property integer $created_time 创建时间戳(Unix秒)
 */
class RedPacketItemModel extends Model
{
    public $table = 'member_red_packet_item';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $fields=[
		"id",
		"packet_id",
		"item_no",
		"amount",
		"status",
		"receive_user_id",
		"receive_time",
		"created_time",
    ];

    protected $append = ['account'];

    public function getAccountAttribute(){
        if(!empty($this->user_id) && $this->relationLoaded('user')){
            return $this->user->account ?? null;
        }
        if(!empty($this->user_id)){
            return $this->user()->value('account');
        }
        return null;
    }

    public function packet(){
        return $this->hasOne(RedPacketModel::class,'packet_id','id');
    }

    public function user(){
        return $this->hasOne(UserModel::class,'receive_user_id','id');
    }
}
