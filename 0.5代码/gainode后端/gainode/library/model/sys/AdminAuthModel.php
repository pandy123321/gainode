<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $eid 企业ID(0:平台)
 * @property integer $admin_id 用户ID
 * @property string $terminal 终端类型(pc、mobile、app)
 * @property string $token_type 授权类型
 * @property string $access_token access_token
 * @property string $refresh_token refresh_token
 * @property string $client_ip 客户端ip
 * @property integer $expires_in 刷新失效时间
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $expired_time 失效时间
 * @property integer $status 状态(1:在线,0:不在线,-1:已删除)
 */
class AdminAuthModel extends Model
{
    public $table = 'sys_admin_auth';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"eid",
		"admin_id",
		"terminal",
		"token_type",
		"access_token",
		"refresh_token",
		"client_ip",
		"expires_in",
		"created_time",
		"updated_time",
		"expired_time",
		"status",
    ];

    public function toM(){
        return [
            'user_id'=>$this->admin_id,
            'token'=>$this->access_token,
            'terminal'=>$this->terminal,
        ];
    }
}
