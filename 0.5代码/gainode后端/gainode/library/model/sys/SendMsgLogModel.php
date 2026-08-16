<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property string $send_type 发送类型
 * @property string $send_to 发送的终端(手机邮箱)
 * @property string $title 标题
 * @property string $content 内容
 * @property string $result 结果
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(0:未处理,1:发送成功,2:发送失败,-1:删除)
 */
class SendMsgLogModel extends Model
{
    public $table = 'sys_send_msg_log';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"send_type",
		"send_to",
		"title",
		"content",
		"result",
		"created_time",
		"updated_time",
		"status",
    ];
}
