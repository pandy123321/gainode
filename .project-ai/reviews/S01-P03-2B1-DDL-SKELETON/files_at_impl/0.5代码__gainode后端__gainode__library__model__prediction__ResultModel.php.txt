<?php

declare(strict_types=1);

namespace library\model\prediction;

use support\extend\Model;

/**
 * results 表映射 — 预测结果（05 §3 Result + §4 Result 状态机）
 *
 * 领域状态机（canonical enum，冻结于 05 §4 V2.3，禁止自创）：
 *   provisional / official / disputed / corrected
 *   - provisional：结果待确认
 *   - official：已确认（触发结算）
 *   - disputed：争议（冻结结算）
 *   - corrected：纠错后（触发重结算）
 *   - corrected 仅一次（MC2 Owner 裁决 #11）
 *
 * 关键不变量：Result `official` ≠ Settlement `paid`。
 *
 * 本骨架不实现状态转移（属 Machine Contract 第二批 State Machine gate）。
 * 转移矩阵 FROZEN 前，任何状态流转 MUST FAIL_CLOSED。
 *
 * @property string $result_id 结果ID(Snowflake，主键)
 * @property string $market_id 市场ID(prediction_markets.market_id)
 * @property string $event_id 赛事事件ID
 * @property string|null $scores 比分(JSON，如 {"home":2,"away":1})
 * @property string $outcome 结果(1X2: HOME/DRAW/AWAY)
 * @property string $status 结果状态(05 §4 canonical，4态)
 * @property string $confirmed_by 确认人 user_id
 * @property int $confirmed_at 确认时间(Unix秒)
 * @property string|null $evidence_ids 证据ID列表(JSON 数组)
 * @property string $dispute_reason_code 争议原因码
 * @property int $correction_version 纠错版本号
 * @property string $rule_version 生效规则版本号
 * @property string $snapshot_id 关联参数快照ID
 * @property string|null $idempotency_key 幂等键(确认去重)
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class ResultModel extends Model
{
    // ---- 结果状态常量（05 §4 V2.3 canonical）----
    public const STATUS_PROVISIONAL = 'provisional';
    public const STATUS_OFFICIAL = 'official';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_CORRECTED = 'corrected';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_PROVISIONAL,
        self::STATUS_OFFICIAL,
        self::STATUS_DISPUTED,
        self::STATUS_CORRECTED,
    ];

    // 结果选项（1X2，与 PredictionOrder.selection 对齐，DDL enum 冻结）
    public const OUTCOME_HOME = 'HOME';
    public const OUTCOME_DRAW = 'DRAW';
    public const OUTCOME_AWAY = 'AWAY';

    public $table = 'results';
    public $primaryKey = 'result_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'result_id',
        'market_id',
        'event_id',
        'scores',
        'outcome',
        'status',
        'confirmed_by',
        'confirmed_at',
        'evidence_ids',
        'dispute_reason_code',
        'correction_version',
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
