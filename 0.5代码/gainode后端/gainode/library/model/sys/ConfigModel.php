<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $eid 企业ID(0:平台)
 * @property string $name 键
 * @property string $value 值
 * @property integer $created_time 创建时间
 * @property integer $updated_time 更新时间
 * @property integer $status 状态(1:正常,-1:删除)
 */
class ConfigModel extends Model
{
    public $table = 'sys_config';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"eid",
		"name",
		"value",
		"created_time",
		"updated_time",
		"status",
    ];
}
