<?php

declare(strict_types=1);

namespace library\model\policy;

use support\extend\Model;

/**
 * consent_receipts 表映射 — 同意回执（05 §3 ConsentReceipt + §4 V2.3）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B1-ENUM-06，冻结于 05 §4 V2.3，禁止自创）：
 *   active / expired（两态）
 *   - 撤回/取代不新增状态值，由新版本 receipt + consent_version 表达
 *   - expired：到期为唯一终态
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $receipt_id 回执ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $consent_type 同意类型
 * @property string $consent_version 同意版本
 * @property string $content_hash 同意内容哈希
 * @property string $status 回执状态(05 §4 V2.3，两态)
 * @property int $agreed_at 同意时间(Unix秒)
 * @property int $expires_at 过期时间(Unix秒)
 * @property string $policy_version 策略版本号
 * @property string|null $idempotency_key 幂等键(consent_type+consent_version 去重)
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class ConsentReceiptModel extends Model
{
    // ---- 回执状态常量（05 §4 V2.3 canonical）----
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
    ];

    public $table = 'consent_receipts';
    public $primaryKey = 'receipt_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'receipt_id',
        'user_id',
        'consent_type',
        'consent_version',
        'content_hash',
        'status',
        'agreed_at',
        'expires_at',
        'policy_version',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];
}
