<?php

declare(strict_types=1);

/**
 * PowerImpactPreview 投影测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 覆盖：
 *   1. REALTIME + PARTIAL：available_before/frozen_before 来自 power_positions；
 *   2. Power 消耗/冻结/释放规则（AI.power_* TBC）→ allowed=false，字段 null（无 mock）；
 *   3. decimal string：available_before/frozen_before 保持字符串（禁 float）；
 *   4. 越权 → UNAVAILABLE；
 *   5. 无持仓 → UNAVAILABLE。
 */

require __DIR__ . '/_bootstrap.php';

use library\model\power\PowerPositionModel;
use library\service\power\PowerImpactPreviewProjectionService;

echo "=====================================================\n";
echo "PowerImpactPreview projection test\n";
echo "=====================================================\n\n";

// ---- 种子数据 ----
PowerPositionModel::query()->insert([
    'user_id' => '90001',
    'available' => '500.000000000000000000',
    'frozen' => '100.000000000000000000',
    'consumed_period' => '0',
    'released_period' => '0',
    'recovering' => '0',
    'limit' => '1000.000000000000000000',
    'power_cap_source_robot_level' => 5,
    'last_restore_at' => 1700000000,
    'next_restore_at' => 1700003600,
    'rule_version' => 'v1',
    'parameter_release_id' => 'PR001',
    'object_version' => 1,
    'created_time' => 1700000000,
    'updated_time' => 1700000100,
]);

$svc = new PowerImpactPreviewProjectionService();

echo "[1] REALTIME + PARTIAL（本人，ROBOT_START）\n";
$r = $svc->preview('90001', '90001', PowerImpactPreviewProjectionService::ACTION_ROBOT_START);
check($r->action_type === 'ROBOT_START', 'action_type 正确');
check($r->data_status === 'REALTIME', 'data_status=REALTIME（持仓可读）');
check($r->source_status === 'PARTIAL', 'source_status=PARTIAL（Power 规则 TBC）');
check($r->available_before === '500.000000000000000000', 'available_before 正确');
check($r->frozen_before === '100.000000000000000000', 'frozen_before 正确');
check($r->robot_level === '5', 'robot_level 正确');
check(is_string($r->available_before), 'available_before 为 decimal string（禁 float）');
echo "\n";

echo "[2] Power 规则 TBC → allowed=false，字段 null（无 mock）\n";
check($r->allowed === false, 'allowed=false（默认 deny）');
check($r->reason_code === 'POWER_RULE_UNAVAILABLE', 'reason_code=POWER_RULE_UNAVAILABLE');
check($r->required_power === null, 'required_power=null');
check($r->freeze_power === null, 'freeze_power=null');
check($r->consume_power === null, 'consume_power=null');
check($r->release_power === null, 'release_power=null');
check($r->power_cap === null, 'power_cap=null（AI.power_cap_by_robot_level TBC）');
check($r->available_after_preview === null, 'available_after_preview=null');
check($r->frozen_after_preview === null, 'frozen_after_preview=null');
echo "\n";

echo "[3] 越权 → UNAVAILABLE\n";
$denied = $svc->preview('90002', '90001', PowerImpactPreviewProjectionService::ACTION_OTC_SELL);
check($denied->data_status === 'UNAVAILABLE', '越权 data_status=UNAVAILABLE');
check($denied->available_before === null, '越权 available_before=null（不泄露）');
check($denied->refresh_hint === 'projection.access_denied', '越权返回安全 reason');
echo "\n";

echo "[4] 无持仓 → UNAVAILABLE\n";
$none = $svc->preview('99999', '99999', PowerImpactPreviewProjectionService::ACTION_WITHDRAWAL);
check($none->data_status === 'UNAVAILABLE', '无持仓 data_status=UNAVAILABLE');
check($none->source_status === 'UNAVAILABLE', '无持仓 source_status=UNAVAILABLE');
check($none->allowed === false, '无持仓 allowed=false');
echo "\n";

summary();
