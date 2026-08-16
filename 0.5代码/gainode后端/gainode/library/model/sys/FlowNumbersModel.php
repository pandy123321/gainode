<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 自增ID
 * @property string $name 流水单据名称
 * @property string $table 来源表单
 * @property string $prefix 流水前缀
 * @property integer $rule 流水号规则(0:无,1:年,2:年月,3:年月日)
 * @property integer $random 流水号是否随机(0:不随机,1:随机)
 * @property integer $start_val 流水号起始值，最大不超过100
 * @property integer $digit 流水号位数(如：00001)
 * @property string $suffix 流水后缀
 * @property string $sn 流水号值
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:停用,-1:删除)
 */
class FlowNumbersModel extends Model
{
    public $table = 'sys_flow_numbers';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"name",
		"table",
		"prefix",
		"rule",
		"random",
		"start_val",
		"digit",
		"suffix",
		"sn",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];
}
