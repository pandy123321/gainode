<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 
 * @property integer $key_id lang_key表id
 * @property string $lang lang语言
 * @property string $content 翻译后的文字值
 * @property string $create_at 创建时间
 * @property string $update_at 修改时间
 */
class LangValueModel extends Model
{
    public $table = 'sys_lang_value';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $fields=[
		"id",
		"key_id",
		"lang",
		"content",
		"create_at",
		"update_at",
    ];
}
