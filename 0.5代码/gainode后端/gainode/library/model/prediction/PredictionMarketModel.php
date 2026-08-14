<?php

declare(strict_types=1);

namespace library\model\prediction;

use support\extend\Model;

/**
 * prediction_markets 表映射 — 预测市场（05 §3 Market + §4 Market 状态机）
 *
 * 领域状态机（canonical enum，冻结于 MC1 Canonical State Freeze，禁止自创）：
 *   draft / open / closing / locked / awaiting_result / settlement / settled / void / exception
 *   - settlement（结算处理中）≠ settled（已结算）
 *   - void：作废（赛事取消是原因之一）
 *   - exception：异常
 *
 * @property string $market_id 市场ID(Snowflake，主键)
 * @property string $event_id 赛事ID(引用 Fixture)
 * @property string $template_id 市场模板(P0: FOOTBALL_PREMATCH_1X2)
 * @property string $market_status 市场状态(05 §4 canonical，9态)
 * @property int $lock_at 锁定时间戳(Unix秒)
 * @property string|null $selections 选项定义 JSON(05: [HOME,DRAW,AWAY])
 * @property string|null $liquidity_summary 流动性汇总 JSON(服务端计算)
 * @property string|null $result_status 赛果状态投影(Result.status canonical: provisional/official/disputed/corrected；独立 Result 对象 DDL 后续建立)
 * @property string|null $idempotency_key 幂等键(创建去重)
 * @property string $rule_version 生效规则版本号
 * @property string $parameter_release_id 参数发布版本ID
 * @property string $policy_version 策略版本号
 * @property string $snapshot_id 关联参数快照ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class PredictionMarketModel extends Model
{
    // ---- 市场状态常量（05 §4 canonical，与 MC1 冻结一致）----
    public const MARKET_STATUS_DRAFT = 'draft';
    public const MARKET_STATUS_OPEN = 'open';
    public const MARKET_STATUS_CLOSING = 'closing';
    public const MARKET_STATUS_LOCKED = 'locked';
    public const MARKET_STATUS_AWAITING_RESULT = 'awaiting_result';
    public const MARKET_STATUS_SETTLEMENT = 'settlement';
    public const MARKET_STATUS_SETTLED = 'settled';
    public const MARKET_STATUS_VOID = 'void';
    public const MARKET_STATUS_EXCEPTION = 'exception';

    /** @var string[] 冻结的合法状态全集 */
    public const MARKET_STATUSES = [
        self::MARKET_STATUS_DRAFT,
        self::MARKET_STATUS_OPEN,
        self::MARKET_STATUS_CLOSING,
        self::MARKET_STATUS_LOCKED,
        self::MARKET_STATUS_AWAITING_RESULT,
        self::MARKET_STATUS_SETTLEMENT,
        self::MARKET_STATUS_SETTLED,
        self::MARKET_STATUS_VOID,
        self::MARKET_STATUS_EXCEPTION,
    ];

    // 市场模板（P0 冻结：足球赛前 1X2）
    public const TEMPLATE_FOOTBALL_PREMATCH_1X2 = 'FOOTBALL_PREMATCH_1X2';

    public $table = 'prediction_markets';
    public $primaryKey = 'market_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'market_id',
        'event_id',
        'template_id',
        'market_status',
        'lock_at',
        'selections',
        'liquidity_summary',
        'result_status',
        'idempotency_key',
        'rule_version',
        'parameter_release_id',
        'policy_version',
        'snapshot_id',
        'object_version',
        'created_time',
        'updated_time',
    ];
}
