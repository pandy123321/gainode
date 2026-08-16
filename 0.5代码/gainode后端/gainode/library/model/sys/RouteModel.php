<?php

namespace library\model\sys;

use Illuminate\Database\Eloquent\Builder;
use support\extend\Model;

/**
 * @property integer $id
 * @property string $key 路由KEY
 * @property string $sys 模块
 * @property string $controller 控制器
 * @property string $action 操作
 * @property string $method 请求类型
 * @property string $plugins 插件
 * @property string $url URL地址
 * @property string $path 文件类路径
 * @property string $middleware 应用的中间件
 * @property integer $verify 验证权限(0:不需要登陆,1:需要登陆,2:需要登陆和权限,3:仅限超管访问)
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 是否加入菜单表(0:未加入,1:已加入)
 */
class RouteModel extends Model
{
    public $table = 'sys_route';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"key",
		"module",
		"controller",
		"action",
		"method",
		"plugins",
		"url",
		"path",
		"middleware",
		"verify",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];

    public function searchDescrAttr(Builder $selector,$value){
        return $selector->where('descr','like','%'.$value.'%');
    }

    public function searchUrlAttr(Builder $selector,$value){
        return $selector->where('url','like','%'.$value.'%');
    }
}
