<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $recharge_order_id 关联 member_recharge_order.id
 * @property integer $user_id 用户ID
 * @property string $network_code 链编码
 * @property string $token_symbol 代币符号
 * @property string $from_address 用户充值地址
 * @property string $to_address 归集地址
 * @property string $amount 归集数量(显示单位)
 * @property integer $decimals 代币精度
 * @property integer $status 状态:1待处理 2处理中 3成功 4失败
 * @property integer $retry_count 重试次数
 * @property string $next_retry_at 下次重试时间
 * @property string $sweep_tx_hash 归集交易hash
 * @property string $gas_tx_hash 补gas交易hash(EVM)
 * @property string $last_error 最后错误
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 */
class Web3NetworkSweepTaskModel extends Model
{
    public $table = 'sys_web3_network_sweep_task';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"recharge_order_id",
		"user_id",
		"network_code",
		"token_symbol",
		"from_address",
		"to_address",
		"amount",
		"decimals",
		"status",
		"retry_count",
		"next_retry_at",
		"sweep_tx_hash",
		"gas_tx_hash",
		"last_error",
		"created_time",
		"updated_time",
    ];
}
