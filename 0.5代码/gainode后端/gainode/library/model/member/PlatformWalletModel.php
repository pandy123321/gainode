<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id 
 * @property string $currency 币种
 * @property string $balance 当前余额
 * @property string $total_in 累计入账
 * @property string $total_out 累计出账
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 */
class PlatformWalletModel extends Model
{
    public $table = 'member_platform_wallet';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    
    public $fields=[
		"id",
		"currency",
		"balance",
		"total_in",
		"total_out",
		"created_time",
		"updated_time",
    ];
}
