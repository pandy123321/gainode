<?php

namespace library\response\sys;

use support\extend\Response;

/**
 * 后台账号接口响应体定义
 *
 * children 可复用的子字段组：
 *   listItem — 列表项（分页 data.data[]）
 *
 * 使用 dot notation 实现多层嵌套：
 *   role.id / role.name          → role { id, name }           (2层)
 *   role.permissions.id          → role { permissions { id } } (3层)
 */
class AdminResponse extends Response
{
    protected array $fields = [
        // 分页外层
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

        // 基础字段
        'id'                => ['type' => 'int',    'description' => '用户ID'],
        'account'           => ['type' => 'string', 'description' => '登陆账号'],
        'name'              => ['type' => 'string', 'description' => '名字'],
        'email'             => ['type' => 'string', 'description' => '邮箱'],
        'mobile'            => ['type' => 'string', 'description' => '手机号码'],
        'avatar'             => ['type' => 'string', 'description' => '头像地址'],
        'role_id'           => ['type' => 'int',    'description' => '所属角色ID'],
        'role_name'         => ['type' => 'string', 'description' => '角色名称'],
        'dept_id'           => ['type' => 'int',    'description' => '所属部门ID'],
        'dept_name'         => ['type' => 'string', 'description' => '部门名称'],
        'is_admin'          => ['type' => 'int',    'description' => '是否管理员(1是0否)'],
        'status'            => ['type' => 'int',    'description' => '状态(1正常 0锁定 -1删除)'],
        'is_multiple_login' => ['type' => 'int',  'description' => '是否多端登录(1是0否)'],
        'menu_ids'          => ['type' => 'string',  'description' => '菜单权限'],
        'login_time'        => ['type' => 'string', 'description' => '最后登陆时间'],
        'login_cnt'         => ['type' => 'int',    'description' => '登陆次数'],
        'login_ip'          => ['type' => 'string', 'description' => '登陆IP地址'],
        'created_time'      => ['type' => 'string', 'description' => '创建时间'],
        'updated_time'      => ['type' => 'string', 'description' => '修改时间'],
        'descr'             => ['type' => 'string', 'description' => '描述'],

        // --- dot notation 上层节点元数据 ---
//        'role'              => ['type' => 'object', 'description' => '所属角色'],
//        'dept'              => ['type' => 'object', 'description' => '所属部门'],
//        'permissions'       => ['type' => 'object', 'description' => '权限信息'],

        // --- dot notation 叶子字段精确元数据 ---
//        'role.id'           => ['type' => 'int',    'description' => '角色ID'],
//        'role.name'         => ['type' => 'string', 'description' => '角色名称'],
//        'dept.id'           => ['type' => 'int',    'description' => '部门ID'],
//        'dept.name'         => ['type' => 'string', 'description' => '部门名称'],
//        'role.permissions.id'   => ['type' => 'int',    'description' => '权限ID'],
//        'role.permissions.name' => ['type' => 'string', 'description' => '权限名称'],
//        'role.id', 'role.name', 'dept.id', 'dept.name', 'role.permissions.id', 'role.permissions.name',

    ];

    protected array $children = [
        'listItem' => ['id', 'account', 'name', 'email','role_id','role_name','dept_id','dept_name','mobile', 'avatar','is_admin','is_multiple_login', 'status', 'login_time', 'login_cnt', 'created_time'],
    ];

    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail' => ['id', 'account', 'name', 'email','role_id','dept_id', 'mobile', 'avatar','is_admin', 'is_multiple_login',"menu_ids", 'status', 'login_time', 'login_cnt', 'login_ip', 'created_time', 'updated_time', 'descr'],
        'add' => ['id', 'account', 'name', 'email','role_id','dept_id', 'mobile', 'avatar','is_admin', 'is_multiple_login',"menu_ids", 'status', 'login_time', 'login_cnt', 'login_ip', 'created_time', 'updated_time', 'descr'],
        'update' => ['id', 'account', 'name', 'email','role_id','dept_id', 'mobile', 'avatar','is_admin', 'is_multiple_login',"menu_ids", 'status', 'login_time', 'login_cnt', 'login_ip', 'created_time', 'updated_time', 'descr'],
    ];
}
