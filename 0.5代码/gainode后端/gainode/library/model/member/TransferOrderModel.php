<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id 
 * @property integer $user_id 用户ID
 * @property string $order_no 划转单号
 * @property string $from_wallet 来源账户类型
 * @property string $to_wallet 目标账户类型
 * @property string $currency 币种
 * @property string $amount 划转金额
 * @property integer $status 0=失败 1=成功
 * @property string $remark 备注
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 */
class TransferOrderModel extends Model
{
    public $table = 'member_transfer_order';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    
    public $fields=[
		"id",
		"user_id",
		"order_no",
		"from_wallet",
		"to_wallet",
		"currency",
		"amount",
		"status",
		"remark",
		"created_time",
		"updated_time",
    ];
}
