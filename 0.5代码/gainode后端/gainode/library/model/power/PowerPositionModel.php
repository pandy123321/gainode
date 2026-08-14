<?php

declare(strict_types=1);

namespace library\model\power;

use support\extend\Model;

/**
 * power_positions 表映射 — Power 持仓（05 §3 PowerPosition，scalar fields，无状态机）
 *
 * Power 为可消耗、可恢复操作资源。无单独 status enum，使用 scalar fields：
 *   available / frozen / consumed_period / released_period / recovering / limit。
 * 容量（limit）由 Robot 等级决定（power_cap_source_robot_level）。
 *
 * 注意：Power 精确消耗/恢复规则由 Active Rule/Parameter 决定，生产参数未批准（TBC）。
 * 本骨架不实现任何 Power 变更逻辑，变更操作 MUST FAIL_CLOSED 直到规则冻结。
 *
 * @property string $user_id 用户ID(主键，一用户一持仓，引用 member_user.id)
 * @property string $available 可用 Power
 * @property string $frozen 冻结 Power
 * @property string $consumed_period 本周期已消耗 Power
 * @property string $released_period 本周期已释放 Power
 * @property string $recovering 恢复中 Power
 * @property string $limit Power 上限/Cap(注意: limit 为 MySQL 保留字，须反引号)
 * @property int $power_cap_source_robot_level Power Cap 来源 Robot 等级
 * @property int $last_restore_at 上次恢复时间(Unix秒)
 * @property int $next_restore_at 下次恢复时间(Unix秒)
 * @property string $rule_version 生效规则版本号
 * @property string $parameter_release_id 参数发布版本ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class PowerPositionModel extends Model
{
    public $table = 'power_positions';
    public $primaryKey = 'user_id';
    public $connection = 'mysql';

    // 主键为 user_id（引用 member_user.id），非自增；bigint unsigned 字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'user_id',
        'available',
        'frozen',
        'consumed_period',
        'released_period',
        'recovering',
        'limit',
        'power_cap_source_robot_level',
        'last_restore_at',
        'next_restore_at',
        'rule_version',
        'parameter_release_id',
        'object_version',
        'created_time',
        'updated_time',
    ];
}
