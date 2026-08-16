<?php

declare(strict_types=1);

/**
 * SessionDevice 投影测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 覆盖：
 *   1. REALTIME 路径：本人读取会话设备，os/browser/ip 正确派生，is_current 判定正确；
 *   2. 越权（跨用户）→ UNAVAILABLE，不泄露存在性（session_id=null）；
 *   3. 不存在会话 → UNAVAILABLE（与越权不可区分，不泄露存在性）；
 *   4. 无 mock：device_info 缺失字段（device_fingerprint/location_region）→ null；
 *   5. revocable 未冻结 → fail-closed 默认 false。
 */

require __DIR__ . '/_bootstrap.php';

use library\model\auth\AuthSessionModel;
use library\service\auth\SessionDeviceProjectionService;

echo "=====================================================\n";
echo "SessionDevice projection test\n";
echo "=====================================================\n\n";

// ---- 种子数据 ----
AuthSessionModel::query()->insert([
    'session_id' => '1001',
    'user_id' => '90001',
    'token_hash' => 'hash_1',
    'status' => AuthSessionModel::STATUS_ACTIVE,
    'device_info' => json_encode(['os' => 'iOS', 'browser' => 'Safari']),
    'ip_address' => '1.2.3.4',
    'mfa_verified' => 1,
    'expires_at' => 1800000000,
    'object_version' => 1,
    'audit_event_id' => 'AE0001',
    'created_time' => 1700000000,
    'updated_time' => 1700000100,
]);

$svc = new SessionDeviceProjectionService();

echo "[1] REALTIME 路径（本人读取）\n";
$r = $svc->getDevice('90001', '1001', '1001');
check($r->data_status === 'REALTIME', 'data_status=REALTIME');
check($r->source_status === 'READY', 'source_status=READY');
check($r->session_id === '1001', 'session_id 正确');
check($r->os === 'iOS', 'os 从 device_info 派生');
check($r->browser === 'Safari', 'browser 从 device_info 派生');
check($r->ip === '1.2.3.4', 'ip 正确');
check($r->is_current === true, 'is_current=true（当前会话）');
check($r->revocable === false, 'revocable=false（撤销规则未冻结，fail-closed）');
echo "\n";

echo "[2] 无 mock（device_info 缺失字段）\n";
check($r->device_fingerprint === null, 'device_fingerprint 缺失 → null（不 mock）');
check($r->location_region === null, 'location_region 缺失 → null（不 mock）');
echo "\n";

echo "[3] 越权（跨用户）→ 不泄露存在性\n";
$denied = $svc->getDevice('90002', '1001', '1001');
check($denied->data_status === 'UNAVAILABLE', '越权 data_status=UNAVAILABLE');
check($denied->source_status === 'UNAVAILABLE', '越权 source_status=UNAVAILABLE');
check($denied->session_id === null, '越权 session_id=null（不泄露存在性）');
check($denied->os === null, '越权 os=null');
check($denied->refresh_hint === 'projection.access_denied', '越权返回安全 reason');
echo "\n";

echo "[4] 不存在会话 → 与越权不可区分\n";
$missing = $svc->getDevice('90001', '9999', '9999');
check($missing->data_status === 'UNAVAILABLE', '不存在 data_status=UNAVAILABLE');
check($missing->session_id === null, '不存在 session_id=null（不泄露存在性）');
check($missing->source_status === 'UNAVAILABLE', '不存在 source_status=UNAVAILABLE');
echo "\n";

echo "[5] 非当前会话 is_current=false\n";
$other = $svc->getDevice('90001', '1001', '2002');
check($other->is_current === false, 'is_current=false（非当前会话）');
check($other->data_status === 'REALTIME', '非当前会话仍 REALTIME（本人可见）');
echo "\n";

summary();
