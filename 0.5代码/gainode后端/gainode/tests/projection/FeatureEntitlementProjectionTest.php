<?php

declare(strict_types=1);

/**
 * FeatureEntitlement 投影测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 资格规则依赖 06 Feature 参数（TBC）→ 默认 deny。
 * allowed_actions 字段在 05 §3 缺失（Contract Gap G2）→ 空数组，不自行推断。
 * 覆盖：
 *   1. 本人读取 → allowed=false，reason=FEATURE_RULE_UNAVAILABLE；
 *   2. allowed_actions=[]（Contract Gap G2，不推断）；
 *   3. 越权 → 安全 reason，不泄露存在性。
 */

require __DIR__ . '/_bootstrap.php';

use library\service\entitlement\FeatureEntitlementProjectionService;

echo "=====================================================\n";
echo "FeatureEntitlement projection test\n";
echo "=====================================================\n\n";

$svc = new FeatureEntitlementProjectionService();

echo "[1] 本人读取 → Feature 规则 TBC → 默认 deny\n";
$r = $svc->getEntitlement('90001', '90001', 'robot_upgrade');
check($r->feature_key === 'robot_upgrade', 'feature_key 正确');
check($r->allowed === false, 'allowed=false（默认 deny）');
check($r->reason_code === 'FEATURE_RULE_UNAVAILABLE', 'reason_code=FEATURE_RULE_UNAVAILABLE');
check($r->data_status === 'UNAVAILABLE', 'data_status=UNAVAILABLE');
check($r->source_status === 'UNAVAILABLE', 'source_status=UNAVAILABLE');
check($r->policy_version === null, 'policy_version=null');
check($r->rule_version === null, 'rule_version=null');
check($r->expires_at === null, 'expires_at=null');
echo "\n";

echo "[2] allowed_actions 字段（Contract Gap G2）→ 空数组，不推断\n";
check($r->allowed_actions === [], 'allowed_actions=[]（05 §3 缺失，不自行推断）');
echo "\n";

echo "[3] 越权 → 安全 reason\n";
$denied = $svc->getEntitlement('90002', '90001', 'robot_upgrade');
check($denied->data_status === 'UNAVAILABLE', '越权 data_status=UNAVAILABLE');
check($denied->feature_key === null, '越权 feature_key=null（不泄露）');
check($denied->refresh_hint === 'projection.access_denied', '越权返回安全 reason');
echo "\n";

summary();
