<?php
return [
    'enable'       => false,
    'websocket'    => 'websocket://0.0.0.0:3131',
    'api'          => 'http://0.0.0.0:3232',
    'app_key'      => env('WEBMAN_PUSH_APP_KEY', ''),
    'app_secret'   => env('WEBMAN_PUSH_APP_SECRET', ''),
    'channel_hook' => 'http://127.0.0.1:8787/plugin/webman/push/hook',
    'auth'         => '/plugin/webman/push/auth'
];
