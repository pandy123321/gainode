<?php

declare(strict_types=1);

namespace library\model\ledger;

use support\extend\Model;

/**
 * apt_ledger_entries 表映射 — APT 账本分录（05 §3 AptLedgerEntry，append-only）
 *
 * 领域状态机（canonical enum，冻结于 MC1 Canonical State Freeze，禁止自创）：
 *   pending / posted / reversed / disputed
 *
 * append-only 约束（MC1 Freeze §3.6）：
 *   - 经济字段（除 state / audit_event_id 外）一经写入永不覆盖，物理删除禁止。
 *   - 本表无 updated_time 列；$timestamps=false，且 UPDATED_AT=null 以杜绝任何 ORM/Dao 误写。
 *   - state 是唯一可变列，仅由 Ledger 模块 Authoritative Writer（LedgerService）流转，
 *     且必须同时追加 append-only 审计事件并更新 audit_event_id。
 *   - 冲正（reversal）通过新增分录 + reversal_of 指向原分录，不删不覆盖原文。
 *
 * @property string $ledger_entry_id 分录ID(Snowflake，主键)
 * @property string $account_id 账号ID(apt_accounts.account_id)
 * @property string $asset 资产类型(仅 APT-I；APT-C=Future/OUT_OF_SCOPE)
 * @property string $quantity 变动数量(正数，方向见 entry_direction)
 * @property int $entry_direction 分录方向(1=入账 -1=出账；取值 TBC，与 Event Catalog 对齐后冻结)
 * @property string $entry_type 分录类型(业务事件码，TBC)
 * @property string $state 分录状态(05 §4 canonical，唯一可变列)
 * @property string $source_object_type 来源对象类型(如 robot_reward/prediction_order/otc_order)
 * @property string $source_object_id 来源对象ID
 * @property string $journal_batch_id 日记账批次ID
 * @property string $reversal_of 冲正引用(指向被冲正原分录 ledger_entry_id，0=非冲正)
 * @property string|null $idempotency_key 幂等键(写操作去重)
 * @property string $rule_version 生效规则版本号
 * @property string $snapshot_id 关联参数快照ID
 * @property string $audit_event_id 关联审计事件ID(state 流转的证据)
 * @property int $created_time 创建时间(Unix秒)
 */
class AptLedgerEntryModel extends Model
{
    // ---- 领域状态常量（05 §4 canonical，与 MC1 冻结一致）----
    public const STATE_PENDING = 'pending';
    public const STATE_POSTED = 'posted';
    public const STATE_REVERSED = 'reversed';
    public const STATE_DISPUTED = 'disputed';

    /** @var string[] 冻结的合法状态全集 */
    public const STATES = [
        self::STATE_PENDING,
        self::STATE_POSTED,
        self::STATE_REVERSED,
        self::STATE_DISPUTED,
    ];

    // 资产类型（DDL enum 冻结，仅 APT-I；APT-C 为 Future/OUT_OF_SCOPE）
    public const ASSET_APT_I = 'APT-I';

    public $table = 'apt_ledger_entries';
    public $primaryKey = 'ledger_entry_id';
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
        'ledger_entry_id',
        'account_id',
        'asset',
        'quantity',
        'entry_direction',
        'entry_type',
        'state',
        'source_object_type',
        'source_object_id',
        'journal_batch_id',
        'reversal_of',
        'idempotency_key',
        'rule_version',
        'snapshot_id',
        'audit_event_id',
        'created_time',
    ];

    /**
     * 账户归属（同模块 FK）
     */
    public function account()
    {
        return $this->belongsTo(AptAccountModel::class, 'account_id', 'account_id');
    }
}
