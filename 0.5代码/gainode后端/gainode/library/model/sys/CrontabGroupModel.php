<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 分组id
 * @property string $name 分组名称
 * @property integer $sort 排序
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:正常,-1:删除)
 */
class CrontabGroupModel extends Model
{
    public $table = 'sys_crontab_group';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"name",
		"sort",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];
}
