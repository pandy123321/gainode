<?php

declare(strict_types=1);

namespace library\model\otc;

use support\extend\Model;
use support\exception\RunException;

/**
 * otc_trades 表映射 — OTC 成交事实（05 §3 OtcTrade + §4 V2.3，append-only 单态）
 *
 * 领域状态机（canonical enum，Owner 裁决 2B1-ENUM-04，冻结于 05 §4 V2.3，禁止自创）：
 *   completed（单态，append-only 成交事实）
 *   - 争议/冲正不覆盖 Trade，走 RiskCase + ledger reversal（作用于 otc_orders / apt_ledger_entries）
 *
 * append-only 约束（MC2 Freeze §3.6 + design S01-P02 D.7.4）：
 *   - 成交事实一经写入永不覆盖，物理删除禁止。
 *   - 本表无 updated_time 列；$timestamps=false，且 UPDATED_AT=null 以杜绝任何 ORM/Dao 误写。
 *
 * 机械强制（fail-closed，代码级，非仅注释约定）：
 *   - save() 在已落盘实例（$this->exists）上直接抛 RunException，杜绝实例级 UPDATE 覆盖。
 *   - delete() 直接抛 RunException，杜绝实例级物理删除。
 *   - newEloquentBuilder() 注入 OtcTradeAppendOnlyBuilder，阻断 Eloquent Builder 层
 *     destructive mutation，并经其 __call() 兜底阻断 Query Builder 层转发。
 *   - 配合 OtcTradeDao 对 delete/deleteAll/update/updateAll/updateOrCreate 的覆写。
 *
 * Protection boundary：覆盖「ORM 正常路径」（Model 实例 + Eloquent Builder + DAO）；
 * 底层 Query Builder / DB::table / PDO raw SQL 属数据库直连层，需 DB 级硬约束另走 Change Request。
 *
 * @property string $trade_id 成交ID(Snowflake，主键)
 * @property string $otc_order_id 订单ID(otc_orders.otc_order_id)
 * @property string $buyer_user_id 买方用户ID
 * @property string $seller_user_id 卖方用户ID
 * @property string $quantity_apt 成交数量 APT
 * @property string $price_apt 成交价格 APT
 * @property string $fee_apt 手续费 APT
 * @property string $power_consumed 消耗 Power
 * @property string $status 成交状态(单态 completed)
 * @property string|null $ledger_entry_ids 账本分录ID列表(JSON 数组)
 * @property string $ledger_batch_id 账本批次ID
 * @property string|null $idempotency_key 幂等键
 * @property string $audit_event_id 关联审计事件ID
 * @property int $created_time 创建时间(Unix秒)
 */
class OtcTradeModel extends Model
{
    // ---- 成交状态常量（05 §4 V2.3 canonical，单态）----
    public const STATUS_COMPLETED = 'completed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATUSES = [
        self::STATUS_COMPLETED,
    ];

    public $table = 'otc_trades';
    public $primaryKey = 'trade_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // append-only：无 updated_time 列，禁止任何自动时间戳写入
    public $timestamps = false;

    // 禁止 Dao/ORM 写入 updated_time（本表不存在该列）
    public const UPDATED_AT = null;

    // V2.0 核心实体以 ENUM 冻结领域状态，无 V1.x 的 status 软删字段
    public $delete_field = '';

    public $fields = [
        'trade_id',
        'otc_order_id',
        'buyer_user_id',
        'seller_user_id',
        'quantity_apt',
        'price_apt',
        'fee_apt',
        'power_consumed',
        'status',
        'ledger_entry_ids',
        'ledger_batch_id',
        'idempotency_key',
        'audit_event_id',
        'created_time',
    ];

    /**
     * 注入 append-only Eloquent Builder，阻断 Query Builder 层 destructive mutation。
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return OtcTradeAppendOnlyBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new OtcTradeAppendOnlyBuilder($query);
    }

    /**
     * append-only 成交事实：禁止 UPDATE。
     *
     * @throws RunException
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new RunException(
                'otc_trades 为 append-only 成交事实：禁止 UPDATE 已落盘成交，争议/冲正走 RiskCase + ledger reversal'
            );
        }
        return parent::save($options);
    }

    /**
     * append-only 成交事实：禁止物理删除。
     *
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('otc_trades 为 append-only 成交事实：禁止物理删除成交记录');
    }

    /**
     * 订单归属（同模块 FK）
     */
    public function otcOrder()
    {
        return $this->belongsTo(OtcOrderModel::class, 'otc_order_id', 'otc_order_id');
    }
}
