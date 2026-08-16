<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 自增id
 * @property string $client_ip 访问IP
 * @property integer $user_id 用户ID
 * @property string $country 国家
 * @property integer $total_visit_num 访问次数
 * @property integer $today_visit_num 今日访问次数
 * @property string $last_visit_time 最后访问时间
 * @property integer $limit_type 限制类型(0:不限制,1:黑名单,2:白名单)
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:停用,-1:删除)
 */
class IpVisitModel extends Model
{
    public $table = 'sys_ip_visit';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"client_ip",
		"user_id",
		"country",
		"total_visit_num",
		"today_visit_num",
		"last_visit_time",
		"limit_type",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];
}
