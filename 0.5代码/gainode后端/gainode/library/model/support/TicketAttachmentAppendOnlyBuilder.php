<?php

declare(strict_types=1);

namespace library\model\support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use support\exception\RunException;

/**
 * ticket_attachments 专用 append-only Eloquent Builder。
 *
 * 阻断所有 destructive mutation，只保留 INSERT（追加）与只读查询。
 * ticket_attachments 为 append-only 值对象（05 §3 TicketAttachment），
 * 附件一经写入永不覆盖，修订以新附件追加表达。
 *
 * 单一事实来源：DESTRUCTIVE_METHODS 覆盖「当前锁定 Illuminate/Database v10.38.1 已审核的
 * ORM destructive mutation」，分两层（与本模块 OtcTradeAppendOnlyBuilder 同 denyset）。
 *
 * __call() 兜底：任何未显式定义但落入 DESTRUCTIVE_METHODS 的方法名，在转发到底层 Query
 * Builder 之前一律 fail-closed。
 *
 * Protection boundary：本类只覆盖 ORM 正常路径；数据库直连层需 DB 级硬约束另走 Change Request。
 */
class TicketAttachmentAppendOnlyBuilder extends Builder
{
    /**
     * ticket_attachments 的 destructive mutation deny set（小写方法名）。
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
                "ticket_attachments 为 append-only 值对象：禁止 destructive mutation `{$method}`"
            );
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @throws RunException
     */
    public function update(array $values)
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Eloquent Builder UPDATE');
    }

    /**
     * @throws RunException
     */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Eloquent Builder upsert');
    }

    /**
     * @throws RunException
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Eloquent Builder increment');
    }

    /**
     * @throws RunException
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Eloquent Builder decrement');
    }

    /**
     * @throws RunException
     */
    public function touch($column = null)
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Eloquent Builder touch');
    }

    /**
     * @throws RunException
     */
    public function delete()
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Eloquent Builder DELETE');
    }

    /**
     * @throws RunException
     */
    public function forceDelete()
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Eloquent Builder forceDelete');
    }

    /**
     * @throws RunException
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Query Builder updateOrInsert');
    }

    /**
     * @throws RunException
     */
    public function updateFrom(array $values)
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Query Builder updateFrom');
    }

    /**
     * @throws RunException
     */
    public function incrementEach(array $columns, array $extra = [])
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Query Builder incrementEach');
    }

    /**
     * @throws RunException
     */
    public function decrementEach(array $columns, array $extra = [])
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Query Builder decrementEach');
    }

    /**
     * @throws RunException
     */
    public function truncate()
    {
        throw new RunException('ticket_attachments 为 append-only 值对象：禁止 Query Builder truncate');
    }
}
