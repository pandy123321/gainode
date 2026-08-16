<?php

declare(strict_types=1);

namespace library\model\prediction;

use support\extend\Model;

/**
 * refund_cases 表映射 — 退款案件（05 §3 RefundCase + §4 V2.3）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B1-ENUM-02，冻结于 05 §4 V2.3，禁止自创）：
 *   pending / approved / executing / completed / rejected / failed
 *   - failed：执行失败，可重试回 executing
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $refund_id 退款案件ID(Snowflake，主键)
 * @property string $market_id 市场ID(prediction_markets.market_id)
 * @property int $batch_size 批次订单数
 * @property string $principal_total_apt 退款本金总额 APT
 * @property string $service_fee_total_apt 退款服务费总额 APT
 * @property string $status 退款状态(05 §4 V2.3，6态)
 * @property string $approved_by 批准人 user_id
 * @property int $executed_at 执行时间(Unix秒)
 * @property string|null $ledger_batch_ids 账本批次ID列表(JSON 数组)
 * @property string $reason_code 退款原因码
 * @property string $case_id 关联案件ID
 * @property string $approval_id 关联审批ID
 * @property string $rule_version 生效规则版本号
 * @property string $snapshot_id 关联参数快照ID
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class RefundCaseModel extends Model
{
    // ---- 退款状态常量（05 §4 V2.3 canonical）----
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_EXECUTING = 'executing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_EXECUTING,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_FAILED,
    ];

    public $table = 'refund_cases';
    public $primaryKey = 'refund_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'refund_id',
        'market_id',
        'batch_size',
        'principal_total_apt',
        'service_fee_total_apt',
        'status',
        'approved_by',
        'executed_at',
        'ledger_batch_ids',
        'reason_code',
        'case_id',
        'approval_id',
        'rule_version',
        'snapshot_id',
        'object_version',
        'idempotency_key',
        'audit_event_id',
        'created_time',
        'updated_time',
    ];

    /**
     * 市场归属（同模块 FK）
     */
    public function market()
    {
        return $this->belongsTo(PredictionMarketModel::class, 'market_id', 'market_id');
    }
}
