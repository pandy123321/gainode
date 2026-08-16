<?php

namespace library\validator\sys;
use support\extend\Validator;

class AdminValidation extends Validator{

    // 定义规则
    public $rules =   [
		"eid"=>"required|integer",
		"account"=>"required",
		"password"=>"required|string",
		"role_id"=>"required|integer",
		"dept_id"=>"required|integer",
		"is_admin"=>"required|integer",
		"encrypt"=>"required|string",
		"name"=>"string",
		"email"=>"string",
		"mobile"=>"string",
		"avatar"=>"required|string",
		"modify_pwd_time"=>"required|integer",
		"login_time"=>"required|integer",
		"login_cnt"=>"required|integer",
		"login_ip"=>"required|string",
        "menu_ids"=>"required|string",
		"is_multiple_login"=>"integer",
		"descr"=>"string",
		"deleted_time"=>"required|integer",
		"status"=>"required|integer",
        "page"=>"integer",
        "size"=>"integer",
    ];

    // 定义信息
    protected $attributes  =   [
		"eid"=>"企业ID",
		"account"=>"登陆账号",
		"password"=>"密码",
		"role_id"=>"所属角色",
		"dept_id"=>"所属部门",
		"is_admin"=>"是否管理员",
		"encrypt"=>"密钥",
		"name"=>"名字",
		"email"=>"邮箱",
		"mobile"=>"手机号码",
		"avatar"=>"头像地址",
		"modify_pwd_time"=>"修改密码时间",
		"login_time"=>"最后登陆时间",
		"login_cnt"=>"登陆次数",
		"login_ip"=>"登陆IP地址",
        "menu_ids"=>"权限菜单",
		"is_multiple_login"=>"是否支持多端登录",
		"descr"=>"描述",
		"deleted_time"=>"删除时间",
		"status"=>"状态(1:正常,0:已锁定,-1:已删除)",
        "new_password"=>'新密码',
        "old_password"=>'旧密码',
        "page"=>"分页",
        "size"=>"显示条数",
    ];

    protected function modifyPassword($data){
        $this->setRules([
            'new_password' => 'required|string',
            'old_password'=> 'string',
        ]);
        $this->setAttributes([
            'new_password'=>$this->attributes['new_password'],
            'old_password'=>$this->attributes['old_password']
        ]);
        return $this->checkValidate($data);
    }


    //定义场景
    protected $scenes = [
        'add'  =>  ['account','password','role_id','dept_id','name','email','mobile','descr','is_multiple_login'],
        'update'  =>  ['role_id','dept_id','name','email','mobile','descr','is_multiple_login'],
        'setStatus'  =>  ['status'],
        'setMenuIds'  =>  ['menu_ids'],
    ];
}
