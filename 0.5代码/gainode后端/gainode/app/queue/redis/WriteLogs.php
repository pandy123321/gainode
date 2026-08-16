<?php

namespace app\queue\redis;

use library\service\sys\IpVisitService;
use library\service\sys\OperationLogsService;
use support\extend\Cache;
use support\extend\Log;
use support\extend\Redis;
use Webman\RedisQueue\Consumer;

class WriteLogs implements Consumer
{
    // 要消费的队列名
    public $queue = 'write_logs';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        try{
            if(!$this->isFilterUrl($data['request_url'])){
                $logService = new OperationLogsService();
                $logService->create($data);
                if($data['module']=='api'){
                    $ipVisitService = new IpVisitService();
                    $exists = Cache::get('visit_ip_'.$data['client_ip']);
                    $ipVisitObj = $ipVisitService->get($data['client_ip'],'client_ip');
                    if(empty($ipVisitObj) && empty($exists)){
                        Cache::set('visit_ip_'.$data['client_ip'],1);
                        $ipVisitService->createIpVisit([
                            'client_ip'=>$data['client_ip'],
                            'user_id'=>$data['user_id'],
                            'last_visit_time'=>getCurrentDate('date')
                        ]);
                    }
                    else{
                        $cache_key = 'visit_ip';
                        Redis::hIncrBy($cache_key,$data['client_ip'],1);
                    }
                }
            }
        }
        catch (\Exception $e){
            Log::channel("queue")->error($e->getMessage(),["type"=>"operation_log"]);
        }
    }

    public function isFilterUrl($url){
        $data = ['/api/login','/api/register'];
        return in_array($url,$data);
    }
}
