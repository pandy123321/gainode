<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property string $platform 所属平台
 * @property string $name 菜单名称
 * @property integer $type 模块类型(0:导航,1:目录,2:菜单,3:按钮,4:接口)
 * @property integer $pid 上级菜单ID
 * @property string $path 菜单路径
 * @property string $icon 图标
 * @property string $btn_style 颜色标识(default, red, blue, green, yellow, purple, dark)
 * @property string $route_key 接口路由标识符
 * @property string $route_url 前端路由地址
 * @property string $params 参数
 * @property integer $choice_ids 选择数据操作(0:不需选择,1:只能选择一个,2:可选择多个)
 * @property string $descr 描述
 * @property integer $sort 排序值
 * @property integer $is_show 是否显示(1:显示,0:隐藏)
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:正常,0:停用,-1:删除)
 */
class MenusModel extends Model
{
    public $table = 'sys_menus';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"platform",
		"name",
		"type",
		"pid",
		"path",
		"icon",
		"btn_style",
		"route_key",
		"route_url",
		"component",
		"choice_ids",
		"descr",
		"sort",
		"is_show",
		"created_time",
		"updated_time",
		"status",
    ];

    public function route(){
        return $this->hasOne(RouteModel::class,'key','route_key',);
    }
}
