<?php

declare(strict_types=1);

namespace library\model\prediction;

use support\extend\Model;

/**
 * prediction_orders 表映射 — 预测订单（05 §3 PredictionOrder + §4 Prediction Order 状态机）
 *
 * 领域状态机（canonical enum，冻结于 MC1 Canonical State Freeze，禁止自创）：
 *   submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected
 *   - RESULT_UNKNOWN 不得混入订单状态（Result 是独立对象）
 *   - correcting / corrected：仅在 settlement error 时触发
 *
 * 注意：asset_status / risk_status 为 05 §4 未定义枚举（TBC，varchar(32) NULL），
 * 业务上 FAIL_CLOSED，待 Contract Freeze 后改 ENUM。本骨架不为其定义状态常量。
 *
 * @property string $order_id 订单ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $market_id 市场ID(prediction_markets.market_id)
 * @property string $selection 投注选项(1X2: HOME/DRAW/AWAY)
 * @property string $amount_apt 参与数量 APT
 * @property string $order_status 订单状态(05 §4 canonical，9态)
 * @property string|null $asset_status 资产状态(05 §4 未定义，TBC)
 * @property string|null $risk_status 风险状态(05 §4 未定义，TBC)
 * @property string $consent_receipt_id 同意确认回执ID
 * @property string $submit_snapshot_id 提交时参数快照ID
 * @property string $parameter_release_id 参数发布版本ID
 * @property string $policy_version 策略版本号
 * @property string|null $idempotency_key 幂等键(下单去重)
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class PredictionOrderModel extends Model
{
    // ---- 订单状态常量（05 §4 canonical，与 MC1 冻结一致）----
    public const ORDER_STATUS_SUBMITTED = 'submitted';
    public const ORDER_STATUS_LOCKED = 'locked';
    public const ORDER_STATUS_AWAITING_RESULT = 'awaiting_result';
    public const ORDER_STATUS_SETTLING = 'settling';
    public const ORDER_STATUS_SETTLED = 'settled';
    public const ORDER_STATUS_REFUNDING = 'refunding';
    public const ORDER_STATUS_REFUNDED = 'refunded';
    public const ORDER_STATUS_CORRECTING = 'correcting';
    public const ORDER_STATUS_CORRECTED = 'corrected';

    /** @var string[] 冻结的合法状态全集 */
    public const ORDER_STATUSES = [
        self::ORDER_STATUS_SUBMITTED,
        self::ORDER_STATUS_LOCKED,
        self::ORDER_STATUS_AWAITING_RESULT,
        self::ORDER_STATUS_SETTLING,
        self::ORDER_STATUS_SETTLED,
        self::ORDER_STATUS_REFUNDING,
        self::ORDER_STATUS_REFUNDED,
        self::ORDER_STATUS_CORRECTING,
        self::ORDER_STATUS_CORRECTED,
    ];

    // 投注选项（1X2，DDL enum 冻结）
    public const SELECTION_HOME = 'HOME';
    public const SELECTION_DRAW = 'DRAW';
    public const SELECTION_AWAY = 'AWAY';

    public $table = 'prediction_orders';
    public $primaryKey = 'order_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'order_id',
        'user_id',
        'market_id',
        'selection',
        'amount_apt',
        'order_status',
        'asset_status',
        'risk_status',
        'consent_receipt_id',
        'submit_snapshot_id',
        'parameter_release_id',
        'policy_version',
        'idempotency_key',
        'audit_event_id',
        'object_version',
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
