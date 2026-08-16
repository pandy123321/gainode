<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 规则ID
 * @property string $ptype 规则类型
 * @property string $v0
 * @property string $v1
 * @property string $v2
 * @property string $v3
 * @property string $v4
 * @property string $v5
 * @property integer $created_time
 */
class CasbinRbacModel extends Model
{
    public $table = 'sys_casbin_rbac';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    const UPDATED_AT = null;
    public $delete_field = null;
    public $fields=[
		"id",
		"ptype",
		"v0",
		"v1",
		"v2",
		"v3",
		"v4",
		"v5",
		"created_time",
    ];
}
