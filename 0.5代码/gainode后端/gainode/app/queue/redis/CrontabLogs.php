<?php

namespace app\queue\redis;

use Carbon\Carbon;
use library\service\sys\CrontabLogService;
use support\Container;
use Webman\RedisQueue\Consumer;
use support\extend\Log;

class CrontabLogs implements Consumer
{
    // 要消费的队列名
    public $queue = 'crontab_logs';

    // 连接名，对应 config/redis_queue.php 里的连接`
    public $connection = 'default';

    /**
     * 消费数据
     * @param $data {log_id,job_id,job_command,run_start_time,status}
     */
    public function consume($data)
    {
        $start_time = Carbon::now()->getTimestampMs();
        $service = new CrontabLogService();
        try{
            $obj = $service->get($data['id']);
            $service->execCommand($obj);
        }
        catch (\Throwable $e){
            $update = [
                'run_end_time'=>Carbon::now()->getTimestampMs(),
                'exception_info'=>mb_substr($e->getMessage(), 0, 480),
                'status'=>3
            ];
            $update['duration'] = (int) ($update['run_end_time'] - $start_time);
            Log::channel("crontab")->error('JobLogs:'.$e->getMessage(),$update);
            $service->update($data['id'],$update);
        }
    }
}
