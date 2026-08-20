# Owner Decision Request: V2 路由路径方案（STAGE-02 后端 API 接线前置）

> 起草日期：2026-08-20
> 起草者：DEVELOPMENT-01（Gainode 唯一 Development Agent）
> 关联：07 §7（STAGE-02 路由复用 sys_route）、05 §1/§2（API 路径契约 `/api/v1/...`）、
>       `openapi/gainode-v2.yaml`（servers: /api/v1）、`config/route/api.php` + `admin.php`
> 状态：**RESOLVED — OPTION_A（Owner 2026-08-20 裁决，见下「裁决结果」）**
> 影响：STAGE-02 已接线但 HTTP 不可达的 Auth/Kyc/User 控制器 + 本包新增的只读控制器能否注册路由

---

## 问题陈述

`config/route.php` 使用 `Route::disableDefaultRoute()` + `config/autoload.php` 的 `routes` 加载
`config/route/api.php` 与 `config/route/admin.php`。这两个文件都用：

```php
Route::group('/v1', function () {
    $routes = getRouteList("api", true);   // 读 sys_route 表 module='api'
    foreach ($routes as $v) {
        $route = Route::add($v['methods'], $v['route_url'], [$v['path'], $v['action']]);
    }
});
```

`ReflectionService::getRouteList()` 把 `sys_route.url` 列（形如 `/api/login`）直接作为 `route_url`。
因此**实际生效路径 = 组前缀 `/v1` + sys_route.url**，即 `/v1/api/...`。

而权威契约 05 §1/§2 与 `openapi/gainode-v2.yaml` 定义的路径是 **`/api/v1/...`**
（例如 `/api/v1/auth/register`）。前端 H5/Admin 的 API client 也按 `/api/v1/...` 调用。

**矛盾**：契约与前端使用 `/api/v1/...`，而现有 sys_route 机制产出 `/v1/api/...`。
两者顺序不同，无法通过单纯加 sys_route 行解决——需要决定 V2 路由的注册路径方案。

这属于规则性（API/路由/范围）修改，按 07 §7 与 rules 规定：Agent 不得猜测，必须提交
Decision Request 由 Owner 裁决；裁决前不注册会造成路由漂移的猜测路径。

## 关键事实

| 事实 | 证据 |
|---|---|
| 契约路径 | 05 §1/§2 = `/api/v1/...`；`openapi/gainode-v2.yaml` `servers.url` = `/api/v1` |
| 前端调用 | `gainode_h5_v2/src/api/*.ts`、`gainode_admin_v2/src/api/http-v2.ts` = `/api/v1/...` |
| 运行时机制 | `config/route/api.php` 组 `/v1` + sys_route.url `/api/...` → `/v1/api/...` |
| 现有 V1 路由 | `sql/database.sql` L2243+ 的 api 行 url 均 `/api/...`（组 `/v1` 下 = `/v1/api/...`） |
| H5 dev 代理 | `gainode_h5_v2/vite.config.ts` 代理前缀 `/v1/api` → 127.0.0.1:8789 |
| 已接线但不可达 | `AuthController` / `KycController` / `UserController`（extends ApiV2）已存在，但 sys_route 无对应行 → HTTP 404 |

## 待裁决

```text
DECISION_ID = V2-ROUTE-PATH-01
DECISION_REQUIRED = V2 API 路由的权威生效路径
AFFECTED = STAGE-02 全部 C 端/Admin 控制器路由注册
CURRENT_AUTHORITY = Owner（契约 05 已冻结为 /api/v1/...）
```

**OPTION_A — 契约对齐（推荐）**：以 05/OpenAPI 为准，V2 路由生效路径 = `/api/v1/...`。
实现：新增独立 `config/route/v2.php`（或调整 api/admin.php），为 V2 控制器建立 `/api/v1` 组前缀，
sys_route url 列存 `/api/v1/...` 全路径；V1 的 `/v1` 组 + `/api/...` 保留不动（不破坏 V1 线上）。
- 优点：与冻结契约/前端/OpenAPI 三者一致；V1 不受影响。
- 缺点：需在 route 配置新增一个 `/api/v1` 组（属"复用 sys_route 机制、非第二套框架"，仍走 sys_route 表）。

**OPTION_B — 沿用 V1 运行时**：V2 路由沿用现有 `/v1` 组 + `/api/...` 机制，生效路径 = `/v1/api/...`，
并把契约/前端 base 统一改写为 `/v1/api/...`。
- 优点：不新增 route 配置，与 V1 完全同机制。
- 缺点：**违反已冻结契约 05（/api/v1）**，需改动 OpenAPI servers + 前端全部 API client 路径，
  属于破坏冻结契约的重写，代价高且风险大。不推荐。

**OPTION_C — 混合静态**：V2 路由不写 sys_route，直接在 config/route/api.php 静态声明 `/api/v1/...` 到控制器。
- 优点：直白。
- 缺点：违反 07 §7「路由只走 sys_route 表，未经架构 Change Request 不建立第二套路由框架」。
  不推荐（除非 Owner 明确豁免）。

## 建议

**OPTION_A**。理由：契约与前端均已按 `/api/v1/...` 冻结，唯一矛盾在运行时组前缀；
建立 `/api/v1` 组并复用 sys_route 表最贴近「复用现有路由框架」要求，且不触碰 V1。

## 裁决结果（Owner 2026-08-20）

```text
DECISION = OPTION_A（新增 /api/v1 组，复用 sys_route 表，V1 /v1 组保留不动）
IMPLEMENTED =
  - 新增 config/route/v2.php：Route::group('/api/v1', ...) 加载 sys_route module='api_v2'
  - config/autoload.php routes 追加 config_path('route/v2.php')
  - sql/20260820_v2_api_routes_seed.sql：module='api_v2'，url 为相对路径（组前缀 + url = /api/v1/<url>）
  - 已接线 Auth/Kyc/User + 新增 Ledger/Robot/Parameter/Prediction/Otc 只读控制器全部注册
  - 中间件 Cors+RequestContext+ActionHook 由 config/middleware.php 'api' 全局注入；鉴权由控制器 getTokenUser() 强制
V1 /v1 组 = 未改动
```

裁决后动作 1（注册已接线控制器）已落实；后续 V2 写路径控制器按同一 sys_route(api_v2) 模式追加。

## 裁决前安全姿态

- 不注册任何可能造成路由漂移的猜测路径。
- 已接线的 Auth/Kyc/User 控制器保持 HTTP 不可达（路由缺失即 fail-closed，安全）。
- 只读控制器代码（path-independent）可先行落地并提交，路由注册待裁决。
