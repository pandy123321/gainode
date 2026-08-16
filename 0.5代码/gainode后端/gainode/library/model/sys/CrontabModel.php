<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 任务ID
 * @property string $name 任务名称
 * @property integer $group_id 任务分组ID
 * @property string $command 执行命令
 * @property string $expression cron执行表达式
 * @property integer $timeout 超时时间(秒)
 * @property integer $is_notify 是否邮件通知
 * @property string $notify_email 通知邮件
 * @property string $descr 描述
 * @property integer $exec_cnt 执行次数
 * @property integer $prev_time 上一次执行时间
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态（1:正常,2:暂停,0:异常,-1:删除）
 */
class CrontabModel extends Model
{
    public $table = 'sys_crontab';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"name",
		"group_id",
		"command",
		"expression",
		"timeout",
		"is_notify",
		"notify_email",
		"descr",
		"exec_cnt",
		"prev_time",
		"created_time",
		"updated_time",
		"status",
    ];
}
