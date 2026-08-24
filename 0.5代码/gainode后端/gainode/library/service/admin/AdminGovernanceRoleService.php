<?php

declare(strict_types=1);

namespace library\service\admin;

use library\model\sys\AdminModel;
use library\service\sys\AdminService;
use support\extend\Service;

/**
 * Admin V2 治理角色解析服务（A-GOV-ROLE-001）。
 *
 * 将当前后台管理员（sys_admin）映射为 05 §8 canonical 治理角色集合。
 * 设计（对齐文档，不猜测）：
 *   - 超管（sys_admin.is_admin=1）持有全部治理角色 → 可执行所有写路径；
 *   - 非超管按 role_id → 治理角色 映射表（本类 ROLE_MAP，可配置）解析；
 *   - 未匹配 / 未配置 → 空集合（fail-closed，无写权限）。
 *
 * 供 AdminV2Controller 写路径解析 $actorId + $actorRole 后调用领域 service
 * （内部 guardRole 二次校验，双重保险）。
 */
class AdminGovernanceRoleService extends Service
{
    /**
     * 05 §8/§11.3 canonical 治理角色全集（冻结枚举，13 个）。
     * NEXT-01 步骤2：补齐 LEDGER_OPERATOR、AUDITOR（此前缺失，仅 11 个）。
     */
    public const ROLES = [
        'END_USER', 'OPS_OPERATOR', 'ADMIN_SECURITY',
        'PARAM_EDITOR', 'PARAM_APPROVER', 'RELEASE_OPERATOR',
        'RISK_ANALYST', 'RISK_APPROVER', 'LEDGER_OPERATOR',
        'FINANCE_REVIEWER', 'KYC_REVIEWER', 'SUPPORT_AGENT',
        'AUDITOR',
    ];

    /**
     * sys_admin.role_id → 治理角色 映射（非超管）。
     * 注：该映射为配置项，默认超管承担全部治理角色；普通 role_id 在此登记。
     * 若某 role_id 未登记，则该管理员无治理角色（fail-closed）。
     *
     * @var array<int, string[]>
     */
    protected const ROLE_MAP = [];

    /**
     * 解析当前管理员持有的治理角色集合。
     *
     * @param AdminModel $admin
     * @return string[]
     */
    public function rolesFor(AdminModel $admin): array
    {
        if ((int) $admin->is_admin === 1) {
            return self::ROLES; // 超管 = 全部治理角色
        }
        $roleId = (int) $admin->role_id;
        return self::ROLE_MAP[$roleId] ?? []; // 未配置 → 空（fail-closed）
    }

    /**
     * 解析当前登录管理员的治理角色集合（基于缓存 admin 数据）。
     *
     * @param int $adminId
     * @return string[]
     */
    public function rolesForAdminId(int $adminId): array
    {
        $adminData = (new AdminService())->getUserById($adminId);
        if (empty($adminData) || !isset($adminData['id'])) {
            return [];
        }
        $admin = new AdminModel();
        foreach ($adminData as $k => $v) {
            if (in_array($k, $admin->fields, true)) {
                $admin->{$k} = $v;
            }
        }
        return $this->rolesFor($admin);
    }

    /**
     * 判断管理员是否持有指定任一治理角色。
     *
     * @param int $adminId
     * @param string[] $requiredRoles
     * @return bool
     */
    public function hasAnyRole(int $adminId, array $requiredRoles): bool
    {
        return count(array_intersect($this->rolesForAdminId($adminId), $requiredRoles)) > 0;
    }
}
