<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id ID
 * @property string $name 名称
 * @property string $code 编码
 * @property string $locale 浏览器语言标识
 * @property string $image 语言图标
 * @property integer $is_default 是否默认
 * @property integer $sort 排序
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:启用,0:停用,-1:删除)
 */
class LangModel extends Model
{
    public $table = 'sys_lang';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"name",
		"code",
		"locale",
		"image",
		"is_default",
		"sort",
		"created_time",
		"updated_time",
		"status",
    ];
}
