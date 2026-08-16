<?php

declare(strict_types=1);

/**
 * SecurityProfile 投影测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 覆盖：
 *   1. REALTIME + PARTIAL：mfa_enrolled_methods 来自 mfa_enrollments（仅 active）；
 *   2. 安全策略 TBC 字段 → null（login_history_window/suspicious_flags/policy_version）；
 *   3. 越权（跨用户）→ UNAVAILABLE，不泄露存在性；
 *   4. 无 mock：不虚构登录历史/可疑标记。
 */

require __DIR__ . '/_bootstrap.php';

use library\model\auth\MfaEnrollmentModel;
use library\service\auth\SecurityProfileProjectionService;

echo "=====================================================\n";
echo "SecurityProfile projection test\n";
echo "=====================================================\n\n";

// ---- 种子数据：一条 active，一条 revoked，一条 pending ----
$seed = [
    ['enrollment_id' => 'E1', 'user_id' => '90001', 'method_type' => 'totp', 'status' => MfaEnrollmentModel::STATUS_ACTIVE, 'enrolled_at' => 1700000000, 'last_verified_at' => 1700000000, 'backup_codes_active' => 0, 'object_version' => 1, 'audit_event_id' => 'AE0001', 'created_time' => 1700000000, 'updated_time' => 1700000000],
    ['enrollment_id' => 'E2', 'user_id' => '90001', 'method_type' => 'sms', 'status' => MfaEnrollmentModel::STATUS_REVOKED, 'enrolled_at' => 1700000000, 'last_verified_at' => 1700000000, 'backup_codes_active' => 0, 'object_version' => 1, 'audit_event_id' => 'AE0002', 'created_time' => 1700000000, 'updated_time' => 1700000000],
    ['enrollment_id' => 'E3', 'user_id' => '90001', 'method_type' => 'backup', 'status' => MfaEnrollmentModel::STATUS_PENDING, 'enrolled_at' => 1700000000, 'last_verified_at' => 0, 'backup_codes_active' => 0, 'object_version' => 1, 'audit_event_id' => 'AE0003', 'created_time' => 1700000000, 'updated_time' => 1700000000],
];
foreach ($seed as $row) {
    MfaEnrollmentModel::query()->insert($row);
}

$svc = new SecurityProfileProjectionService();

echo "[1] REALTIME + PARTIAL（本人读取）\n";
$r = $svc->getProfile('90001', '90001');
check($r->user_id === '90001', 'user_id 正确');
check($r->data_status === 'REALTIME', 'data_status=REALTIME（mfa 可读）');
check($r->source_status === 'PARTIAL', 'source_status=PARTIAL（安全策略 TBC）');
check($r->mfa_enrolled_methods === ['totp'], 'mfa_enrolled_methods 仅含 active（totp）');
echo "\n";

echo "[2] 安全策略 TBC 字段 → null（无 mock）\n";
check($r->login_history_window === null, 'login_history_window=null');
check($r->suspicious_flags === null, 'suspicious_flags=null');
check($r->policy_version === null, 'policy_version=null');
check($r->last_password_change === null, 'last_password_change=null');
check($r->mfa_required_actions === [], 'mfa_required_actions=[]（TBC，不虚构）');
echo "\n";

echo "[3] 越权（跨用户）→ 不泄露存在性\n";
$denied = $svc->getProfile('90002', '90001');
check($denied->data_status === 'UNAVAILABLE', '越权 data_status=UNAVAILABLE');
check($denied->user_id === null, '越权 user_id=null（不泄露存在性）');
check($denied->mfa_enrolled_methods === [], '越权 mfa_enrolled_methods=[]');
check($denied->refresh_hint === 'projection.access_denied', '越权返回安全 reason');
echo "\n";

summary();
