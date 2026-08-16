<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id
 * @property string $name 国家中文名称
 * @property string $name_en 国家英文名称
 * @property integer $continent 所在地域
 * @property string $code 二字码
 * @property string $flag 图标
 * @property string $three_code 三字码
 * @property string $dial 电话区号
 * @property integer $sort 排序值
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property integer $status 状态(1:可用,0:隐藏,-1:删除)
 */

class CountryModel extends Model
{
    public $table = 'sys_country';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"name",
		"name_en",
		"continent",
		"code",
		"flag",
		"three_code",
		"dial",
		"sort",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];
}
