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
 * 单一事实来源：DESTRUCTIVE_METHODS 覆盖「当前锁定 Illuminate/Database v10.38.1 已审核的
 * ORM destructive mutation」，分为两层：
 *   - Eloquent Builder 层（本类显式覆写）：update / upsert / increment / decrement / touch /
 *     delete / forceDelete
 *   - Query Builder 层（经 Eloquent Builder __call() 转发，本类显式覆写 + __call() 兜底）：
 *     updateOrInsert / truncate / incrementEach / decrementEach / updateFrom
 *
 * __call() 兜底：任何未显式定义但落入 DESTRUCTIVE_METHODS 的方法名，在转发到底层 Query
 * Builder 之前一律 fail-closed。
 *
 * 安全声明边界（不夸大）：
 *   - DESTRUCTIVE_METHODS 是「当前已审核的锁定版本」deny set。静态清单本身无法自动识别
 *     Illuminate 未来升级新增的 mutation API；依赖升级必须经
 *     tests/ledger/LedgerAppendOnlyMutationMatrixTest.php 的 dependency mutation-surface
 *     contract 检查（出现未 disposition 的新 write method 即 FAIL，要求人工复核），
 *     不得假设「升级自动安全」。
 *   - updateFrom 属 PostgreSQL 的 UPDATE ... FROM ...，当前 Gainode 冻结库为 MySQL，
 *     不可形成实际 MySQL 修改路径；为语义完整仍纳入 deny。
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
     * apt_ledger_entries 的 destructive mutation deny set（小写方法名）。
     *
     * 覆盖范围 = 当前锁定 Illuminate/Database v10.38.1 已审核的 ORM destructive mutation，
     * 供 __call() 兜底拦截与 dependency mutation-surface contract 回归测试共用。
     *
     * 注意：这是「当前已审核」清单，不是「全量 / 未来升级自动安全」承诺。Illuminate 升级
     * 新增 mutation API 时，必须由 tests/ledger 的 contract 测试检测并人工 disposition。
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
     * append-only：禁止 Query Builder updateFrom（PostgreSQL UPDATE ... FROM ...；
     * 当前 Gainode 冻结库为 MySQL 不可执行，为语义完整仍 deny）。
     *
     * @throws RunException
     */
    public function updateFrom(array $values)
    {
        throw new RunException('apt_ledger_entries 为 append-only 账本：禁止 Query Builder updateFrom');
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
