<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 
 * @property integer $eid 企业ID(0:平台)
 * @property integer $admin_id 用户ID(0:所有)
 * @property integer $category_id 公告分类
 * @property string $title 标题
 * @property string $content 内容
 * @property integer $sort 排序值
 * @property integer $is_rec 是否推荐
 * @property integer $created_time 创建时间
 * @property integer $updated_time 最后修改时间
 * @property integer $status 状态(1:正常,0:不显示,-1:删除)
 */
class NoticeModel extends Model
{
    public $table = 'sys_notice';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    
    public $fields=[
		"id",
		"eid",
		"admin_id",
		"category_id",
		"title",
		"content",
		"sort",
		"is_rec",
		"created_time",
		"updated_time",
		"status",
    ];
}
