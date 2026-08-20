<?php

declare(strict_types=1);

namespace library\model\power;

use support\extend\Model;
use support\exception\RunException;

/**
 * power_ledger_entries 表映射 — Power 账本分录（append-only）
 *
 * 独立 Power 流水（Owner 决策 CR-20260818-003），对标 apt_ledger_entries 的防护模型：
 *   - 经济字段（除 state / audit_event_id / object_version 外）一经写入永不覆盖。
 *   - 无 updated_time 列；$timestamps=false，UPDATED_AT=null。
 *   - state 是唯一可变列，仅由 Power 模块 Authoritative Writer 流转，且必须同时追加
 *     append-only 审计事件并更新 audit_event_id。
 *   - 冲正（reversal）通过新增分录 + reversal_of 指向原分录，不删不覆盖原文。
 *
 * 机械强制（fail-closed，代码级）：
 *   - save() 在已落盘实例（$this->exists）上抛 RunException，杜绝实例级 UPDATE 覆盖。
 *   - delete() 抛 RunException，杜绝实例级物理删除。
 *   - newEloquentBuilder() 注入 PowerLedgerEntryAppendOnlyBuilder，阻断 Eloquent Builder 层
 *     destructive mutation。
 *
 * @property string $power_ledger_entry_id 分录ID(Snowflake，主键)
 * @property string $user_id 用户ID(power_positions.user_id)
 * @property string $quantity 变动数量(正数，方向见 entry_direction)
 * @property int $entry_direction 分录方向(1=入账 -1=出账)
 * @property string $entry_type 分录类型(业务事件码，TBC)
 * @property string $state 分录状态(canonical，唯一可变列)
 * @property string $source_object_type 来源对象类型
 * @property string $source_object_id 来源对象ID
 * @property string $journal_batch_id 日记账批次ID
 * @property string $reversal_of 冲正引用
 * @property string|null $idempotency_key 幂等键
 * @property string $rule_version 生效规则版本号
 * @property string $snapshot_id 关联参数快照ID
 * @property string $audit_event_id 关联审计事件ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 */
class PowerLedgerEntryModel extends Model
{
    // ---- 领域状态常量（对齐 apt_ledger_entries，05 §4 canonical）----
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

    // 分录方向（对齐 MC2 Event Catalog，Owner 裁决 #16）
    public const ENTRY_DIRECTION_CREDIT = 1;   // 入账（释放/恢复）
    public const ENTRY_DIRECTION_DEBIT = -1;   // 出账（消耗/冻结）

    public $table = 'power_ledger_entries';
    public $primaryKey = 'power_ledger_entry_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // append-only：无 updated_time 列，禁止任何自动时间戳写入
    public $timestamps = false;
    public const UPDATED_AT = null;

    public $delete_field = '';

    public $fields = [
        'power_ledger_entry_id',
        'user_id',
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
        'object_version',
        'created_time',
    ];

    /**
     * 注入 append-only Eloquent Builder，阻断 Query Builder 层 destructive mutation。
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return PowerLedgerEntryAppendOnlyBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new PowerLedgerEntryAppendOnlyBuilder($query);
    }

    /**
     * append-only 账本：禁止 UPDATE。
     *
     * @throws RunException
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new RunException(
                'power_ledger_entries 为 append-only 账本：禁止 UPDATE 已落盘分录，更正请追加 reversal 分录'
            );
        }
        return parent::save($options);
    }

    /**
     * append-only 账本：禁止物理删除。
     *
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止物理删除分录');
    }

    /**
     * 用户持仓归属（同模块 FK）
     */
    public function position()
    {
        return $this->belongsTo(PowerPositionModel::class, 'user_id', 'user_id');
    }
}
