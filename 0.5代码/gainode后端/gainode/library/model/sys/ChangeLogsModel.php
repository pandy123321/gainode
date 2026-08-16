<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 自增id
 * @property string $change_table 修改的表
 * @property integer $primary_id 主键ID
 * @property string $original 原来的值
 * @property string $change 修改的值
 * @property integer $created_time 创建时间
 */
class ChangeLogsModel extends Model
{
    public $table = 'sys_change_logs';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $delete_field = null;
    public $fields=[
		"id",
		"change_table",
		"primary_id",
		"original",
		"change",
		"created_time",
    ];
}
