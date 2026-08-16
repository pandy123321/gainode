<?php

declare(strict_types=1);

namespace library\model\prediction;

use support\extend\Model;

/**
 * settlement_batches 表映射 — 结算批（05 §3 SettlementBatch + §4 V2.3）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B1-ENUM-01，冻结于 05 §4 V2.3，禁止自创）：
 *   created / processing / completed / partially_failed / failed
 *   - partially_failed：部分失败（批量容器语义），可重试回 processing
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $batch_id 结算批ID(Snowflake，主键)
 * @property string $status 结算批状态(05 §4 V2.3，5态)
 * @property int $market_count 市场数量
 * @property int $order_count 订单数量
 * @property string|null $settlement_ids 结算单ID列表(JSON 数组)
 * @property string $total_principal_apt 本金总额 APT
 * @property string $total_reward_apt 盈利总额 APT
 * @property string $total_service_fee_apt 服务费总额 APT
 * @property int $executed_at 执行时间(Unix秒)
 * @property string $rule_version 生效规则版本号
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class SettlementBatchModel extends Model
{
    // ---- 结算批状态常量（05 §4 V2.3 canonical）----
    public const STATUS_CREATED = 'created';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIALLY_FAILED = 'partially_failed';
    public const STATUS_FAILED = 'failed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_CREATED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_PARTIALLY_FAILED,
        self::STATUS_FAILED,
    ];

    public $table = 'settlement_batches';
    public $primaryKey = 'batch_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'batch_id',
        'status',
        'market_count',
        'order_count',
        'settlement_ids',
        'total_principal_apt',
        'total_reward_apt',
        'total_service_fee_apt',
        'executed_at',
        'rule_version',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];

    /**
     * 结算单集合（同模块 FK，一对多）
     */
    public function settlements()
    {
        return $this->hasMany(SettlementModel::class, 'batch_id', 'batch_id');
    }
}
