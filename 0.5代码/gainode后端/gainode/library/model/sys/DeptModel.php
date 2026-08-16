<?php

namespace library\model\sys;

use library\dao\sys\AdminDao;
use library\dao\sys\DeptDao;
use support\extend\Model;

/**
 * @property integer $id id
 * @property integer $eid 企业ID(0:平台)
 * @property string $name 部门名称
 * @property integer $pid 上级部门id
 * @property integer $admin_id 负责人ID
 * @property integer $sort 排序
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $deleted_time 删除时间
 * @property integer $status 状态(1:正常,0:停用,-1:删除)
 */
class DeptModel extends Model
{
    public $table = 'sys_dept';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"eid",
		"name",
		"pid",
		"admin_id",
		"sort",
		"descr",
		"created_time",
		"updated_time",
		"deleted_time",
		"status",
    ];

    protected $appends = [
        'parent_name','admin_name'
    ];
    public function parent(){
        return $this->hasOne(DeptModel::class,'id','pid');
    }

    public function getParentNameAttribute(){
        if(!empty($this->pid) && $this->relationLoaded('parent')){
            return $this->parent->name ?? null;
        }
        if(!empty($this->pid)){
            return $this->parent()->value('name');
        }
        return null;
    }

    public function admin(){
        return $this->hasOne(AdminModel::class,'id','admin_id');
    }

    public function getAdminNameAttribute(){
        if(!empty($this->admin_id) && $this->relationLoaded('admin')){
            return $this->admin->name ?? null;
        }
        if(!empty($this->admin_id)){
            return $this->admin()->value('name');
        }
        return null;
    }

    public function getParentList(){
        $deptDao = new DeptDao();
        $data = $deptDao->getSelectList();
        array_unshift($data,['value'=>0,'label'=>'顶级部门']);
        return $data;
    }

    public function getAdminList(){
        $adminDao = new AdminDao();
        $data = $adminDao->getSelectList();
        array_unshift($data,['value'=>0,'label'=>'暂无']);
        return $data;
    }
}
