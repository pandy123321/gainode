<?php

declare(strict_types=1);

namespace library\service\transaction;

use support\extend\Db;
use Throwable;

/**
 * 统一事务边界（07 §S02-P01 步骤 5 / S02-P03 事务模板）。
 *
 * 封装 DB 事务执行，供各 Authoritative Writer 复用，保证「业务写入 + AuditEvent +
 * Outbox」在同一事务内原子提交。
 *
 * Economic Mutation Lock 语义（MC2 Freeze，账户级统一锁）：所有改 balance_apt_* /
 * frozen_apt_* / aggregate_dispute_hold 的操作，须先在事务内以
 * `apt_accounts.object_version` CAS 锁定；affected rows≠1 统一抛 OBJECT_VERSION_CONFLICT(409)。
 * 本类提供 `lockForUpdate()` 供调用方对目标行加锁，具体 CAS 判定由调用方执行。
 */
final class TransactionBoundary
{
    /**
     * 在事务内执行回调，自动提交/回滚。
     *
     * @param callable $callback   事务体，接收 Connection 实例
     * @param string   $connection 连接名（默认 mysql）
     * @return mixed 回调返回值
     * @throws Throwable
     */
    public function run(callable $callback, string $connection = 'mysql')
    {
        return Db::connection($connection)->transaction(function ($conn) use ($callback) {
            return $callback($conn);
        });
    }

    /**
     * 对目标表指定主键行加 SELECT ... FOR UPDATE 锁（悲观锁，与 object_version CAS 互补）。
     *
     * @param string $table
     * @param string $primaryKey
     * @param string $id
     * @param string $connection
     * @return void
     */
    public function lockForUpdate(string $table, string $primaryKey, string $id, string $connection = 'mysql'): void
    {
        Db::connection($connection)
            ->table($table)
            ->where($primaryKey, $id)
            ->lockForUpdate()
            ->first();
    }
}
