<?php

namespace library\response\api;

use support\extend\Response;

/**
 * 登录接口响应体定义
 */
class LoginResponse extends Response
{
    protected array $fields = [
        // 基础字段
        'user_id'        => ['type' => 'int',    'description' => '用户ID'],
        'token'           => ['type' => 'string', 'description' => '授权Token'],
        'terminal'        => ['type' => 'string', 'description' => '终端'],
        'account'        => ['type' => 'string', 'description' => '用户账号'],
    ];

    protected array $children = [];

    protected array $scenes = [
        'login' => ['user_id', 'token', 'terminal'],
        'codeLogin' => ['user_id', 'token', 'terminal'],
        'register'=>['user_id','account']
    ];
}
