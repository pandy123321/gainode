<?php

declare(strict_types=1);

namespace library\model\prediction;

use support\extend\Model;

/**
 * settlements 表映射 — 结算单（05 §3 Settlement + §4 Settlement 状态机）
 *
 * 领域状态机（canonical enum，冻结于 05 §4 V2.3，禁止自创）：
 *   queued / calculating / review / payable / paid / failed
 *   - queued/calculating/payable：结算处理中
 *   - paid：已结算（唯一"已结算"真值）
 *   - review：人工复核
 *   - failed：结算失败（异常，可重试）
 *
 * 关键不变量：Result `official` ≠ Settlement `paid`；Result confirmer ≠ Settlement approver。
 *
 * 本骨架不实现状态转移（属 State Machine gate）。转移矩阵 FROZEN 前，
 * 任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $settlement_id 结算单ID(Snowflake，主键)
 * @property string $market_id 市场ID(prediction_markets.market_id)
 * @property string $batch_id 结算批ID(settlement_batches.batch_id)
 * @property string $status 结算状态(05 §4 canonical，6态)
 * @property string $principal_total_apt 本金总额 APT
 * @property string $reward_total_apt 盈利总额 APT
 * @property string $service_fee_total_apt 服务费总额 APT
 * @property string $ledger_batch_id 关联账本批次ID
 * @property string $approved_by 批准人 user_id
 * @property int $executed_at 执行时间(Unix秒)
 * @property string $rule_version 生效规则版本号
 * @property string $parameter_release_id 参数发布版本ID
 * @property string $snapshot_id 关联参数快照ID
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class SettlementModel extends Model
{
    // ---- 结算状态常量（05 §4 V2.3 canonical）----
    public const STATUS_QUEUED = 'queued';
    public const STATUS_CALCULATING = 'calculating';
    public const STATUS_REVIEW = 'review';
    public const STATUS_PAYABLE = 'payable';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_CALCULATING,
        self::STATUS_REVIEW,
        self::STATUS_PAYABLE,
        self::STATUS_PAID,
        self::STATUS_FAILED,
    ];

    public $table = 'settlements';
    public $primaryKey = 'settlement_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'settlement_id',
        'market_id',
        'batch_id',
        'status',
        'principal_total_apt',
        'reward_total_apt',
        'service_fee_total_apt',
        'ledger_batch_id',
        'approved_by',
        'executed_at',
        'rule_version',
        'parameter_release_id',
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

    /**
     * 结算批归属（同模块 FK）
     */
    public function batch()
    {
        return $this->belongsTo(SettlementBatchModel::class, 'batch_id', 'batch_id');
    }
}
