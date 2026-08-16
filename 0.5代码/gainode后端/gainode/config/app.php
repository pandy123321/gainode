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

use support\Request;

return [
    'debug' => env('APP_DEBUG', false),
    'app_key' => env('APP_KEY', ''),
    'error_reporting' => E_ALL,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
    "sign_private_key"=> env('SIGN_PRIVATE_KEY', ''),
    "pwd_secret_key"=> env('PWD_SECRET_KEY', ''),
    'url_expire'=>60,
    "jwt_key"=> env('JWT_KEY', ''),
    "jwt_iss"=> env('JWT_ISS', 'www.gainode.com'),
    "jwt_expire"=> (24*3600*15),
    "jwt_refresh_expire"=> (24*3600*60),
    'validation_sign'=>[
        'backend'=>["Token","Sign","Timestamp","Version","Language","TraceId"],
        'admin'=>["Token","Sign","Timestamp","Version","Language","TraceId"],
        'api'=>["Token","Sign","Timestamp","Version","Language","TraceId"],
        'common'=>["Token","Sign","Timestamp","Version","Language","TraceId"],
    ],
    'operation_log'=>[
        'backend'=>true,
        'admin'=>true,
        'api'=>true,
        'common'=>true,
    ],
    'limit' => [
        'enable' => true,
        'limit' => 30, // 请求次数
        'window_time' => 10, // 窗口时间，单位：秒
        'status' => 429,  // HTTP 状态码
        'body' => [  // 响应信息
            'status'=>0,
            'code' => 10001,
            'msg' => '请求过于频繁,请稍后再试!',
            'data' => null
        ]
    ],
    'upload' =>[
        'engine'=>'local',
        'max_size'=>1024*1024*1024*5,
        'ext_allow'=>['jpg','jpeg', 'png','gif'],
        'upload_dir'=>null,
        'upload_path'=>null,
        'img_quality'=>100,
        'water_image'=>null,
        'file_path'=>'/'
    ]
];
