<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property string $tb_name 表名称
 * @property string $fd_name 字段名称
 * @property string $fd_desc 字段描述
 * @property integer $fd_sort 字段排序
 * @property string $fd_type 字段类型
 * @property string $is_null 是否允许为空
 * @property string $is_primary 是否主键
 * @property string $is_list 是否在列表
 * @property string $is_add 是否添加
 * @property string $is_edit 是否编辑
 * @property string $is_query 是否查询
 * @property string $is_required 是否必填
 * @property string $is_sort 是否排序
 * @property string $query_mode 查询模式(eq,neq,gt,egt,lt,elt,like,not_like,in,not_in,between,not_between)
 * @property string $view_type 显示类型(text,password,number,textarea,select,radio,checkbox,datetime,date,upload,editor)
 * @property string $default_value 默认值
 * @property string $width 宽度
 * @property string $fixed 固定类型(left,right,center)
 * @property string $customSlot 定制显示类型(status,avatar)
 * @property string $placeholder 输入描述
 * @property string $colProps 定制显示类型(status,avatar)
 * @property string $model_func 模型层方法
 * @property string $listen_func 前端监听方法
 * @property string $rules 正则验证
 * @property string $module_id 模块ID
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 */
class TableFieldModel extends Model
{
    public $table = 'sys_table_field';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $delete_field = null;
    public $fields=[
		"id",
		"tb_name",
		"fd_name",
		"fd_desc",
		"fd_sort",
		"fd_type",
		"is_null",
		"is_primary",
		"is_list",
		"is_add",
		"is_edit",
		"is_query",
		"is_required",
		"is_sort",
		"query_mode",
		"view_type",
		"default_value",
        "width",
        "fixed",
        "customSlot",
		"placeholder",
        "colProps",
		"model_func",
        "listen_func",
        "rules",
		"module_id",
		"created_time",
		"updated_time",
    ];

    public function getOptions($className){
        $obj = new $className();
        if(method_exists($obj,$this->model_func)){
            return $obj->{$this->model_func}();
        }
        return [];
    }
}
