<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

global $argv;

$process = [];

$list = env('APP_PROCESS_LIST');
if(empty($list)){
    return $process;
}
$config = explode(',',$list);
if(in_array('file_monitor',$config)){
    // File update detection and automatic reload
    $process['file_monitor'] = [
        'handler' => process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitorDir' => array_merge([
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            // Files with these suffixes will be monitored
            'monitorExtensions' => [
                'php', 'html', 'htm', 'env'
            ],
            'options' => [
                'enable_file_monitor' => !in_array('-d', $argv) && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ];
}
if(in_array('task_server',$config)){
    $process['task_server'] = [
        'handler' => \Webman\App::class,
        'listen' => 'http://0.0.0.0:8786',
        'count' => 2, // 进程数
        'user' => '',
        'group' => '',
        'reusePort' => false,
        'eventLoop' => '', // 默认为空自动选择Select或者Event，不开启协程
        'constructor' => [
            'requestClass' => \support\Request::class, // request类设置
            'logger' => \support\Log::channel('default'), // 日志实例
            'appPath' => app_path(), // app目录位置
            'publicPath' => public_path() // public目录位置
        ]
    ];
}
if(in_array('coroutine_server',$config)){
    $process['coroutine_server'] = [
        'handler' => \Webman\App::class,
        'listen' => 'http://0.0.0.0:8686',
        'count' => 1,
        'user' => '',
        'group' => '',
        'reusePort' => false,
        // 开启协程需要设置为 Workerman\Events\Swoole::class 或者 Workerman\Events\Swow::class 或者 Workerman\Events\Fiber::class
        'eventLoop' => Workerman\Events\Swoole::class,
        'context' => [],
        'constructor' => [
            'requestClass' => \support\Request::class, // request类设置
            'logger' => \support\Log::channel('default'), // 日志实例
            'appPath' => app_path(),
            'publicPath' => public_path()
        ]
    ];
}
if(in_array('crontab_task',$config)){
    $process['crontab_task'] = [
        'handler'  => process\CrontabTask::class,
        'count'       => 1, // 进程数
    ];
}
if(in_array('channel_server',$config)){
    $process['channel_server'] = [
        'handler' => process\ChannelServer::class,
        'listen'  => 'frame://0.0.0.0:2206',
        'reloadable' => false,
        'count' => 1,   // 必须是1
    ];
}
if(in_array('proxy_server',$config)){
    $process['proxy'] = [
        'handler' => \process\Proxy::class,
        'listen' => 'http://0.0.0.0:8989',
        'count' => cpu_count(),
        'reloadable' => false,
    ];
}
if(in_array('pusher_server',$config)){
    $process['pusher'] = [
        'handler' => \process\Pusher::class,
        // 监听的协议 ip 及端口 （可选）
        'listen'  => 'websocket://0.0.0.0:8888',
        'AuthConnections'=>[],
        // 进程数 （可选，默认1）
        'count'   => 2,
        // 进程运行用户 （可选，默认当前用户）
        'user'    => '',
        // 进程运行用户组 （可选，默认当前用户组）
        'group'   => '',
        // 当前进程是否支持reload （可选，默认true）
        'reloadable' => true,
        // 是否开启reusePort
        'reusePort'  => true,
        // transport (可选，当需要开启ssl时设置为ssl，默认为tcp)
        'transport'  => 'tcp',
        // context （可选，当transport为是ssl时，需要传递证书路径）
        'context'    => [],
        // 进程类构造函数参数，这里为 process\Pusher::class 类的构造函数参数 （可选）
        'constructor' => [
//            'ssl' => [
//                'local_pk'    => env('SOCKET_SSL_PK'),
//                'local_cert'  => env('SOCKET_SSL_CERT'),
//                'verify_peer' => false
//            ]
        ],
        //当前进程是否启用
        'enable' => true
    ];
}
$process['task'] = [
    'handler' => process\Task::class,
    'count'       => 1, // 进程数
];
if(in_array('arb_task',$config)){
    $process['arb_task'] = [
        'handler' => process\ArbitrageTask::class,
        'count'       => 1, // 进程数
    ];
}
//$process['bsc_task'] = [
//    'handler' => process\BscListenTask::class,
//    'count'       => 1, // 进程数
//];
//$process['eth_task'] = [
//    'handler' => process\EthListenTask::class,
//    'count'       => 1, // 进程数
//];
//$process['tron_task'] = [
//    'handler' => process\TronListenTask::class,
//    'count'       => 1, // 进程数
//];
return $process;
