<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id 
 * @property integer $user_id 用户ID
 * @property string $client_type 第三方类型
 * @property string $client_id 第三方账号
 * @property string $result 授权的数据
 * @property string $client_ip 客户端ip
 * @property integer $bind_time 绑定时间
 * @property integer $created_time 登录时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:正常,-1:已删除)
 */
class UserOauthModel extends Model
{
    public $table = 'member_user_oauth';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    
    public $fields=[
		"id",
		"user_id",
		"client_type",
		"client_id",
		"result",
		"client_ip",
		"bind_time",
		"created_time",
		"updated_time",
		"status",
    ];
}
