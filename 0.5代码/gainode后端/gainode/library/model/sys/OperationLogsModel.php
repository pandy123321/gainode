<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id ID
 * @property string $sys 模块类型
 * @property integer $user_id 操作人
 * @property string $request_url 访问URL
 * @property string $request_method 请求类型
 * @property string $request_data 请求的数据
 * @property string $request_date 记录日期
 * @property string $refer_url 来源URL
 * @property string $client_ip 访问IP
 * @property integer $created_time 创建时间
 */
class OperationLogsModel extends Model
{
    public $table = 'sys_operation_logs';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $delete_field = null;
    public $fields=[
		"id",
		"module",
		"user_id",
		"request_url",
		"request_method",
		"request_data",
		"request_date",
		"refer_url",
		"client_ip",
		"created_time",
    ];

    protected $appends = [
        'user_name'
    ];

    public function getMethodList(){
        return [
            ["label"=>"GET","value"=>"GET"],
            ["label"=>"POST","value"=>"POST"],
            ["label"=>"PUT","value"=>"PUT"],
            ["label"=>"DELETE","value"=>"DELETE"]
        ];
    }

    public function admin(){
        return $this->hasOne(AdminModel::class,'id','user_id');
    }

    public function getUserNameAttribute(){
        if(!empty($this->user_id) && $this->relationLoaded('admin')){
            return $this->admin->account ?? null;
        }
        if(!empty($this->user_id)){
            return $this->admin()->value('account');
        }
        return null;
    }
}
