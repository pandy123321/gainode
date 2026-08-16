<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 
 * @property integer $article_id 内容 ID
 * @property string $lang 语言
 * @property string $title 标题
 * @property string $content 内容
 * @property integer $created_time 创建时间
 * @property integer $updated_time 最后修改时间
 */
class ArticleLangModel extends Model
{
    public $table = 'sys_article_lang';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    
    public $fields=[
		"id",
		"article_id",
		"lang",
		"title",
		"content",
		"created_time",
		"updated_time",
    ];
}
