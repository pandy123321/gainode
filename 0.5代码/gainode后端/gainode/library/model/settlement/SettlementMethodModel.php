<?php

declare(strict_types=1);

namespace library\model\settlement;

use support\extend\Model;

/**
 * settlement_methods 表映射 — 结算方式（05 §3 SettlementMethod，值对象/只读聚合）
 *
 * verification_status 为可变字段（验证状态流转在 State Machine gate 冻结后实现）。
 * 本骨架不实现状态转移，任何流转 MUST FAIL_CLOSED。
 *
 * @property string $method_id 结算方式ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $currency 币种
 * @property string $method_type 结算方式类型
 * @property int $is_default 是否默认
 * @property string $verification_status 验证状态
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class SettlementMethodModel extends Model
{
    public $table = 'settlement_methods';
    public $primaryKey = 'method_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'method_id',
        'user_id',
        'currency',
        'method_type',
        'is_default',
        'verification_status',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
