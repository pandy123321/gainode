<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 自增ID
 * @property string $name 字典名称
 * @property string $code 字典标识码
 * @property integer $type 字典类型
 * @property integer $sort 排序值
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:正常,0:隐藏,-1:删除)
 */
class DictModel extends Model
{
    public $table = 'sys_dict';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"name",
		"code",
		"type",
		"sort",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];
}
