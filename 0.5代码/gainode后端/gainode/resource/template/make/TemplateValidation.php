<?php

namespace library\validator\module;
use support\extend\Validator;

class TemplateValidation extends Validator{

    // 定义规则
    public $rules =   ['rules'];

    // 定义信息
    protected $attributes  =   ['attributes'];

    //定义场景
    protected $scenes = [
        'add'  =>  [],
    ];
}
