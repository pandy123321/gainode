<?php
namespace process;

use library\model\arbitrage\ProjectOrderLogsModel;
use library\service\arbitrage\ProjectOrderLogsService;
use library\service\sys\CrontabLogService;
use Workerman\Timer;

class Task
{
    /**
     * 调度任务
     */
    public function onWorkerStart()
    {
        $crontabService = new CrontabLogService();
        Timer::add(60, function()use($crontabService){
            $this->syncCrontabJobs($crontabService);
        });
        $projectOrderLogService = new ProjectOrderLogsService();
        Timer::add(30, function()use($projectOrderLogService){
            $this->syncProjectOrderLog($projectOrderLogService);
        });

    }

    /**
     * 同步执行定时任务
     */
    private function syncCrontabJobs(CrontabLogService $crontabService){
        $obj = $crontabService->fetch(['status'=>1,'exec_cnt'=>['lt',3]],['id'=>'asc']);
        if(!empty($obj)){
            $crontabService->execCommand($obj);
        }
    }

    private function syncProjectOrderLog(ProjectOrderLogsService $service){
        $rows = $service->fetchAll(['status'=>ProjectOrderLogsModel::STATUS_PENDING,'size'=>10],['id'=>'asc']);
        foreach($rows as $v){
            if(lockApp('arbitrage_project_order_logs_'.$v['id'],1)){
                $service->settleProjectOrderLog($v);
            }
        }
    }
}
