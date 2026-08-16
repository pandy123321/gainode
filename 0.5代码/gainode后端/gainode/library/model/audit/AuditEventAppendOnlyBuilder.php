<?php

declare(strict_types=1);

namespace library\model\audit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use support\exception\RunException;

/**
 * audit_events 专用 append-only Eloquent Builder。
 *
 * 阻断所有 destructive mutation，只保留 INSERT（追加）与只读查询。
 * audit_events 为 append-only 审计事件表（MC2 Freeze §6，无 UPDATE/DELETE）。
 *
 * 单一事实来源：DESTRUCTIVE_METHODS 覆盖「当前锁定 Illuminate/Database v10.38.1 已审核的
 * ORM destructive mutation」，分两层（复用 AptLedgerEntryAppendOnlyBuilder 的已验证 deny set，
 * 仅表名与错误信息改为 audit_events）：
 *   - Eloquent Builder 层：update / upsert / increment / decrement / touch / delete / forceDelete
 *   - Query Builder 层（经 Eloquent Builder __call() 转发）：updateOrInsert / truncate /
 *     incrementEach / decrementEach / updateFrom
 *
 * __call() 兜底：任何未显式定义但落入 DESTRUCTIVE_METHODS 的方法名，在转发到底层 Query
 * Builder 之前一律 fail-closed。
 *
 * 安全声明边界：DESTRUCTIVE_METHODS 是「当前已审核的锁定版本」deny set，静态清单本身无法
 * 自动识别 Illuminate 未来升级新增的 mutation API；依赖升级须经 contract 测试人工复核。
 *
 * Protection boundary：本类只覆盖 ORM 正常路径。显式取得底层 Query Builder / DB facade /
 * PDO raw SQL 属数据库直连层，应用层不在此封堵；需数据库级硬约束时另走 Change Request。
 */
class AuditEventAppendOnlyBuilder extends Builder
{
    /**
     * audit_events 的 destructive mutation deny set（小写方法名）。
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
                "audit_events 为 append-only 审计事件：禁止 destructive mutation `{$method}`"
            );
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @throws RunException
     */
    public function update(array $values)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Eloquent Builder UPDATE');
    }

    /**
     * @throws RunException
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Eloquent Builder upsert');
    }

    /**
     * @throws RunException
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Eloquent Builder increment');
    }

    /**
     * @throws RunException
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Eloquent Builder decrement');
    }

    /**
     * @throws RunException
     */
    public function touch($column = null)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Eloquent Builder touch');
    }

    /**
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Eloquent Builder DELETE');
    }

    /**
     * @throws RunException
     */
    public function forceDelete()
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Eloquent Builder forceDelete');
    }

    /**
     * @throws RunException
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Query Builder updateOrInsert');
    }

    /**
     * @throws RunException
     */
    public function updateFrom(array $values)
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Query Builder updateFrom');
    }

    /**
     * @throws RunException
     */
    public function incrementEach(array $columns, array $extra = [])
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Query Builder incrementEach');
    }

    /**
     * @throws RunException
     */
    public function decrementEach(array $columns, array $extra = [])
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Query Builder decrementEach');
    }

    /**
     * @throws RunException
     */
    public function truncate()
    {
        throw new RunException('audit_events 为 append-only 审计事件：禁止 Query Builder truncate');
    }
}
