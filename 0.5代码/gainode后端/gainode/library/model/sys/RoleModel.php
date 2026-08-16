<?php

namespace library\model\sys;

use library\dao\sys\RoleDao;
use support\extend\Model;

/**
 * @property integer $id 角色ID
 * @property integer $eid 企业ID(0:平台)
 * @property string $name 角色名称
 * @property integer $pid 父级角色
 * @property string $descr 描述
 * @property integer $sort 排序
 * @property string $menu_ids 菜单ID
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $deleted_time 删除时间
 * @property integer $status 状态(1:正常,0:停用,-1:删除)
 */
class RoleModel extends Model
{
    public $table = 'sys_role';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"eid",
		"name",
		"pid",
		"descr",
		"sort",
        "menu_ids",
		"created_time",
		"updated_time",
		"deleted_time",
		"status",
    ];

    protected $appends = [
        'parent_name'
    ];
    public function parent(){
        return $this->hasOne(RoleModel::class,'id','pid');
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

    public function getParentList(){
        $roleDao = new RoleDao();
        $data = $roleDao->getSelectList();
        array_unshift($data,['value'=>0,'label'=>'顶级角色']);
        return $data;
    }
}
