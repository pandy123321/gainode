<?php

namespace library\validator;
use support\extend\Validator;

class UploadImageValidation extends Validator{

    public $rules =   [
        'callback' => 'required|string',
        'type' => 'required|string',
        'file'=> 'required',
        'url'=> 'required|string',
        'num'=> 'required|integer'
    ];

    // 定义信息
    protected $attributes  =  [
        'callback'=>'回调函数',
        'type'=>'类型',
        'file'=>'文件名',
        'url'=>'文件地址',
        'num' => '序号'
    ];

    protected function file($data){
        $this->setRules([
            'callback'=>'string',
            'type' => 'required|string',
            'file'=> 'required',
            'num'=>'integer'
        ]);
        $this->setAttributes([
            'callback'=>$this->attributes['callback'],
            'type'=>$this->attributes['type'],
            'file' =>$this->attributes['file'],
            'num' =>$this->attributes['num'],
        ]);
        return $this->checkValidate($data);
    }

    protected function curl($data){
        $this->setRules([
            'callback'=>'string',
            'type' => 'required|string',
            'url'=> 'required|string',
            'num'=>'integer'
        ]);
        $this->setAttributes([
            'callback'=>$this->attributes['callback'],
            'type'=>$this->attributes['type'],
            'url' =>$this->attributes['url'],
            'num' =>$this->attributes['num'],
        ]);
        return $this->checkValidate($data);
    }
}
