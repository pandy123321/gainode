<?php

namespace library\response\api;

use support\extend\Response;

/**
 * 表单配置接口响应体定义
 */
class SchemaFormResponse extends Response
{
    protected array $fields = [
        // 基础字段
        'id'        => ['type' => 'int',    'description' => 'ID'],
        'tb_name'           => ['type' => 'string', 'description' => '表名称'],
        'tb_code'        => ['type' => 'string', 'description' => '表编码'],
        'tb_desc'        => ['type' => 'string', 'description' => '表格描述'],
        'tb_type'        => ['type' => 'string', 'description' => '表格类型'],
        'entity_name'        => ['type' => 'string', 'description' => '实体类名称'],
        'module_id'        => ['type' => 'string', 'description' => '模块ID'],
        'descr'        => ['type' => 'string', 'description' => '描述'],
        'is_select'        => ['type' => 'int', 'description' => '是否支持选择'],
        'is_modify'        => ['type' => 'int', 'description' => '是否修改'],
        'is_sync'        => ['type' => 'int', 'description' => '是否同步'],
        'is_operate'        => ['type' => 'string', 'description' => '是否支持操作'],
        'created_time'        => ['type' => 'int', 'description' => '创建时间'],
        'updated_time'        => ['type' => 'int', 'description' => '修改时间'],
        'status'        => ['type' => 'string', 'description' => '状态'],
    ];

    protected array $children = [];

    protected array $scenes = [
        'list' => ["id","tb_name","tb_code","tb_desc","tb_type","entity_name","module_id","descr","is_select",
            "is_modify","is_sync",'is_operate',"created_time","updated_time","status"],
    ];
}
