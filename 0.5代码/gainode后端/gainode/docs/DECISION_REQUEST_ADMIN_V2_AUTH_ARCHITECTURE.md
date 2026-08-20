# Owner Decision Request: Admin V2 认证与路径架构（Admin V2 控制器前置）

> 起草日期：2026-08-20
> 起草者：DEVELOPMENT-01
> 状态：**RESOLVED — OPTION_A（Owner 2026-08-20 裁决，见「裁决结果」）**
> 影响：admin.yaml 12 项操作 + Admin 前端 33 权威页的后端 DTO 接口层能否落地

---

## 问题陈述

`openapi/gainode-v2.yaml` 将 admin 操作挂在 `servers: /api/v1` 下，路径如 `/api/v1/admin/markets`。
但本项目认证体系按 `$request->app` 区分守卫（`support/Request::getTokenUser()`）：

```php
if($this->app=='admin' && $jwtData->guard=='admin'){ ... AdminAuth ... }
elseif($this->app=='api' && ($jwtData->guard=='member'||'api')){ ... MemberAuth ... }
```

`$request->app` 由 Webman 按控制器所属目录自动判定：`app/api/controller/**` → `app='api'`；
`app/admin/controller/**` → `app='admin'`。当前 `config/route/v2.php` 的 `/api/v1` 组指向
`app/api/controller`，因此：

- **admin 令牌（guard='admin'）在 `/api/v1`（api 应用）下无法通过 `getTokenUser()`** → 认证不识别。
- **member 令牌在 api 应用下只能访问 C 端端点**。

而 admin.yaml 的写操作（赛果/结算/冲正/审批/参数发布）明确需要 admin 角色 + SoD 守卫，
Admin 前端也以 admin 登录。因此「Admin V2 = C 端 `/api/v1` 组 + admin 令牌」这一组合在当前认证架构下不可行，需要裁决。

## 关键事实

| 事实 | 证据 |
|---|---|
| admin guard 认证 | `AdminAuth::guard='admin'`；`getTokenUser()` 需 `app=='admin' && guard=='admin'` |
| api guard 认证 | `getTokenUser()` 需 `app=='api' && (guard=='member'||'api')` |
| V2 组当前指向 | `config/route/v2.php` 组 `/api/v1` → `app/api/controller/**`（api 应用） |
| admin.yaml 操作 | 12 项（market/result/settlement/refund/correction/case/approval/audit/async/export），多为写操作需 admin 角色 |
| Admin 前端登录 | `app/admin/controller/LoginController` 用 AdminAuth 签发 guard='admin' 令牌 |

## 待裁决

```text
DECISION_ID = ADMIN-V2-AUTH-01
DECISION_REQUIRED = Admin V2 操作的认证守卫与路径归属
AFFECTED = admin.yaml 12 项 + Admin 33 权威页后端 DTO
```

**OPTION_A — Admin V2 独立 admin 应用组（推荐）**：Admin 写/读操作放在 `app/admin/controller/v2/**`
（或 `app/admin/controller/**`），通过独立 `/api/v1/admin`（或沿用 admin 路由机制）注册，
`$request->app='admin'` → 走 AdminAuth + Casbin RBAC。契约路径 `/api/v1/admin/...` 由前端代理重写到后端 admin 应用。
- 优点：认证/角色/SoD 天然正确，复用现有 admin RBAC；写操作 fail-closed 守卫齐全。
- 缺点：契约 path 前缀 `/api/v1/admin` 与 admin 应用实际挂载点需对齐（可加路由组或反向代理重写）。

**OPTION_B — 扩展 api 守卫认 admin 令牌**：修改 `getTokenUser()` 使 api 应用也认 guard='admin' 令牌，
Admin V2 控制器仍放 `app/api/controller`，控制器内自行做 admin 角色断言。
- 优点：不新增应用组，契约 path 直通。
- 缺点：**修改核心认证守卫逻辑**（影响面大，可能引入越权风险）；api 与 admin 语义耦合，违反职责分离；需谨慎。

**OPTION_C — Admin V2 全部 fail-closed 占位**：Admin 写操作控制器只返回 DEPENDENCY_UNAVAILABLE/POLICY_DENIED，
不绑定真实服务；只读（audit_log）用 admin 令牌但暂不落地。
- 优点：安全，不碰认证架构。
- 缺点：Admin 前端 33 页无真实后端接口，无法推进 Admin 前端对接。

## 建议

**OPTION_A**。理由：认证/角色/SoD 走既有 admin RBAC 最正确，符合「Admin 写操作默认 fail-closed + admin 守卫」纪律；
不与 C 端认证混淆。契约 path 与后端应用的差异通过路由组/代理对齐，不改核心认证逻辑。

裁决后动作：
1. 若选 A：新建 `app/admin/controller/v2/AdminV2Controller.php`（或按域拆分），绑定 Result/Refund/Correction/
   Approval/Audit/Market service；audit_log 只读先落地；写操作 fail-closed。
2. 注册 admin 应用路由（复用 getRouteList('admin') 或新增组）。
3. 更新本 Decision + manifest/context。

## 裁决结果（Owner 2026-08-20）

```text
DECISION = OPTION_A（独立 admin 应用组）
IMPLEMENTED =
  - app/admin/controller/v2/AdminV2Controller.php（extends ApiV2，位于 admin 应用）
  - config/route/admin.php 新增 Route::group('/api/v1/admin', ...) 加载 sys_route module='admin_v2'
  - sql/20260820_admin_v2_routes_seed.sql：audit-log(只读) / async-jobs(FAIL_CLOSED) / export-tasks(FAIL_CLOSED)
  - 认证：控制器位于 app/admin/controller/v2 → $request->app='admin' → getTokenUser() 走 AdminAuth
  - wiring 契约测试 60 断言全绿
Admin 写操作（市场/结算/退款/更正/审批）= FAIL_CLOSED（admin 角色映射 sys_admin.role_id → 05 13 角色未冻结）
```

## 裁决前安全姿态

- Admin V2 写操作保持无控制器/无路由（路由缺失即关闭）。
- audit_log 等 admin 只读接口暂不落地，避免在 api 应用下用 admin 令牌导致认证错误。
