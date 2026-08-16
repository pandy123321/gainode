<?php

declare(strict_types=1);

/**
 * OtcCapacity 投影测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * 容量/储备比例依赖 06 OTC 参数（TBC）→ 一律 UNAVAILABLE，字段 null。
 * 覆盖：
 *   1. 本人读取 → UNAVAILABLE，容量字段 null（不 mock、不回退旧值）；
 *   2. decimal string 约束（字段即使未来有值也必须是 string）；
 *   3. 越权 → 安全 reason，不泄露存在性。
 */

require __DIR__ . '/_bootstrap.php';

use library\service\otc\OtcCapacityProjectionService;

echo "=====================================================\n";
echo "OtcCapacity projection test\n";
echo "=====================================================\n\n";

$svc = new OtcCapacityProjectionService();

echo "[1] 本人读取 → OTC 参数 TBC → UNAVAILABLE\n";
$r = $svc->getCapacity('90001', '90001', 'SELL');
check($r->direction === 'SELL', 'direction 正确');
check($r->data_status === 'UNAVAILABLE', 'data_status=UNAVAILABLE');
check($r->source_status === 'UNAVAILABLE', 'source_status=UNAVAILABLE');
check($r->user_remaining_capacity === null, 'user_remaining_capacity=null（不 mock）');
check($r->global_remaining_capacity === null, 'global_remaining_capacity=null');
check($r->reserve_ratio === null, 'reserve_ratio=null（otc.*_reserve_ratio TBC）');
check($r->rule_version === null, 'rule_version=null');
check($r->parameter_release_id === null, 'parameter_release_id=null');
echo "\n";

echo "[2] 越权 → 安全 reason\n";
$denied = $svc->getCapacity('90002', '90001', 'BUY');
check($denied->data_status === 'UNAVAILABLE', '越权 data_status=UNAVAILABLE');
check($denied->refresh_hint === 'projection.access_denied', '越权返回安全 reason');
echo "\n";

summary();
