<?php

declare(strict_types=1);

namespace library\model\otc;

use support\extend\Model;

/**
 * otc_orders 表映射 — OTC 订单（05 §3 OtcOrder + §4 OTC 状态机）
 *
 * 领域状态机（canonical enum，冻结于 MC1 Canonical State Freeze，禁止自创）：
 *   draft / review / matching / partial / completed / cancelled / expired / rejected / disputed
 *   - completed：完整成交
 *   - cancelled：用户/系统主动取消
 *   - expired：有效期自然到期（非取消）
 *   - partial + cancelled/expired 仅释放 remaining
 *   - disputed：保持冻结直到处置
 *   - 不删除/覆盖历史 Trade、APT Ledger、Power Ledger
 *
 * @property string $otc_order_id OTC订单ID(Snowflake，主键)
 * @property string $user_id 用户ID
 * @property string $side 方向(BUY=买入 SELL=卖出)
 * @property string $price 价格(每 APT 单位)
 * @property string $quantity_apt 挂单数量 APT
 * @property string $filled_quantity_apt 已成交数量 APT
 * @property string $remaining_quantity_apt 剩余数量 APT
 * @property string $fee_apt 手续费 APT
 * @property string $power_required 所需 Power(Preview 下发)
 * @property string $power_consumed 已消耗 Power
 * @property string $power_frozen 冻结 Power
 * @property string $status OTC订单状态(05 §4 canonical，9态)
 * @property int $review_required 是否需审核(0=否 1=是)
 * @property string $quote_id 报价ID(关联报价快照)
 * @property string $snapshot_id 关联参数快照ID
 * @property string $rule_version 生效规则版本号
 * @property string $parameter_release_id 参数发布版本ID
 * @property string $policy_version 策略版本号
 * @property string|null $idempotency_key 幂等键(挂单去重)
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class OtcOrderModel extends Model
{
    // ---- 订单状态常量（05 §4 canonical，与 MC1 冻结一致）----
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_MATCHING = 'matching';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DISPUTED = 'disputed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEW,
        self::STATUS_MATCHING,
        self::STATUS_PARTIAL,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
        self::STATUS_REJECTED,
        self::STATUS_DISPUTED,
    ];

    // 方向（DDL enum 冻结）
    public const SIDE_BUY = 'BUY';
    public const SIDE_SELL = 'SELL';

    public $table = 'otc_orders';
    public $primaryKey = 'otc_order_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    public $delete_field = '';

    public $fields = [
        'otc_order_id',
        'user_id',
        'side',
        'price',
        'quantity_apt',
        'filled_quantity_apt',
        'remaining_quantity_apt',
        'fee_apt',
        'power_required',
        'power_consumed',
        'power_frozen',
        'status',
        'review_required',
        'quote_id',
        'snapshot_id',
        'rule_version',
        'parameter_release_id',
        'policy_version',
        'idempotency_key',
        'audit_event_id',
        'object_version',
        'created_time',
        'updated_time',
    ];
}
