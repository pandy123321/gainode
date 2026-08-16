<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 自增ID
 * @property string $name 网络名称
 * @property string $code 网络编码
 * @property string $family 链路类型
 * @property integer $chain_id 链路ID
 * @property string $native_symbol 原生符号
 * @property string $native_name 原生名称
 * @property integer $native_decimals 原生精度
 * @property string $rpc_url rpc地址
 * @property string $explorer_url 浏览器网址
 * @property string $icon 图标
 * @property string $is_mainnet 是否主网络（1:是，0:否）
 * @property integer $sort 排序
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:隐藏,-1:删除)
 */
class Web3NetworkModel extends Model
{
    public $table = 'sys_web3_network';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"name",
		"code",
		"family",
        "chain_id",
        "native_symbol",
		"native_name",
        "native_decimals",
		"rpc_url",
		"explorer_url",
        "icon",
		"is_mainnet",
		"sort",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];

    public function tokens(){
        return $this->hasMany(Web3NetworkTokenModel::class,'network_id','id');
    }
}
