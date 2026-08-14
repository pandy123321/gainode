<?php

declare(strict_types=1);

namespace library\model\ledger;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use support\exception\RunException;

/**
 * apt_ledger_entries 专用 append-only Eloquent Builder。
 *
 * 阻断所有 destructive mutation，只保留 INSERT（追加）与只读查询。
 *
 * 单一事实来源：DESTRUCTIVE_METHODS 覆盖 Illuminate/Database v10.38.1 的两层 mutation 面：
 *   - Eloquent Builder 层（本类显式覆写）：update / upsert / increment / decrement / touch /
 *     delete / forceDelete
 *   - Query Builder 层（经 Eloquent Builder __call() 转发，本类显式覆写 + __call() 兜底）：
 *     updateOrInsert / truncate / incrementEach / decrementEach
 *
 * __call() 兜底：任何未显式定义但落入 DESTRUCTIVE_METHODS 的方法名，在转发到底层 Query
 * Builder 之前一律 fail-closed，避免 Illuminate 升级新增 mutation API 后静默绕过。
 *
 * Protection boundary（本类只覆盖 ORM 正常路径）：
 *   - Model::query() / Model::where() / Model::find() / newQuery() / DAO / Model 实例
 *     等常规 ORM 入口均会走本类（由 AptLedgerEntryModel::newEloquentBuilder() 注入）。
 *   - 显式取得底层 Query Builder / DB facade（toBase() / getQuery() / DB::table(...) /
 *     PDO raw SQL）属于数据库直连层，应用层不在此封堵；需数据库级硬约束时另走
 *     Change Request（DB Trigger / DB Role），不改 MC1 Frozen DDL。
 */
class AptLedgerEntryAppendOnlyBuilder extends Builder
{
    /**
     * apt_ledger_entries 的 destructive mutation 全量 deny set（小写方法名）。
     * 供 __call() 兜底拦截与回归测试共用，是「不可变账本禁写」的机械单一事实来源。
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
    ];

    public function __construct(QueryBuilder $query)
    {
        parent::__construct($query);
    }

    /**
     * 兜底：阻断经 Eloquent Builder __call() 转发到底层 Query Builder 的 destructive mutation。
     * 未落入 deny set 的方法（含 select/where/find/get/first/insert 等）正常转发给父类。
     *
     * @param string $method
     * @param array  $parameters
     * @return mixed
     *
     * @throws RunException
     */
    public function __call($method, $parameters)
    {
        if (in_array(strtolower($method), self::DESTRUCTIVE_METHODS, true)) {
            throw new RunException(
                "apt_ledger_entries 为 append-only 账本：禁止 destructive mutation `{$method}`"
            );
        }

        return parent::__call($method, $parameters);
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

    /**
     * append-only：禁止 Query Builder updateOrInsert（可能 UPDATE 已存在分录）。
     *
     * @throws RunException
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Query Builder updateOrInsert');
    }

    /**
     * append-only：禁止 Query Builder incrementEach。
     *
     * @throws RunException
     */
    public function incrementEach(array $columns, array $extra = [])
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Query Builder incrementEach');
    }

    /**
     * append-only：禁止 Query Builder decrementEach。
     *
     * @throws RunException
     */
    public function decrementEach(array $columns, array $extra = [])
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Query Builder decrementEach');
    }

    /**
     * append-only：禁止 Query Builder truncate（会清空整表）。
     *
     * @throws RunException
     */
    public function truncate()
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Query Builder truncate');
    }
}
