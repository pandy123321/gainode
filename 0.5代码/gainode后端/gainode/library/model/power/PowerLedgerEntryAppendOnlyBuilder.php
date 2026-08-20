<?php

declare(strict_types=1);

namespace library\model\power;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use support\exception\RunException;

/**
 * power_ledger_entries 专用 append-only Eloquent Builder。
 *
 * 阻断所有 destructive mutation，只保留 INSERT（追加）与只读查询。
 * 对标 AptLedgerEntryAppendOnlyBuilder（apt_ledger_entries）的 deny set 与 __call() 兜底。
 *
 * 安全声明边界（不夸大）：
 *   - DESTRUCTIVE_METHODS 是「当前已审核的锁定版本」deny set；Illuminate 未来升级新增
 *     mutation API 需经 contract 测试人工复核，不得假设「升级自动安全」。
 *   - 显式取得底层 Query Builder / DB facade / PDO raw SQL 属数据库直连层，应用层不封堵。
 */
class PowerLedgerEntryAppendOnlyBuilder extends Builder
{
    /**
     * power_ledger_entries 的 destructive mutation deny set（小写方法名）。
     * 与 AptLedgerEntryAppendOnlyBuilder::DESTRUCTIVE_METHODS 保持一致。
     */
    public const DESTRUCTIVE_METHODS = [
        'update',
        'upsert',
        'increment',
        'decrement',
        'touch',
        'delete',
        'forcedelete',
        'updateorinsert',
        'truncate',
        'incrementeach',
        'decrementeach',
        'updatefrom',
    ];

    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    /**
     * 兜底：阻断经 Eloquent Builder __call() 转发到底层 Query Builder 的 destructive mutation。
     *
     * @throws RunException
     */
    public function __call($method, $parameters)
    {
        if (in_array(strtolower($method), self::DESTRUCTIVE_METHODS, true)) {
            throw new RunException(
                "power_ledger_entries 为 append-only 账本：禁止 destructive mutation `{$method}`"
            );
        }

        return parent::__call($method, $parameters);
    }

    /** @throws RunException */
    public function update(array $values)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Eloquent Builder UPDATE');
    }

    /** @throws RunException */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Eloquent Builder upsert');
    }

    /** @throws RunException */
    public function increment($column, $amount = 1, array $extra = [])
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Eloquent Builder increment');
    }

    /** @throws RunException */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Eloquent Builder decrement');
    }

    /** @throws RunException */
    public function touch($column = null)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Eloquent Builder touch');
    }

    /** @throws RunException */
    public function delete()
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Eloquent Builder DELETE');
    }

    /** @throws RunException */
    public function forceDelete()
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Eloquent Builder forceDelete');
    }

    /** @throws RunException */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Query Builder updateOrInsert');
    }

    /** @throws RunException */
    public function updateFrom(array $values)
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Query Builder updateFrom');
    }

    /** @throws RunException */
    public function incrementEach(array $columns, array $extra = [])
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Query Builder incrementEach');
    }

    /** @throws RunException */
    public function decrementEach(array $columns, array $extra = [])
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Query Builder decrementEach');
    }

    /** @throws RunException */
    public function truncate()
    {
        throw new RunException('power_ledger_entries 为 append-only 账本：禁止 Query Builder truncate');
    }
}
