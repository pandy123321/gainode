<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $eid 企业ID(0:平台)
 * @property integer $user_id 用户ID
 * @property string $account 用户账号
 * @property string $token 用户token
 * @property string $action 用户行为
 * @property string $os 操作系统
 * @property string $browser 浏览器类型
 * @property string $user_agent 访问标识
 * @property string $client_ip 客户端ip
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 */
class UserLogsModel extends Model
{
    public $table = 'member_user_logs';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $fields=[
		"id",
		"eid",
		"user_id",
		"account",
		"token",
		"action",
		"os",
		"browser",
		"user_agent",
		"client_ip",
		"descr",
		"created_time",
    ];
}
