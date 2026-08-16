<?php

namespace library\response\api;

use support\extend\Response;

/**
 * 上传接口响应体定义
 */
class UploadImageResponse extends Response
{
    protected array $fields = [
        // 分页外层
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

        // 基础字段
        'file_id'            => ['type' => 'int',    'description' => '文件ID'],
        'file_url'           => ['type' => 'string', 'description' => '文件地址'],
        'file_hash'          => ['type' => 'string', 'description' => '文件Hash'],
        'cut'                => ['type' => 'array', 'description' => '裁切尺寸'],
        'type'               => ['type' => 'string', 'description' => '所属类型'],
        'num'                => ['type' => 'int',    'description' => '序号'],
    ];

    protected array $children = [
        'listItem' => ['file_id', 'file_url', 'file_hash', 'type','cut','num'],
    ];

    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data','num'],
        'file' => ['file_id', 'file_url', 'file_hash', 'type','cut','num'],
        'curl' => ['file_id', 'file_url', 'file_hash', 'type','cut','num'],
    ];
}
