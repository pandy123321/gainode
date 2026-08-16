<?php

namespace library\service\sys;

use Carbon\Carbon;
use library\model\sys\CrontabLogModel;
use library\dao\sys\CrontabLogDao;
use support\Container;
use support\extend\Log;
use support\extend\Service;

/**
 * Service
 * @method CrontabLogModel create($data)
 * @method CrontabLogModel updateOrCreate(array $params,array $data)
 * @method CrontabLogModel update($id,array $data){
 * @method CrontabLogModel get($id,string $field = null)
 * @method CrontabLogModel find($id)
 * @method CrontabLogModel findOrFail($id)
 * @method CrontabLogModel firstOrCreate(array $params,array $data)
 * @method CrontabLogModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class CrontabLogService extends Service
{
    public function __construct()
    {
        $this->dao = CrontabLogDao::class;
        parent::__construct();
    }

    function execCommand(CrontabLogModel $obj){
        if($obj->exec_cnt>=3){
            return false;
        }
        $start_time = Carbon::now()->getTimestampMs();
        $obj->saveData([
            'run_start_time'=>$start_time,
            'status'=>1,
            'exec_cnt'=>$obj->exec_cnt+1
        ]);
        $arr = explode('::',$obj->cron_command);
        $status = 2;
        $result = null;
        if(count($arr)<2){
            $output = [];
            $result_code = null;
            $cmd = (string) $obj->cron_command;
            if (!str_starts_with(trim($cmd), 'php ')) {
                $cmd = 'php ' . $cmd;
            }
            // 固定到项目根目录执行，避免 cwd 导致找不到 webman
            @exec('cd ' . escapeshellarg(base_path()) . ' && ' . $cmd . ' 2>&1', $output, $result_code);
            $result = $output;
            if($result_code !== 0){
                $status = 3;
            }
        }
        else{
            $jobObj = Container::get($arr[0]);
            $result = call_user_func([$jobObj,$arr[1]]);
        }
        // message 为 varchar：数组需 json_encode，并截断
        $message = is_string($result)
            ? $result
            : json_encode($result, JSON_UNESCAPED_UNICODE);
        if (is_string($message) && strlen($message) > 480) {
            $message = substr($message, 0, 480);
        }
        $update = [
            'run_end_time'=>Carbon::now()->getTimestampMs(),
            'message'=>$message,
            'status'=>$status
        ];
        $update['duration'] = (int) ($update['run_end_time'] - $start_time);
        Log::channel("crontab")->info('CrontabLogs:success',$update);
        $obj->saveData($update);
    }
}
