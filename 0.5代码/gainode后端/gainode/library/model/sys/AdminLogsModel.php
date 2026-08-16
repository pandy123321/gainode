<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $eid 企业ID(0:平台)
 * @property integer $admin_id 管理员ID
 * @property string $account 用户账号
 * @property string $token 用户token
 * @property string $action 用户行为
 * @property string $os 操作系统
 * @property string $browser 浏览器类型
 * @property string $client_ip 客户端ip
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 */
class AdminLogsModel extends Model
{
    public $table = 'sys_admin_logs';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $delete_field = null;
    public $fields=[
		"id",
		"eid",
        "admin_id",
		"account",
		"token",
		"action",
		"os",
		"browser",
		"client_ip",
		"descr",
		"created_time",
    ];
}
