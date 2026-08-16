<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 自增ID
 * @property string $dict_code 字典标识码
 * @property string $field_code 字段代码
 * @property string $field_name 字段名称
 * @property string $field_type 字段类型
 * @property string $field_value 字段值
 * @property string $field_required 是否必填
 * @property string $field_tips 字段提示
 * @property integer $field_sort 字段排序
 * @property string $value_range_txt 范围值名称
 * @property string $value_range 范围值
 * @property string $addon 扩展符号
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:正常,-1:删除)
 */
class DictListModel extends Model
{
    public $table = 'sys_dict_list';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"dict_code",
		"field_code",
		"field_name",
		"field_type",
		"field_value",
		"field_required",
		"field_tips",
		"field_sort",
		"value_range_txt",
		"value_range",
		"addon",
		"created_time",
		"updated_time",
		"status",
    ];
}
