<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $network_id 所属网络
 * @property string $network_code 链编码
 * @property string $contract_address 代币合约地址
 * @property string $symbol 代币符号
 * @property string $name 代币名称
 * @property string $standard 代币标准(ERC20|TRC20|BEP20)
 * @property integer $decimals 代币精度
 * @property integer $is_native 是否原生代币(1=是 0=否)
 * @property integer $confirm_required 入账需确认区块数
 * @property string $icon 币种图标
 * @property integer $is_recharge 是否允许充值
 * @property integer $is_withdraw 是否允许提现
 * @property integer $is_transfer 是否允许划转
 * @property integer $sort 排序(越小越前)
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1=启用 0=禁用)
 */
class Web3NetworkTokenModel extends Model
{
    public $table = 'sys_web3_network_token';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"network_id",
		"network_code",
		"contract_address",
		"symbol",
		"name",
		"standard",
		"decimals",
		"is_native",
		"confirm_required",
		"icon",
		"is_recharge",
		"is_withdraw",
		"is_transfer",
		"sort",
		"created_time",
		"updated_time",
		"status",
    ];

    public function network(){
        return $this->hasOne(Web3NetworkModel::class,'id','network_id');
    }
}
