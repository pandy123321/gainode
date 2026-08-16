<?php

namespace library\model\sys;

use library\dao\sys\DeptDao;
use library\dao\sys\RoleDao;
use support\extend\Model;

/**
 * @property integer $id 用户ID
 * @property integer $eid 企业ID(0:平台)
 * @property string $account 登陆账号
 * @property string $password 密码
 * @property integer $role_id 所属角色
 * @property integer $dept_id 所属部门
 * @property integer $is_admin 是否管理员
 * @property string $encrypt 密钥
 * @property string $name 名字
 * @property string $email 邮箱
 * @property string $mobile 手机号码
 * @property string $avatar 头像地址
 * @property integer $modify_pwd_time 修改密码时间
 * @property integer $login_time 最后登陆时间
 * @property integer $login_cnt 登陆次数
 * @property string $login_ip 登陆IP地址
 * @property integer $is_multiple_login 是否支持多端登录
 * @property string $menu_ids 菜单权限
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $deleted_time 删除时间
 * @property integer $status 状态(1:正常,0:已锁定,-1:已删除)
 */
class AdminModel extends Model
{
    public $table = 'sys_admin';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    protected $hidden = [
        'password'
    ];

    public $fields=[
		"id",
		"eid",
		"account",
		"password",
		"role_id",
		"dept_id",
		"is_admin",
		"encrypt",
		"name",
		"email",
		"mobile",
		"avatar",
		"modify_pwd_time",
		"login_time",
		"login_cnt",
		"login_ip",
        "menu_ids",
		"is_multiple_login",
		"descr",
		"created_time",
		"updated_time",
		"deleted_time",
		"status",
    ];

    public function toM(){
        return [
            'id'=>$this->id,
            'account'=>$this->account,
            'role_id'=>$this->role_id,
            'dept_id'=>$this->dept_id,
            'is_admin'=>$this->is_admin,
            'name'=>$this->name,
            'email'=>$this->email,
            'mobile'=>$this->mobile,
            'avatar'=>$this->avatar,
            'modify_pwd_time'=>$this->modify_pwd_time,
            'login_time'=>$this->login_time,
            'login_cnt'=>$this->login_cnt,
            'login_ip'=>$this->login_ip,
            'created_time'=>$this->getDateTime('created_time')
        ];
    }

    protected $appends = [
        'role_name','dept_name'
    ];
    public function role(){
        return $this->belongsTo(RoleModel::class,'role_id','id');
    }

    public function getRoleNameAttribute(){
        if(!empty($this->role_id) && $this->relationLoaded('role')){
            return $this->role->name ?? null;
        }
        if(!empty($this->role_id)){
            return $this->role()->value('name');
        }
        return null;
    }

    public function dept(){
        return $this->belongsTo(DeptModel::class,'dept_id','id');
    }

    public function getDeptNameAttribute(){
        if(!empty($this->dept_id) && $this->relationLoaded('dept')){
            return $this->dept->name ?? null;
        }
        if(!empty($this->dept_id)){
            return $this->dept()->value('name');
        }
        return null;
    }

    public function getDeptList(){
        $deptDao = new DeptDao();
        return $deptDao->getOptionList();
    }

    public function getRoleList(){
        $roleDao = new RoleDao();
        return $roleDao->getOptionList();
    }

    public function getMultipleStatus(){
        return [
            ['value'=>1,'label'=>'是'],
            ['value'=>0,'label'=>'否'],
        ];
    }

    public function getIsAdminStatus(){
        return [
            ['value'=>1,'label'=>'是'],
            ['value'=>0,'label'=>'否'],
        ];
    }
}
