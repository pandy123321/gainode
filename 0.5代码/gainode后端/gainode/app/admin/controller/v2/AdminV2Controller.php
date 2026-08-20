<?php

declare(strict_types=1);

namespace app\admin\controller\v2;

use library\dict\ErrorDict;
use library\service\admin\AdminOtcDtoService;
use library\service\admin\AdminRobotDtoService;
use library\service\admin\AdminUserDtoService;
use library\service\audit\AuditEventService;
use library\service\sys\AdminService;
use support\controller\ApiV2;
use support\exception\DomainException;
use support\Response;

/**
 * Admin V2 控制器（admin 应用，AdminAuth 认证；OPTION_A 落地）。
 *
 * 路径契约：/api/v1/admin/...（由 admin 应用 /api/v1/admin 组注册）。
 * $request->app='admin'（控制器位于 app/admin/controller/v2）→ getTokenUser() 走 AdminAuth。
 *
 * 只读：
 *   GET /api/v1/admin/audit-log → 审计日志查询（AuditEventService::listAdmin，脱敏白名单）
 *   GET /api/v1/admin/async-jobs/{id} → 异步任务详情（AsyncJob 无服务，fail-closed）
 *
 * 写操作（市场/结算/退款/更正/案件/审批/导出）：
 *   依赖 admin 角色(05 13 角色)与 sys_admin.role_id 的映射——该映射为另一决策点，
 *   本阶段写操作一律 fail-closed（DEPENDENCY_UNAVAILABLE/POLICY_DENIED），不绑定真实服务，
 *   避免在角色映射未冻结时开放有经济副作用的写路径。
 */
class AdminV2Controller extends ApiV2
{
    /** GET /api/v1/admin/audit-log */
    public function auditLog(): Response
    {
        try {
            $this->request->getTokenUser(); // admin 应用 → AdminAuth
            $filters = [];
            foreach (['actor_id', 'event_code', 'target_object_type', 'target_object_id', 'outcome', 'request_id'] as $k) {
                $v = $this->request->get($k);
                if ($v !== null && $v !== '') {
                    $filters[$k] = (string) $v;
                }
            }
            $result = (new AuditEventService())->listAdmin($filters);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/admission/users — 用户列表 DTO（A-USER-001） */
    public function users(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $keyword = (string) $this->request->get('keyword', '');
            $result = (new AdminUserDtoService())->list($page, $size, $keyword);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/otc/orders — OTC 订单列表 DTO（A-OTC-001） */
    public function otcOrders(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminOtcDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/robot/list — Robot 列表 DTO（A-ROBOT-001） */
    public function robots(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminRobotDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/async-jobs/{id} — AsyncJob 服务不存在 → fail-closed */
    public function asyncJob(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'async job service not available');
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/admin/export-tasks — fail-closed（export 服务不存在） */
    public function exportTask(): Response
    {
        try {
            $this->request->getTokenUser();
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'export task service not available');
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** 内部：解析 admin actor 基本信息（id + role_id，role 映射待决） */
    private function resolveActor(): array
    {
        $this->request->getTokenUser();
        $adminId = (string) $this->request->getUserID();
        $admin = (new AdminService())->getUserById($adminId);
        return [
            'actor_id' => $adminId,
            'role_id'  => $admin ? (int) $admin->role_id : 0,
            'is_admin' => $admin ? (int) $admin->is_admin : 0,
        ];
    }
}
