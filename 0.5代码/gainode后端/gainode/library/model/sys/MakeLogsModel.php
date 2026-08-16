<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id ID
 * @property string $type 创建类型
 * @property string $table 创建表名
 * @property string $file_class 文件地址
 * @property integer $is_modify 表结构是否修改
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:正常,-1:删除)
 */
class MakeLogsModel extends Model
{
    public $table = 'sys_make_logs';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"type",
		"table",
		"file_class",
		"is_modify",
		"created_time",
		"updated_time",
		"status",
    ];
}
