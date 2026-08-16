<?php

namespace library\response\module;
use support\extend\Response;

class TemplateResponse extends Response{

    // 定义信息
    protected array $fields  =   ['attributes'];


    protected array $children = [
        'listItem' => ['scenes'],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'add'  =>  ['scenes'],
        'update'  =>  ['scenes'],
        'detail'  =>  ['scenes'],
    ];
}
