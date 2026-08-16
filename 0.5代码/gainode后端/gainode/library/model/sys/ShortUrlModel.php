<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property string $code 短网址code
 * @property string $long_url 长网址
 * @property string $long_url_hash 长网址做hash后的值
 * @property integer $request_num 请求次数
 * @property integer $max_num 最大访问次数
 * @property string $client_ip 请求ip
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:停用,-1:删除)
 */
class ShortUrlModel extends Model
{
    public $table = 'sys_short_url';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"code",
		"long_url",
		"long_url_hash",
		"request_num",
		"max_num",
		"client_ip",
		"created_time",
		"updated_time",
		"status",
    ];
}
