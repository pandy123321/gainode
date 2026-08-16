<?php

declare(strict_types=1);

/**
 * Envelope + ErrorDict 契约测试（S02-P01）。
 *
 * 覆盖：
 *   1. 成功 envelope 结构（request_id/data/data_status/source_status + 数据新鲜度 8 字段）；
 *   2. 写操作 extra 字段不能被覆盖（$base + $extra 左优先）；
 *   3. 错误 envelope 结构；
 *   4. ErrorDict 05 §7 16 错误码 → HTTP 状态映射逐项正确；
 *   5. 未知错误码兜底 500。
 */

require __DIR__ . '/_bootstrap.php';

use library\dict\ErrorDict;
use library\response\Envelope;

echo "=====================================================\n";
echo "Envelope + ErrorDict contract test\n";
echo "=====================================================\n\n";

// ---- 成功 envelope ----
echo "[1] 成功 envelope 结构\n";
$s = Envelope::success(['id' => '1001'], ['snapshot_id' => 'SNAP1'], [], 'REQ123');
check($s['request_id'] === 'REQ123', 'request_id 透传');
check($s['data']['id'] === '1001', 'data 正确');
check($s['data_status'] === 'REALTIME', 'data_status 默认 REALTIME');
check($s['source_status'] === 'OK', 'source_status 默认 OK');
check($s['snapshot_id'] === 'SNAP1', 'snapshot_id 来自 meta');
check(array_key_exists('as_of', $s), 'as_of 存在');
check(array_key_exists('next_refresh_at', $s), 'next_refresh_at 存在');
check(array_key_exists('stale_after', $s), 'stale_after 存在');
echo "\n";

// ---- 写操作 extra 字段 ----
echo "[2] 写操作 extra 字段（不可覆盖固定字段）\n";
$w = Envelope::success(['status' => 'processing'], [], [
    'idempotency_key' => 'IK1',
    'object_type'     => 'prediction_orders',
    'object_id'       => '2001',
    'status'          => 'pending',
    'result_code'     => 'RESULT_UNKNOWN',
], 'REQ456');
check($w['idempotency_key'] === 'IK1', 'idempotency_key 追加');
check($w['object_type'] === 'prediction_orders', 'object_type 追加');
check($w['request_id'] === 'REQ456', 'request_id 不可被覆盖');
check($w['data']['status'] === 'processing', 'data 不可被覆盖');
echo "\n";

// ---- 错误 envelope ----
echo "[3] 错误 envelope 结构\n";
$e = Envelope::error(ErrorDict::OBJECT_VERSION_CONFLICT, '版本冲突', 409, [], 'REQ789');
check($e['result_code'] === 'OBJECT_VERSION_CONFLICT', 'result_code 正确');
check($e['http_status'] === 409, 'http_status 正确');
check($e['request_id'] === 'REQ789', 'request_id 正确');
check(array_key_exists('details', $e), 'details 存在');
echo "\n";

// ---- ErrorDict HTTP 状态映射 ----
echo "[4] ErrorDict 05 §7 映射\n";
$expected = [
    'VALIDATION_ERROR'         => 400,
    'AUTH_UNAUTHENTICATED'     => 401,
    'AUTH_FORBIDDEN'           => 403,
    'KYC_REQUIRED'             => 403,
    'POLICY_DENIED'            => 403,
    'FEATURE_CLOSED'           => 403,
    'CONSENT_VERSION_MISMATCH' => 409,
    'IDEMPOTENCY_CONFLICT'     => 409,
    'OBJECT_VERSION_CONFLICT'  => 409,
    'QUOTE_EXPIRED'            => 409,
    'INSUFFICIENT_APT'         => 422,
    'INSUFFICIENT_POWER'       => 422,
    'MARKET_LOCKED'            => 422,
    'DEPENDENCY_UNAVAILABLE'   => 503,
    'RESULT_UNKNOWN'           => 202,
    'INTERNAL_ERROR'           => 500,
];
foreach ($expected as $code => $status) {
    $actual = ErrorDict::httpStatus($code);
    check($actual === $status, "{$code} → {$status}（实际 {$actual}）");
}
check(ErrorDict::httpStatus('UNKNOWN_CODE') === 500, '未知错误码兜底 500');
echo "\n";

summary();
