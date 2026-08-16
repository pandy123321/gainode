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

return [
    'listen' => env('APP_LISTEN'),
    'domain' => env('APP_DOMAIN'),
    'transport' => 'tcp',
    'context' => [],
    'name' => 'app_server',
    'count' => cpu_count(),
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',     // 默认为空自动选择Select或者Event，不开启协程
    'stop_timeout' => 2,
    'pid_file' => runtime_path() . '/webman.pid',
    'status_file' => runtime_path() . '/webman.status',
    'stdout_file' => runtime_path() . '/logs/stdout.log',
    'log_file' => runtime_path() . '/logs/workerman.log',
    'max_package_size' => 10 * 1024 * 1024
];
