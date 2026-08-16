<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $user_id 锁定的订单ID
 * @property integer $network_id 网络ID
 * @property string $network_code 网络链编码
 * @property integer $wallet_type 钱包类型(user/deposit/hot/cold)
 * @property string $wallet_address 钱包地址
 * @property string $total_in 入账总额
 * @property string $total_out 出账总额
 * @property integer $success_cnt 成功次数
 * @property string $last_transfer_at 转账时间
 * @property string $private_key 私钥
 * @property string $public_key 公钥
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:不可用,-1:删除)
 */
class Web3NetworkWalletModel extends Model
{
    public $table = 'sys_web3_network_wallet';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    protected $hidden = [
        'private_key',
        'public_key'
    ];

    public $fields=[
		"id",
		"user_id",
		"network_id",
		"network_code",
		"wallet_type",
		"wallet_address",
		"total_in",
		"total_out",
		"success_cnt",
		"last_transfer_at",
		"private_key",
		"public_key",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];

    public function network(){
        return $this->hasOne(Web3NetworkModel::class,'id','network_id');
    }
}
