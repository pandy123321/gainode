<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 
 * @property string $name lang键名
 * @property integer $parent_id 创建时间
 * @property string $type 类型
 * @property integer $sort 排序值
 * @property string $content 翻译内容
 * @property string $source 来源
 * @property string $create_at 创建时间
 * @property string $update_at 修改时间
 * @property integer $status 状态
 */
class LangKeyModel extends Model
{
    public $table = 'sys_lang_key';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $fields=[
		"id",
		"name",
		"parent_id",
		"type",
		"sort",
		"content",
		"source",
		"create_at",
		"update_at",
		"status",
    ];
}
