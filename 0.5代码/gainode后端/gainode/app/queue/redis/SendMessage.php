<?php

namespace app\queue\redis;

use library\service\sys\SendMsgLogService;
use Webman\RedisQueue\Consumer;
use support\extend\Log;

class SendMessage implements Consumer
{
    // 要消费的队列名
    public $queue = 'send_message';

    // 连接名，对应 config/redis_queue.php 里的连接`
    public $connection = 'default';

    /**
     * 消费数据
     * @param $data {log_id,send_type,send_to,title,content,status}
     */
    public function consume($data)
    {
        try{
            if($data['send_type']=='mobile'){
                //$this->sendMobileMsg($data);
            }
            else{
                $this->sendEmailMsg($data);
            }
        }
        catch (\Exception $e){
            Log::channel("queue")->error($e->getMessage(),["type"=>"error"]);
        }
    }

    private function sendEmailMsg($sendMsgObj){
        $service = new SendMsgLogService();
        $mailService = new \support\mailer\SwiftMailer();
        $res = $mailService->send($sendMsgObj['send_to'],$sendMsgObj['title'],$sendMsgObj['content']);
        if(!$res){
            Log::channel('message')->error($mailService->getErrorMsg(),['type'=>'email']);
            $service->updateAll(['id'=>$sendMsgObj['id']],['status'=>2,'result'=>$mailService->getErrorMsg()]);
        }
        else{
            $service->updateAll(['id'=>$sendMsgObj['id']],['status'=>1,'result'=>'发送成功']);
        }
        return $res;
    }
}
