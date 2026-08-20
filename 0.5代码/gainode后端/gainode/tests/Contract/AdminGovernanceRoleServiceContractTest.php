<?php

declare(strict_types=1);

// CLI 契约测试：AdminGovernanceRoleService 治理角色解析（A-GOV-ROLE-001）
// 用法：php tests/Contract/AdminGovernanceRoleServiceContractTest.php
require __DIR__ . '/_bootstrap.php';

use library\model\sys\AdminModel;
use library\service\admin\AdminGovernanceRoleService;

check(class_exists(\library\service\admin\AdminGovernanceRoleService::class), 'AdminGovernanceRoleService exists');

$svc = new AdminGovernanceRoleService();

// 超管（is_admin=1）→ 全部治理角色
$su = new AdminModel();
$su->id = 1;
$su->is_admin = 1;
$su->role_id = 0;
$suRoles = $svc->rolesFor($su);
check(is_array($suRoles) && count($suRoles) > 0, 'super admin gets all governance roles (count>0)');
check(in_array('RISK_APPROVER', $suRoles, true), 'super admin holds RISK_APPROVER');
check(in_array('SUPPORT_AGENT', $suRoles, true), 'super admin holds SUPPORT_AGENT');
check(in_array('KYC_REVIEWER', $suRoles, true), 'super admin holds KYC_REVIEWER');

// 非超管且 role_id 未配置 → 空集合（fail-closed）
$normal = new AdminModel();
$normal->id = 2;
$normal->is_admin = 0;
$normal->role_id = 999; // 未配置
check($svc->rolesFor($normal) === [], 'unconfigured normal admin => empty roles (fail-closed)');

// hasAnyRole / rolesForAdminId 需 Redis/DB（getUserById 走 Cache），CLI 无 Redis → 不在此运行
check(is_callable([$svc, 'rolesForAdminId']), 'rolesForAdminId callable (runtime w/ Redis)');
check(is_callable([$svc, 'hasAnyRole']), 'hasAnyRole callable (runtime w/ Redis)');

summary('AdminGovernanceRoleService');
