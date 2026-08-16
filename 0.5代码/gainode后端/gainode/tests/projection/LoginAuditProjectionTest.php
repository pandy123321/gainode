<?php

declare(strict_types=1);

/**
 * LoginAudit 投影测试（独立 CLI 脚本，无需 PHPUnit）。
 *
 * source-of-truth 未在 05 明确（Contract Gap G1）→ 一律 UNAVAILABLE，不读取任何表。
 * 覆盖：
 *   1. 本人读取 → UNAVAILABLE（source 未裁决，不 mock、不读取未裁决 schema）；
 *   2. 越权（跨用户）→ UNAVAILABLE，与本人读取在存在性上不可区分。
 */

require __DIR__ . '/_bootstrap.php';

use library\service\auth\LoginAuditProjectionService;

echo "=====================================================\n";
echo "LoginAudit projection test\n";
echo "=====================================================\n\n";

$svc = new LoginAuditProjectionService();

echo "[1] 本人读取 → source 未裁决 → UNAVAILABLE\n";
$r = $svc->getAudit('90001', '90001');
check($r->data_status === 'UNAVAILABLE', 'data_status=UNAVAILABLE');
check($r->source_status === 'UNAVAILABLE', 'source_status=UNAVAILABLE');
check($r->audit_id === null, 'audit_id=null（不 mock）');
check($r->event_type === null, 'event_type=null');
check($r->ip_address === null, 'ip_address=null（不泄露）');
check($r->refresh_hint === 'projection.source_unavailable', 'refresh_hint=source_unavailable');
echo "\n";

echo "[2] 越权 → 安全 reason，不泄露存在性\n";
$denied = $svc->getAudit('90002', '90001');
check($denied->data_status === 'UNAVAILABLE', '越权 data_status=UNAVAILABLE');
check($denied->user_id === null, '越权 user_id=null');
check($denied->refresh_hint === 'projection.access_denied', '越权 refresh_hint=access_denied');
echo "\n";

summary();
