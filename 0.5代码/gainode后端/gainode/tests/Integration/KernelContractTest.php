<?php

declare(strict_types=1);

/**
 * 通用内核契约测试（S02-P01，Integration）。
 *
 * 覆盖：
 *   1. NullIdempotencyStore：isAvailable()=false（fail-closed），find/reserve/complete 无副作用；
 *   2. NullOutboxStore：isAvailable()=false，append 无副作用；
 *   3. 未冻结存储的 fail-closed 语义：调用方依赖 isAvailable() 判定拒绝写。
 */

require __DIR__ . '/_bootstrap.php';

use library\service\idempotency\IdempotencyStore;
use library\service\idempotency\NullIdempotencyStore;
use library\service\outbox\NullOutboxStore;
use library\service\outbox\OutboxStore;

echo "=====================================================\n";
echo "Kernel contract test\n";
echo "=====================================================\n\n";

// ---- IdempotencyStore 契约 ----
echo "[1] NullIdempotencyStore fail-closed\n";
$idem = new NullIdempotencyStore();
check($idem instanceof IdempotencyStore, '实现 IdempotencyStore 接口');
check($idem->isAvailable() === false, 'isAvailable()=false（未冻结存储）');
check($idem->find('IK1', 'prediction_orders') === null, 'find 恒返回 null');
// reserve/complete 无副作用（不抛异常）
$idem->reserve('IK1', 'prediction_orders', '2001', 'REQ1');
$idem->complete('IK1', 'prediction_orders', '2001', ['status' => 'completed']);
check(true, 'reserve/complete 无副作用不抛异常');
echo "\n";

// ---- fail-closed 判定契约 ----
echo "[2] 依赖 isAvailable() 的 fail-closed 判定\n";
$canWrite = $idem->isAvailable();
check($canWrite === false, '未冻结存储 → 依赖幂等保证的写必须拒绝');
echo "\n";

// ---- OutboxStore 契约 ----
echo "[3] NullOutboxStore fail-closed\n";
$outbox = new NullOutboxStore();
check($outbox instanceof OutboxStore, '实现 OutboxStore 接口');
check($outbox->isAvailable() === false, 'isAvailable()=false（未冻结存储）');
$outbox->append('DEDUP1', 'prediction_order_created', ['order_id' => '2001'], 'REQ1');
check(true, 'append 无副作用不抛异常');
echo "\n";

summary();
