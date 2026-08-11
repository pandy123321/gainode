# 架构说明

## 已确认信息

### 整体架构

Gainode 采用前后端分离架构。后端基于 PHP Webman (Workerman) 技术栈，事件驱动、常驻内存。

```text
┌──────────────────────────────────────────────────────────────┐
│                      Client Layer                             │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐ │
│  │  Mobile / H5  │  │  App (iOS /  │  │    Admin Web       │ │
│  │  (Vue 3 + TS) │  │   Android)   │  │   (Vue 3 + TS)     │ │
│  │  4 Tab 导航    │  │   (Flutter)  │  │   (8 一级导航)     │ │
│  └───────┬───────┘  └──────┬───────┘  └──────────┬─────────┘ │
└──────────┼─────────────────┼──────────────────────┼───────────┘
           │    REST/JSON (OpenAPI 3.1)              │
           ▼                 ▼                        ▼
┌────────────────────────────────────────────────────┐
│                Webman HTTP Server                  │
│  Port 8787 (主) / 8786 (Task) / 8686 (协程)       │
│                                                    │
│  ┌──────────────────────────────────────────────┐ │
│  │  Middleware: Cors → ActionHook → Auth         │ │
│  │           (admin middleware)                  │ │
│  │  Middleware: Cors → ActionHook → Auth         │ │
│  │           (api middleware)                    │ │
│  └──────────────────┬───────────────────────────┘ │
│                     │                              │
│  ┌──────────────────┼──────────────────────────┐  │
│  │       Controllers (app/ directory)           │  │
│  │                                              │  │
│  │  admin/controller/   api/controller/         │  │
│  │  ├─ arbitrage/       ├─ AccountController    │  │
│  │  ├─ member/          ├─ LoginController      │  │
│  │  └─ sys/             └─ ...                  │  │
│  └──────────────────┬───────────────────────────┘  │
│                     │                              │
│  ┌──────────────────┼──────────────────────────┐  │
│  │        Services (library/service/)           │  │
│  │                                              │  │
│  │  arbitrage/  member/  sys/  auth/            │  │
│  │  (extends support\extend\Service)            │  │
│  └──────────────────┬───────────────────────────┘  │
│                     │                              │
│  ┌──────────────────┼──────────────────────────┐  │
│  │        DAOs (library/dao/)                   │  │
│  └──────────────────┬───────────────────────────┘  │
│                     │                              │
│  ┌──────────────────┼──────────────────────────┐  │
│  │        Models (library/model/)               │  │
│  │    (extends support\extend\Model)            │  │
│  └──────────────────┬───────────────────────────┘  │
│                     │                              │
│  ┌──────────────────┼──────────────────────────┐  │
│  │           Data Layer                          │  │
│  │                                              │  │
│  │  MySQL 8.4.9                Redis            │  │
│  │  ├─ webman (主库)       ├─ default (DB 0)    │  │
│  │  └─ gainode            ├─ cache (DB 1)      │  │
│  │                         └─ stack (端口 6380)  │  │
│  └───────────────────────────────────────────────┘ │
│                                                    │
│  ┌──────────────────────────────────────────────┐ │
│  │        Background Processes (process/)        │ │
│  │                                              │ │
│  │  CrontabTask    ArbitrageTask   ChannelServer │ │
│  │  (定时任务)      (套利引擎)      (pub/sub)    │ │
│  │                                              │ │
│  │  Pusher          Task                         │ │
│  │  (WebSocket推送) (通用任务Worker)             │ │
│  └──────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

### 进程拓扑

| 进程 | 端口 | 协议 | 数量 | 控制方式 | 用途 |
|---|---|---|---|---|---|
| `Webman\App` (主) | 8787 | HTTP | — | 框架内置 | API 请求处理 |
| `task_server` | 8786 | HTTP | 2 | `APP_PROCESS_LIST` 环境变量 | 异步任务处理 |
| `coroutine_server` | 8686 | HTTP | 1 | `APP_PROCESS_LIST` (默认关闭) | Swoole 协程服务 |
| `crontab_task` | — | — | 1 | `APP_PROCESS_LIST` | 定时任务（DB 驱动） |
| `arb_task` | — | — | 1 | `APP_PROCESS_LIST` | 套利引擎周期任务 |
| `channel_server` | 2206 | frame | 1 | `APP_PROCESS_LIST` | 进程间发布/订阅 |
| `pusher_server` | 8888 | WebSocket | 2 | `APP_PROCESS_LIST` | 客户端实时推送 |
| `proxy_server` | 8989 | HTTP | cpu_count | `APP_PROCESS_LIST` | HTTP 代理 |
| `task` | — | — | 1 | 始终启用 | 通用异步 Worker |

> 说明：生产环境通过 Docker `docker-compose.yml` 将 `APP_PROCESS_LIST` 设为 `"task_server,crontab_task,arb_task"`。

### 代码分层约定

| 层 | 命名空间 | 基类 | 职责 |
|---|---|---|---|
| **Controller** | `app\admin\controller\` / `app\api\controller\` | `support\extend\Controller` | 请求解析、参数校验、调用 Service、组装响应 |
| **Service** | `library\service\` | `support\extend\Service` | 业务逻辑编排、事务管理、调用 DAO |
| **DAO** | `library\dao\` | `support\extend\Dao` | 数据库查询封装 |
| **Model** | `library\model\` | `support\extend\Model` | 数据表映射、ActiveRecord 风格 CRUD |

Service 中通过 `$this->dao = XxxDao::class` 注入 DAO，基类自动提供 `$this->getNewDao()` 和代理调用方法（create/update/get/find/fetch 等）。

### 路由机制

路由存储在 `sys_route` 数据库表中，启动时动态加载：

```php
// config/route/admin.php
$routes = getRouteList("admin", true);
foreach ($routes as $v) {
    Route::add($v['methods'], $v['route_url'], [$v['path'], $v['action']])
         ->middleware(explode(',', $v['middleware']));
}
```

新增 API 只需在 `sys_route` 表中插入一行，无需修改路由文件。

### 模块职责

| 模块 | 职责 | V6.1 差距 |
|---|---|---|
| **Mobile/H5** | Vue 3 + TypeScript。4 个底部导航。75 个 P0 页面。375/390/430 三尺寸兼容。 | 原型阶段进行中 |
| **App (iOS/Android)** | Flutter。原生 App，后续阶段开发。 | 全新 |
| **Admin Web** | Vue 3 + TypeScript。8 个一级导航，58 个 Page ID（51 Admin + 7 Agent Portal），基于现有 RBAC 后台改造。含 P1_CONDITIONAL 页面（A-USER-004 资产调整，Preview-Only）。 | 需从旧菜单结构迁移至 V6.1 8 导航 |
| **Admin Governance (V2.4.1)** | Contract Gap Registry（Page→Gap JOIN）、Blocking/NON-BLOCKING Gap 分类、SoD 权限矩阵、Evidence-Based QA 框架。 | 治理基线已合入 04（审核 #491 零 Finding） |
| **Auth** | JWT 登录、注册、OAuth、验证码、Token 刷新/撤销、JWT 缓存黑名单。 | 缺 MFA 多因素认证、OTP 验证、Session 设备管理 |
| **KYC** | `member_user_kyc` 表 + 后台审核队列。 | 缺多级状态机、补件流程、与 FeatureEntitlement 联动 |
| **现有套利引擎** | BetBurger 信号采集 → API-Football 比赛同步 → 窗口下单 → 结算。基于日计划（`arbitrage_day_plan`）和仓位（`arbitrage_position`）。**方案 B**：保留为 V6.1 AI 经济引擎基础，`confirmed_profit` 写入内部指标（`reference_profit → mapped_apt_budget`），不对 C 端暴露。 | 改造为不直接对 C 端暴露；保留 `ArbitrageTask` 进程和 10 张 arb 表 |
| **Robot & Reward** | — | 全新模块 |
| **Prediction Engine** | — | 完全重写（现有 arbitrage 不是竞猜） |
| **APT & Power & OTC** | 有 wallet 系统（可变余额+日志） | 重大改造（需 append-only Ledger + Power + OTC 撮合） |
| **Shared Domain** | User、Level、Team。缺 FeatureEntitlement。 | 需新增 FeatureEntitlement 服务端统一评估 |
| **Parameter Center** | `sys_dict` / `sys_config`（简单 KV 配置，无版本控制） | 需改为 Release-based 版本化参数管理 |
| **Approval Engine** | Casbin RBAC + RESTful 权限，无审批工作流 | 需全新 Approval 工作流引擎 |
| **Audit** | `sys_operation_logs` / `sys_admin_logs` | 需强化按 request/object/approval 追踪 |
| **Notification** | 同步发送（SwiftMailer/SMS/Telegram），`sys_notice` 表 | 需改为 Outbox Pattern + 异步投递 + 去重 + 重试 |
| **Support** | 无工单系统 | 全新模块（Ticket/TicketMessage/TicketAttachment） |

### 外部依赖

| 依赖 | 当前状态 | 用途 |
|---|---|---|
| **BetBurger** | 已集成（`BetBurgerClient`） | 体育套利信号源（V6.1 中可能转为内部 AI 引擎数据源） |
| **API-Football** | 已集成（`ApiFootballClient`） | 比赛数据、球队名称匹配 |
| **结果源（主/备）** | 待确认 | Prediction 赛果确认 |
| **通知渠道** | SwiftMailer（邮件）/ Smsbao（短信）/ Telegram Bot | PUSH/EMAIL/SMS/IN_APP 需统一 Outbox 框架 |
| **汇率源** | 待确认 | APT 参考估值 |
| **KYC 证据服务** | 待确认 | 身份验证 |
| **区块链节点** | BSC/ETH/TRON RPC 已配置 | 链上转账、USDT 合约交互（当前代码已注释） |

### 调用链与数据流

```text
用户操作
  → HTTP POST (JSON body, Headers: Token/Sign/Timestamp/Version/Language/TraceId)
    → Webman HTTP Server (端口 8787/8786)
      → Cors 中间件
        → ActionHook 中间件（操作日志记录 + 签名校验）
          → Auth 中间件（JWT Token 解析 → Casbin RBAC 权限检查）
            → Controller 解析参数 + Illuminate Validation 校验
              → Service 编排业务逻辑
                → DAO 执行数据库操作
                  → Model (illuminate/database) 执行 SQL（PDO → MySQL 8.4）
              → Event::emit() 触发事件（如 user.login、user.finishRechargeOrder）
                → Redis Queue 投递异步任务
                  → Redis Queue Consumer 处理（WriteLogs、SendMessage 等）
              → 返回统一 JSON 响应
```

### 现有数据表分组

详见 `sql/database.sql`（55+ 张表）：

| 分组 | 表数 | 主要表 |
|---|---|---|
| **arbitrage** | 10 | `arbitrage_attempt`、`arbitrage_day_plan`、`arbitrage_fixture`、`arbitrage_position`、`arbitrage_project`、`arbitrage_project_order`、`arbitrage_signal`、`arbitrage_signal_raw` |
| **member** | 14 | `member_user`、`member_user_auth`、`member_user_kyc`、`member_user_team`、`member_user_wallet`、`member_user_wallet_log`、`member_recharge_order`、`member_withdraw_order`、`member_level` |
| **sys** | 32 | `sys_admin`、`sys_role`、`sys_route`、`sys_menus`、`sys_casbin_rbac`、`sys_crontab`、`sys_dict`、`sys_lang`、`sys_config`、`sys_web3_*` |

### 关键 API 约定

- API 风格：REST/JSON，OpenAPI 3.1（待引入 swagger-php）
- 路由前缀：`/v1/`
- 请求头必带：Token / Sign / Timestamp / Version / Language / TraceId
- 时间：UTC timestamp，客户端本地化
- 金额：string decimal，禁止 JS float
- 分页：cursor-based（待从现有 offset 分页迁移）
- 写入幂等：所有写操作必须 idempotency_key（待实现）
- 并发控制：If-Match / object_version（待实现）
- 错误码区分：VALIDATION_ERROR(400)、AUTH_UNAUTHENTICATED(401)、POLICY_DENIED(403)、IDEMPOTENCY_CONFLICT(409)、QUOTE_EXPIRED(409)、INSUFFICIENT_APT(422)、INSUFFICIENT_POWER(422)、MARKET_LOCKED(422)、RESULT_UNKNOWN(202)

### 禁止事项

1. 前端不得自行判断资格（必须读 allowed_actions/entitlement）。
2. 前端不得用 JS float 做资产计算。
3. 前端不得用 Mock 值作为生产 fallback。
4. 后端不得覆盖或删除历史账本记录（修正用 reversal 追加）。
5. 参数不得绕过 Approval 直接修改生效。
6. 业务状态不得因通知失败回滚。
7. 同一申请人不得审批自己的申请。
8. Prediction 与 AI 资金不得互相补贴。
9. 用户端不得出现 APR/APY/固定收益/保本/博彩化词汇。
10. 正式 UI 不得显示 Demo/Mock/Sandbox 标签。
11. 超级管理员不得绕过账本、审批或审计规则。
12. TBC 生产参数不得用本地默认值补齐。
13. 不得删除 sys_route 表中的数据库路由记录（会导致 API 404）。
14. 不得在生产环境启用 file_monitor 进程（热重载仅开发环境）。
15. 授权不得简化为纯 RBAC：`FINAL_AUTHORIZATION = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD`。
16. UI Persona 不得作为 Role Grant（PRESENTATION_ONLY）。
17. SoD 不得仅靠"当前激活 Role"检查：必须为 Actor-level Invariant（同一 Workflow Object 冲突阶段由不同 Actor ID 执行）。

## 基于代码的推断

- 现有分层（Controller→Service→DAO→Model）可以直接作为 V6.1 新模块的模板。新模块（Robot/Power/OTC/Prediction）应在 `library/` 下按领域建立 `model/`、`dao/`、`service/` 三个子目录。
- 现有 arbitrage 引擎的日程编排模式（DayPlan → Windows → Position → Settlement）可以作为 Prediction 结算引擎的参考。
- 现有 `sys_dict` 表可作为 Parameter Center 从简单到版本化迁移的起点。
- 路由 DB 驱动设计天然支持 API 版本的动态管理，可扩展到 OpenAPI 3.1 文档自动生成。

## 待确认事项

- [ ] 现有 arbitrage 模块处置：保留为 AI 经济引擎（方案 B）/ 完全废弃 / 保留为独立功能
- [ ] 数据库分库策略：Ledger 表放 `gainode` 库还是新建 `gainode_ledger` 库
- [ ] 前端框架选型（React/Vue）
- [ ] 结果源、通知渠道（PUSH/SMS 服务商）、汇率源的具体服务商
- [ ] 测试框架引入（PHPUnit 版本）
- [ ] OpenAPI 文档生成方案（swagger-php 或手动维护）
- [ ] 数据库 migration 方案（Phinx 或 Laravel Migration）

## 信息来源

- `0.5代码/gainode后端/gainode/composer.json`（技术栈依赖）
- `0.5代码/gainode后端/gainode/config/process.php`（进程拓扑）
- `0.5代码/gainode后端/gainode/config/database.php`（数据库连接）
- `0.5代码/gainode后端/gainode/config/arbitrage.php`（套利引擎配置）
- `0.5代码/gainode后端/gainode/config/route/admin.php` + `api.php`（路由加载逻辑）
- `0.5代码/gainode后端/gainode/sql/database.sql`（数据表结构）
- `0.5代码/gainode后端/gainode/support/extend/Service.php`（Service 基类模式）
- `0.5代码/gainode后端/gainode/support/extend/Model.php`（Model 基类模式）
- `0.5代码/gainode后端/gainode/Dockerfile` + `docker-compose.yml`（部署架构）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（API 契约）
- `Gainode_Development_Ready_V6.1_Latest/04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md`（Admin 页面规范，V2.4.1 治理基线已合入）
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（开发顺序）
- `0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/GAINODE_ADMIN_CONTRACT_GAP_REGISTER_V2.4.1.md`（Contract Gap 登记表）
