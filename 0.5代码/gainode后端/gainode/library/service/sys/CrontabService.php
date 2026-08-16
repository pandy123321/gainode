<?php

namespace library\service\sys;

use library\dict\QueueDict;
use library\model\sys\CrontabLogModel;
use library\model\sys\CrontabModel;
use library\dao\sys\CrontabDao;
use support\exception\RunException;
use support\extend\Service;

/**
 * Service
 * @method CrontabModel create($data)
 * @method CrontabModel updateOrCreate(array $params,array $data)
 * @method CrontabModel update($id,array $data){
 * @method CrontabModel get($id,string $field = null)
 * @method CrontabModel find($id)
 * @method CrontabModel findOrFail($id)
 * @method CrontabModel firstOrCreate(array $params,array $data)
 * @method CrontabModel fetch(array $params=[],array $orderBy=[],array $fields=[])
 */
class CrontabService extends Service
{
    public function __construct()
    {
        $this->dao = CrontabDao::class;
        parent::__construct();
    }

    /**
     * 获取所有的任务脚本
     * @return array
     */
    public function getJobCommandList(){
        return $this->pluck('job_command',['status'=>1]);
    }

    public function getRunCrondList(){
        return $this->fetchAll(['status'=>1]);
    }

    /**
     * 手动执行任务
     * @param $id
     */
    public function execCrontabLogs(CrontabModel $cronObj){
        if($cronObj->status!=1){
            throw new RunException('只能执行正常的任务');
        }
        $data = [
            'cron_id'=>$cronObj->id,
            'cron_command'=>$cronObj->command,
            'run_start_time'=>microtime(true)*1000,
        ];
        $crondLogService = new CrontabLogService();
        $crondLogsObj = $crondLogService->create($data);
        if(!empty($crondLogsObj)){
            $cronObj->saveData(['prev_time'=>time(),'exec_cnt'=>($cronObj->exec_cnt+1)]);
            pushQueue(QueueDict::QUEUE_CRONTAB_LOGS, $crondLogsObj->toArray());
        }
        return true;
    }
}
