<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property string $tb_name 表名称
 * @property string $tb_code 表编码
 * @property string $tb_desc 表格描述
 * @property string $tb_type 表格类型
 * @property string $entity_name 实体类名称
 * @property string $sys_id 模块ID
 * @property string $descr 描述
 * @property integer $is_select 是否可以选择
 * @property integer $is_modify 是否修改
 * @property integer $is_sync 是否同步
 * @property integer $is_operate 是否可以操作
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态
 */
class TableListModel extends Model
{
    public $table = 'sys_table_list';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"tb_name",
		"tb_code",
		"tb_desc",
		"tb_type",
		"entity_name",
		"module_id",
		"descr",
		"is_select",
		"is_modify",
		"is_sync",
		"is_operate",
		"created_time",
		"updated_time",
		"status",
    ];
}
