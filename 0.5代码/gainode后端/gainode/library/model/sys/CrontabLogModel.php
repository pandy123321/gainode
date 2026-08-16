<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 任务日志ID
 * @property integer $cron_id 任务ID
 * @property string $cron_command 执行命令
 * @property integer $run_start_time 运行开始时间
 * @property integer $run_end_time 运行结束时间
 * @property integer $duration 消耗时间/毫秒
 * @property string $message 日志信息
 * @property string $exception_info 异常信息
 * @property integer $exec_cnt 执行次数
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 执行状态（-1:不符合条件不运行,0:未开始,1:准备运行,2:运行成功,3:运行失败）
 */
class CrontabLogModel extends Model
{
    public $table = 'sys_crontab_log';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"cron_id",
		"cron_command",
		"run_start_time",
		"run_end_time",
		"duration",
		"message",
		"exception_info",
		"exec_cnt",
		"created_time",
		"updated_time",
		"status",
    ];
}
