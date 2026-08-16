<?php

declare(strict_types=1);

/**
 * OtcEligibility 投影测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 覆盖：
 *   1. KYC approved → reason=MAINTENANCE（OTC 参数 TBC，无法判定资格，默认 deny）；
 *   2. KYC rejected → reason=KYC_REQUIRED（确定性 deny）；
 *   3. KYC review → reason=UNDER_REVIEW；
 *   4. 无 KYC 案件 → reason=KYC_REQUIRED；
 *   5. 越权 → UNAVAILABLE，不泄露存在性；
 *   6. reason_code 仅使用 05 §3 冻结七选一（不覆盖 OtcOrder.status）。
 */

require __DIR__ . '/_bootstrap.php';

use library\model\kyc\KycCaseModel;
use library\response\otc\OtcEligibilityResponse;
use library\service\otc\OtcEligibilityProjectionService;

echo "=====================================================\n";
echo "OtcEligibility projection test\n";
echo "=====================================================\n\n";

// ---- 种子数据：不同 KYC 状态 ----
$seed = [
    ['case_id' => 'K1', 'user_id' => '90001', 'kyc_level' => 'L1', 'status' => KycCaseModel::STATUS_APPROVED, 'submitted_at' => 1700000000, 'reviewed_at' => 1700000000, 'reviewed_by' => 'R1', 'reason_code' => '', 'reason_text_key' => '', 'next_action' => '', 'policy_version' => '', 'rule_version' => '', 'object_version' => 1, 'audit_event_id' => 'AE0001', 'created_time' => 1700000000, 'updated_time' => 1700000000],
    ['case_id' => 'K2', 'user_id' => '90002', 'kyc_level' => 'L1', 'status' => KycCaseModel::STATUS_REJECTED, 'submitted_at' => 1700000000, 'reviewed_at' => 1700000000, 'reviewed_by' => 'R1', 'reason_code' => '', 'reason_text_key' => '', 'next_action' => '', 'policy_version' => '', 'rule_version' => '', 'object_version' => 1, 'audit_event_id' => 'AE0002', 'created_time' => 1700000000, 'updated_time' => 1700000000],
    ['case_id' => 'K3', 'user_id' => '90003', 'kyc_level' => 'L1', 'status' => KycCaseModel::STATUS_REVIEW, 'submitted_at' => 1700000000, 'reviewed_at' => 0, 'reviewed_by' => '', 'reason_code' => '', 'reason_text_key' => '', 'next_action' => '', 'policy_version' => '', 'rule_version' => '', 'object_version' => 1, 'audit_event_id' => 'AE0003', 'created_time' => 1700000000, 'updated_time' => 1700000000],
];
foreach ($seed as $row) {
    KycCaseModel::query()->insert($row);
}

$svc = new OtcEligibilityProjectionService();

echo "[1] KYC approved → MAINTENANCE（OTC 参数 TBC，默认 deny）\n";
$r1 = $svc->getEligibility('90001', '90001');
check($r1->allowed === false, 'allowed=false');
check($r1->buy_allowed === false, 'buy_allowed=false');
check($r1->sell_allowed === false, 'sell_allowed=false');
check($r1->reason_code === OtcEligibilityResponse::REASON_MAINTENANCE, 'reason_code=MAINTENANCE');
check($r1->source_status === 'PARTIAL', 'source_status=PARTIAL（KYC 可读，OTC 参数 TBC）');
check($r1->data_status === 'REALTIME', 'data_status=REALTIME（KYC 状态实时）');
echo "\n";

echo "[2] KYC rejected → KYC_REQUIRED\n";
$r2 = $svc->getEligibility('90002', '90002');
check($r2->allowed === false, 'allowed=false');
check($r2->reason_code === OtcEligibilityResponse::REASON_KYC_REQUIRED, 'reason_code=KYC_REQUIRED');
echo "\n";

echo "[3] KYC review → UNDER_REVIEW\n";
$r3 = $svc->getEligibility('90003', '90003');
check($r3->reason_code === OtcEligibilityResponse::REASON_UNDER_REVIEW, 'reason_code=UNDER_REVIEW');
check($r3->allowed === false, 'allowed=false');
echo "\n";

echo "[4] 无 KYC 案件 → KYC_REQUIRED\n";
$r4 = $svc->getEligibility('90004', '90004');
check($r4->reason_code === OtcEligibilityResponse::REASON_KYC_REQUIRED, '无 KYC reason_code=KYC_REQUIRED');
check($r4->allowed === false, '无 KYC allowed=false');
check($r4->data_status === 'REALTIME', '无 KYC data_status=REALTIME（确定性拒绝）');
echo "\n";

echo "[5] 越权 → 不泄露存在性\n";
$denied = $svc->getEligibility('90002', '90001');
check($denied->data_status === 'UNAVAILABLE', '越权 data_status=UNAVAILABLE');
check($denied->reason_code === null, '越权 reason_code=null（不泄露）');
check($denied->refresh_hint === 'projection.access_denied', '越权返回安全 reason');
echo "\n";

echo "[6] reason_code 仅用 05 §3 冻结七选一\n";
$frozen = [
    OtcEligibilityResponse::REASON_KYC_REQUIRED,
    OtcEligibilityResponse::REASON_SECURITY_VERIFICATION_REQUIRED,
    OtcEligibilityResponse::REASON_OTC_CAPACITY_INSUFFICIENT,
    OtcEligibilityResponse::REASON_INSUFFICIENT_POWER,
    OtcEligibilityResponse::REASON_UNDER_REVIEW,
    OtcEligibilityResponse::REASON_REGION_UNAVAILABLE,
    OtcEligibilityResponse::REASON_MAINTENANCE,
];
check(in_array($r1->reason_code, $frozen, true), 'r1 reason_code 在冻结七选一内');
check(in_array($r2->reason_code, $frozen, true), 'r2 reason_code 在冻结七选一内');
check(in_array($r3->reason_code, $frozen, true), 'r3 reason_code 在冻结七选一内');
echo "\n";

summary();
