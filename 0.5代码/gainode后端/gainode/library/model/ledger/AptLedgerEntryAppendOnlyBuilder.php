<?php

declare(strict_types=1);

namespace library\model\ledger;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use support\exception\RunException;

/**
 * apt_ledger_entries 专用 append-only Eloquent Builder。
 *
 * 阻断所有 Eloquent Builder 层的 destructive mutation，只保留 INSERT（追加）与只读查询。
 * 被阻断的 mutation（与 Illuminate/Database v10.38.1 实际签名一致）：
 *   update / upsert / increment / decrement / touch / delete / forceDelete
 *
 * Protection boundary（本类只覆盖 ORM 正常路径）：
 *   - Model::query() / Model::where() / Model::find() / newQueryWithoutScopes() 等返回的
 *     Eloquent Builder 均会走本类（由 AptLedgerEntryModel::newEloquentBuilder() 注入）。
 *   - 底层 Query Builder（toBase() / getQuery()）、通过 __call 下探的 Query Builder 方法
 *     （如 updateOrInsert / truncate）、以及 DB::table('apt_ledger_entries') 属于数据库直连层，
 *     应用层不在此封堵。若需数据库级硬约束，须另走 Change Request（DB Trigger / DB Role）。
 */
class AptLedgerEntryAppendOnlyBuilder extends Builder
{
    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    /**
     * append-only：禁止 Eloquent Builder UPDATE。
     *
     * @throws RunException
     */
    public function update(array $values)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Eloquent Builder UPDATE');
    }

    /**
     * append-only：禁止 Eloquent Builder upsert。
     *
     * @throws RunException
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Eloquent Builder upsert');
    }

    /**
     * append-only：禁止 Eloquent Builder increment。
     *
     * @throws RunException
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Eloquent Builder increment');
    }

    /**
     * append-only：禁止 Eloquent Builder decrement。
     *
     * @throws RunException
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Eloquent Builder decrement');
    }

    /**
     * append-only：禁止 Eloquent Builder touch（可能带显式列名触发 UPDATE）。
     *
     * @throws RunException
     */
    public function touch($column = null)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Eloquent Builder touch');
    }

    /**
     * append-only：禁止 Eloquent Builder DELETE。
     *
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Eloquent Builder DELETE');
    }

    /**
     * append-only：禁止 Eloquent Builder forceDelete。
     *
     * @throws RunException
     */
    public function forceDelete()
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Eloquent Builder forceDelete');
    }
}
