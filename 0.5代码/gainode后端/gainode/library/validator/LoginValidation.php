<?php

namespace library\validator;
use support\extend\Validator;

class LoginValidation extends Validator{

    // 定义规则
    public $rules =   [
		"account"=>"required",
		"password"=>"required",
		"vcode"=>"required",
        "type"=>"required|string",
        'source'=>"required|string",
        "invite_code"=>"string",
        "nickname"=>"string",
        "account_type"=>"required|string",
    ];

    // 定义信息
    protected $attributes  =   [
		"account"=>"登陆账号",
		"password"=>"密码",
        "vcode"=>"验证码",
        "type"=>"类型(mobile:手机,email:邮箱)",
        'source'=>"来源(login:登录,register:注册,forget:忘记密码,bind:绑定,code:验证码登录)",
        "invite_code"=>"邀请码",
        "nickname"=>"昵称",
        "account_type"=>"注册账号类型(email:邮箱,mobile:手机)"
    ];

    //定义场景
    protected $scenes = [
        'login'=>['account','password','vcode'],
        "codeLogin"=>['account','vcode','type','invite_code','source'],
        "forget"=>['account','password','vcode'],
        "sendSmsCode"=>["type","account","source"],
        'register'=>['account','vcode','password','account_type','invite_code','nickname'],
    ];
}
