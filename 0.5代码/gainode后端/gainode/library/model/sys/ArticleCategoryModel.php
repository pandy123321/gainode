<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 分类id
 * @property integer $eid 企业ID(0:平台)
 * @property string $name 分类名称
 * @property integer $pid 父分类
 * @property integer $sort 排序
 * @property integer $created_time 创建时间
 * @property integer $updated_time 最后修改时间
 * @property integer $status 状态(1:正常,0:不显示,-1:删除)
 */
class ArticleCategoryModel extends Model
{
    public $table = 'sys_article_category';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"eid",
		"name",
		"pid",
		"sort",
		"created_time",
		"updated_time",
		"status",
    ];
}
