<?php

namespace process;

use support\extend\Redis;
use Webman\Channel\Client;
use Workerman\RedisQueue\Client as RedisClient;
use Workerman\Timer;

class ChannelServer extends \Channel\Server
{
    /**
     * @var RedisClient
     */
    private $redisClient;

    public function __construct()
    {
//        $this->initSubscribe();
//        $this->redisSubscribe();
    }

    private function redisSubscribe(){
        $this->redisClient = new RedisClient('redis://127.0.0.1:6379');
        $this->redisClient->subscribe('QueueWriteLogs', function($data){
            $this->execTaskJobs($data);
        });
    }

    /**
     * 消息订阅
     */
    private function initSubscribe(){
        Client::connect('127.0.0.1', 2206);
        Client::on('QueueWriteLogs', function($data) {
            $this->execTaskJobs($data);
        });
    }

    private function execTaskJobs($data){

    }

    public function onWorkerStart($worker)
    {
        $this->_worker = $worker;
        $worker->channels = [];
//        Timer::add(5, function (){
//            Client::publish('QueueWriteLogs',['name'=>'test','type'=>'client']);
//            $this->redisClient->send('QueueWriteLogs',['name'=>'test','type'=>'redis']);
//        });
    }
}
