# 架构说明

## 仓库总览

```
E:\github\sports\
├── .project-ai/                                          # 共享 AI 上下文（本目录）
│   ├── manifest.yaml           # 上下文配置 + 决策登记
│   ├── bootstrap.md            # 项目治理 Bootstrap（PROJECT_BOOTSTRAP_TEMPLATE 填写）
│   ├── context.md              # 项目上下文
│   ├── architecture.md         # 本文件：架构说明
│   ├── glossary.md             # 领域术语
│   ├── rules/                  # 编码/审核规则
│   └── tasks/                  # 当前任务
├── Gainode_Development_Ready_V6.1_Latest/                # 唯一需求权威源
│   ├── README.md
│   ├── 01-08 号规格文档
│   ├── i18n/                                             # 7 语言翻译
│   ├── design-system/
│   └── assets/logo/
├── 0.5代码/
│   ├── gainode后端/gainode/                              # 现有后端（PHP/Webman）— V2.0 升级基线
│   │   ├── app/          # 控制器（admin / api / command / queue）
│   │   ├── library/      # 业务逻辑（model / dao / service / dict / event）
│   │   ├── support/      # 基础设施（arbitrage / extend / utils / middleware）
│   │   ├── process/      # 长驻进程（ArbitrageTask / CrontabTask / ChannelServer / Pusher）
│   │   ├── config/       # 配置文件
│   │   └── sql/database.sql  # 60+ 张表
│   ├── admin-proto/      # Admin 交互原型（HTML/CSS/JS，57/58 页可开发）
│   └── *.md              # 设计文档
├── _existing_prod/                                        # V1.x 生产代码镜像（只读，升级基线）
│   ├── gainode_h5/       # 来源：https://github.com/Xfd100/gainode_h5.git
│   │   │                   Vue 3.5 + TS 6.0 + Vite 8，19 views / 17 routes
│   └── gainode_admin/    # 来源：https://github.com/Xfd100/gainode_admin.git
│                           Vue 3.3 + TS 4.5 + Vite 4 + Layui Vue + Pinia 2，46 routes
├── 通过agent开发前规则/                                   # 外部治理规则（只读）
└── 历史文档/                                             # 历史归档（禁止用于需求推导）
```

## 1. 目标架构（V2.0）

Gainode V2.0 在 V1.x 基础上升级，采用前后端分离架构，后端基于 PHP Webman (Workerman)。

```text
┌──────────────────────────────────────────────────────────────┐
│                      Client Layer                             │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐ │
│  │  Mobile / H5  │  │  App (iOS /  │  │    Admin Web       │ │
│  │  (Vue 3 + TS) │  │   Android)   │  │   (Vue 3 + TS)     │ │
│  │  4 Tab 导航    │  │   (Flutter)  │  │   (8 一级导航)     │ │
│  │  V1→V2 升级    │  │   V2 全新     │  │   V1→V2 升级       │ │
│  └───────┬───────┘  └──────┬───────┘  └──────────┬─────────┘ │
└──────────┼─────────────────┼──────────────────────┼───────────┘
           │    REST/JSON (OpenAPI 3.1)              │
           ▼                 ▼                        ▼
┌────────────────────────────────────────────────────┐
│                Webman HTTP Server                  │
│  Port 8787 (主) / 8786 (Task) / 8686 (协程)       │
│  Middleware: Cors → ActionHook → Auth              │
│  Controller → Service → DAO → Model               │
│                                                    │
│  Data Layer                                        │
│  MySQL 8.4.9 (webman + gainode)  Redis (3 实例)    │
│                                                    │
│  Background: CrontabTask / ArbitrageTask /         │
│  ChannelServer / Pusher / Task                     │
└────────────────────────────────────────────────────┘
```

## 2. V1.x → V2.0 升级架构

### 2.1 V1.x H5 架构 → V2.0 H5 架构

```text
V1.x H5 (线上运行)                       V2.0 H5 (目标)
┌──────────────────────────┐          ┌──────────────────────────┐
│ main.ts                   │          │ main.ts                   │
│  createApp + Router       │          │  createApp + Router       │
│                            │          │  + Pinia (+ persisted)    │  ← 新增
│                            │          │  + vue-i18n               │  ← 新增
│                            │          │  + Vant 4 (mobile-first)   │  ← 新增
├──────────────────────────┤          ├──────────────────────────┤
│ api/http.ts (fetch)       │          │ api/http.ts (fetch)       │
│  MD5 sign (200+ lines JS) │          │  MD5 sign (crypto-js)     │  ← 整理
│  SIGN_PRIVATE_KEY 硬编码   │          │  import.meta.env.VITE_KEY │  ← 外置
├──────────────────────────┤          ├──────────────────────────┤
│ stores/user.ts (custom)   │          │ stores/user.ts (Pinia)    │  ← 迁移
│ stores/project.ts         │          │ stores/project.ts (Pinia) │
├──────────────────────────┤          ├──────────────────────────┤
│ i18n/index.ts (custom)    │          │ i18n/index.ts (vue-i18n)  │  ← 迁移
│  zh-CN, en-US (260 keys)  │          │  7 languages              │  ← 扩展
├──────────────────────────┤          ├──────────────────────────┤
│ components/ (手写)         │          │ components/ (组件库)      │  ← 替换
│  6 shared components      │          │  + 手写业务组件            │
├──────────────────────────┤          ├──────────────────────────┤
│ views/ (19 views)          │          │ views/ (44 P0 pages)      │  ← 扩展
│  4 Tab + 15 standalone    │          │  + V6.1 新页面             │
├──────────────────────────┤          ├──────────────────────────┤
│ 无测试                     │          │ __tests__/ (Vitest)       │  ← 新增
│ 无 .env                    │          │ .env / .env.development   │  ← 新增
└──────────────────────────┘          └──────────────────────────┘
```

### 2.2 V1.x Admin 架构 → V2.0 Admin 架构

```text
V1.x Admin (线上运行)                    V2.0 Admin (目标)
┌──────────────────────────┐          ┌──────────────────────────┐
│ main.ts                   │          │ main.ts                   │
│  createApp + Router       │          │  createApp + Router       │
│  + Pinia + persistedstate │          │  + Pinia + persistedstate │
│  + Layui Vue              │          │  + Element Plus (migrate)  │  ← 新增
│  + Lay JSON Schema Form   │          │  + JSON Schema Form       │  ← 保留
├──────────────────────────┤          ├──────────────────────────┤
│ api/http.ts (axios)       │          │ api/http.ts (axios)       │
│  MD5 sign + AES password  │          │  MD5 sign + AES password  │
│  密钥硬编码                │          │  密钥外置 (.env)          │  ← 修复
├──────────────────────────┤          ├──────────────────────────┤
│ stores/user.ts (Pinia)    │          │ stores/user.ts (Pinia)    │
│  permissions (string[])   │          │  permissions (bitmask?)   │  ← 优化
│  menus (tree from API)    │          │  menus (tree from API)    │
├──────────────────────────┤          ├──────────────────────────┤
│ lang/ (vue-i18n)          │          │ lang/ (vue-i18n)          │
│  zh_CN, en_US, ko         │          │  7 languages              │  ← 扩展
├──────────────────────────┤          ├──────────────────────────┤
│ layouts/                   │          │ layouts/                   │
│  BasicLayout (sidebar+    │          │  BasicLayout (保留+升级)   │  ← 保留核心
│   header+multi-tab)       │          │  + 58 Page 导航            │  ← 扩展
│  面包屑+主题+响应式         │          │  面包屑+主题+响应式        │
├──────────────────────────┤          ├──────────────────────────┤
│ Components (7 custom)     │          │ Components (Schema-driven) │  ← 扩展
│  Schema-driven 已就绪      │          │  TableSearch/Form/Action   │
├──────────────────────────┤          ├──────────────────────────┤
│ views/ (46 routes)         │          │ views/ (58 Page IDs)      │  ← 扩展
│  V1.x 业务页面             │          │  V6.1 全部 Admin 页面      │
├──────────────────────────┤          ├──────────────────────────┤
│ Vite 4, TS 4.5            │          │ Vite 5/6, TS 5+           │  ← 升级
│ 无测试                     │          │ Vitest                    │  ← 新增
│ 无 .env                    │          │ .env                      │  ← 新增
└──────────────────────────┘          └──────────────────────────┘
```

### 2.3 V1.x Backend → V2.0 Backend 升级

```text
V1.x Backend (线上运行)                   V2.0 Backend (目标)
┌──────────────────────────┐          ┌──────────────────────────┐
│ 现有模块（保留 + 扩展）：   │          │ 现有模块改造：              │
│  Auth (JWT)              │  保持    │  Auth (JWT + MFA/OTP 扩展)│
│  Wallet (充提)            │  改造    │  APT/Power/OTC (四账分离)  │
│  Team (团队)              │  保持    │  Team + Affiliate/Agent    │
│  KYC                     │  扩展    │  KYC (多级状态机)           │
│  Mining (矿机)            │  改造    │  Robot (AI 代理 56 级)     │
│  Signal/Arbitrage        │  改造    │  AI Economic Engine (内部)  │
│  RedEnvelope             │  改造    │  Reward 发放通道            │
│  Content                 │  保持    │  Content                   │
│  System (RBAC)           │  保持    │  System (Casbin 扩展)       │
│  Configuration           │  保持    │  Parameter Center (版本化)   │
├──────────────────────────┤          ├──────────────────────────┤
│                            │          │ 新增模块：                  │
│                            │          │  Prediction (Football 1X2) │
│                            │          │  OTC (用户间撮合)           │
│                            │          │  AI Operations (策略+建议)  │
│                            │          │  Approval Engine (工作流)   │
│                            │          │  Support/Ticket             │
│                            │          │  Notification (Outbox)      │
│                            │          │  Audit (按维度追踪)         │
│                            │          │  Affiliate/Agent Portal     │
└──────────────────────────┘          └──────────────────────────┘
```

## 3. 进程拓扑

| 进程 | 端口 | 协议 | 用途 | V1→V2 变更 |
|---|---|---|---|---|
| `Webman\App` | 8787 | HTTP | API 请求处理 | 不变 |
| `task_server` | 8786 | HTTP | 异步任务处理 | 不变 |
| `crontab_task` | — | — | 定时任务（DB 驱动） | 不变 |
| `arb_task` | — | — | 套利引擎周期任务 | 改造为 AI 经济引擎 |
| `channel_server` | 2206 | frame | 进程间发布/订阅 | 不变 |
| `pusher_server` | 8888 | WebSocket | 客户端实时推送 | 不变 |
| `proxy_server` | 8989 | HTTP | HTTP 代理 | 不变 |
| `task` | — | — | 通用异步 Worker | 不变 |

## 4. 代码分层约定

| 层 | 基类 | 职责 | V1→V2 变更 |
|---|---|---|---|
| **Controller** | `support\extend\Controller` | 请求解析、参数校验、调用 Service、组装响应 | 不变 |
| **Service** | `support\extend\Service` | 业务逻辑编排、事务管理、调用 DAO。每个 Service 声明 `@authoritative_writer` 注解 | 新增注解 |
| **DAO** | `support\extend\Dao` | 数据库查询封装 | 不变 |
| **Model** | `support\extend\Model` | 数据表映射、ActiveRecord 风格 CRUD。定义 `TABLE` 常量 | 新增常量规范 |

Service 通过 `$this->dao = XxxDao::class` 注入 DAO，基类自动提供代理调用方法。

### V2.0 新模块目录约定

```
library/
├── model/
│   ├── Robot.php, RobotLevel.php, RobotClaim.php      # Robot 模块
│   ├── Prediction*.php                                  # Prediction 模块
│   ├── AptLedger*.php, PowerAccount*.php                # APT/Power 模块
│   ├── OtcOrder.php, OtcMatch.php                       # OTC 模块
│   ├── Agent.php, AgentEarning.php, Referral.php        # Affiliate 模块
│   └── AiSignal.php, AiRecommendation.php               # AI 运营模块
├── dao/
│   └── (同名 Dao 类，继承 support\extend\Dao)
├── service/
│   └── (同名 Service 类，继承 support\extend\Service)
└── dict/  (枚举/常量定义)
```

## 5. 路由机制

路由存储在 `sys_route` 数据库表中，启动时动态加载。新增 API 只需插入 `sys_route` 表，无需修改路由文件。

V2.0 需新增的 API 路由前缀：`/v1/api/`（C 端）、`/v1/`（Admin），与 V1.x 保持一致。

## 6. 模块职责与 V1→V2 映射

| 模块 | V1.x 对应 | V2.0 状态 | 差距 |
|---|---|---|---|
| **Mobile/H5** | `gainode_h5` (19 views) | Vue 3 + TS，44 页 P0 | 重构升级 + 25 新页面 |
| **App** | 无 | Flutter（V2 全新） | 从零开始 |
| **Admin Web** | `gainode_admin` (46 routes) | Vue 3 + TS，58 Page ID | 重构升级 + 12 新页面 |
| **Auth** | 手机+邮箱登录，JWT | JWT + MFA/OTP | 缺 MFA/OTP |
| **KYC** | `member_user_kyc` 表 | 多级状态机 | 缺多级状态机 |
| **AI 经济引擎** | arbitrage (Signal/Arbitrage) | BetBurger + API-Football（合同已签） | 改造为内部引擎（方案 B） |
| **Robot & Reward** | Mining (矿机订单) | 56 级 AI 代理 | 全新模块（完全重写） |
| **Prediction** | 无 | Football Pre-match 1X2 | 全新模块（完全重写） |
| **APT & Power & OTC** | Wallet (充提) | 四账分离 + OTC 撮合 | 重大改造（append-only） |
| **Affiliate / Agent** | Team (团队) | 4+7 页（已纳入 V6.1） | 基于 Team 扩展 |
| **AI 运营** | 无 | 策略模拟 + 建议管线（已纳入 V6.1） | 全新模块 |
| **Parameter Center** | `sys_dict`/`sys_config` | 版本化管理 | 需版本化管理 |
| **Approval Engine** | Casbin RBAC | 需工作流引擎 | 需扩展 |
| **Audit** | `sys_operation_logs` | 按维度追踪 | 需强化 |
| **Notification** | 同步发送 | Outbox Pattern | 重大改造 |
| **Support** | 无 | 全新模块 | 从零开始 |

## 7. V1.x Admin Schema 驱动架构（保留 + 扩展）

V1.x Admin 的 Schema 驱动架构是核心优势，V2.0 保留并扩展：

```text
V1.x 已有端点（示例）：
  getSearchSchemaForm(code)     → 返回搜索表单字段定义
  getListSchemaForm(code)       → 返回表格列定义
  getCreateSchemaForm(code)     → 返回创建表单字段定义
  getUpdateSchemaForm(code)     → 返回更新表单字段定义

V2.0 扩展：
  - 58 个 Page ID 全部对应 Schema Code
  - Schema 需包含：字段名、类型、校验规则、权限控制、国际化 label key
  - Schema 需支持：条件显示（if/else）、联动（cascade）、只读（readonly）
  - 前端组件：TableSearchSchema, TableActionSchema, TableToolsSchema, FormOpenSchema
```

## 8. 外部依赖

| 依赖 | 状态 | 用途 | V1→V2 变更 |
|---|---|---|---|
| **BetBurger** | 合同已签，`BetBurgerClient` 已集成 | 内部 AI 引擎数据源（不对 C 端暴露） | 不变 |
| **API-Football** | 合同已签，`ApiFootballClient` 已集成 | 比赛数据、球队名称匹配 | 不变 |
| **结果源（主/备）** | 待确认 | Prediction 赛果确认 | 新增 |
| **通知渠道** | SwiftMailer/Smsbao/Telegram | 需统一 Outbox 框架 | 改造 |
| **汇率源** | 待确认 | APT 参考估值 | 新增 |
| **KYC 证据服务** | 待确认 | 身份验证 | 扩展 |
| **区块链节点** | BSC/ETH/TRON RPC 已配置 | 链上转账、USDT 合约交互 | 不变 |

## 9. API 约定

- 风格：REST/JSON，OpenAPI 3.1
- 路由前缀：`/v1/api/`（C 端），`/v1/`（Admin）
- 请求头：Token / Sign / Timestamp / Version / Language / TraceId
- 签名：MD5(sorted(header key=value pairs) & Key=projectApi).toUpperCase() — V2.0 密钥外置
- 时间：UTC timestamp
- 金额：string decimal
- 分页：cursor-based
- 写入幂等：Idempotency-Key（所有写操作）
- 并发控制：If-Match / object_version

## 10. 错误分类

| 错误类型 | HTTP | V1→V2 变更 |
|---|---|---|
| VALIDATION_ERROR | 400 | 不变 |
| AUTH_UNAUTHENTICATED | 401 | 不变 |
| AUTH_FORBIDDEN | 403 | 不变 |
| KYC_REQUIRED | 403 | 不变 |
| POLICY_DENIED | 403 | 不变 |
| FEATURE_CLOSED | 403 | 不变 |
| IDEMPOTENCY_CONFLICT | 409 | 不变 |
| OBJECT_VERSION_CONFLICT | 409 | 不变 |
| QUOTE_EXPIRED | 409 | 新增（OTC 报价过期） |
| INSUFFICIENT_APT | 422 | 不变 |
| INSUFFICIENT_POWER | 422 | 不变 |
| MARKET_LOCKED | 422 | 不变 |
| RESULT_UNKNOWN | 202 | 不变 |

## 11. 禁止事项

1. 前端不得自行判断资格（必须读 allowed_actions/entitlement）
2. 前端不得用 JS float 做资产计算
3. 后端不得覆盖或删除历史账本记录（修正用 reversal 追加）
4. 参数不得绕过 Approval 直接修改生效
5. 业务状态不得因通知失败回滚
6. 同一申请人不得审批自己的申请
7. 用户端不得出现 APR/APY/固定收益/保本/博彩化词汇
8. TBC 生产参数不得用本地默认值补齐
9. 不得删除 sys_route 表中的数据库路由记录
10. 授权不得简化为纯 RBAC：`FINAL_AUTHORIZATION = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD`
11. 不得在生产环境启用 file_monitor 进程
12. **（V1.x 遗留安全）** 不得在 V2.0 代码中硬编码 API 签名密钥、AES 加密密钥、S3 凭证
13. **（数据迁移）** 不得在迁移方案冻结前直接修改 V1.x 生产数据

## 信息来源

- `0.5代码/gainode后端/gainode/composer.json`（技术栈依赖）
- `0.5代码/gainode后端/gainode/config/process.php`（进程拓扑）
- `0.5代码/gainode后端/gainode/config/database.php`（数据库连接）
- `0.5代码/gainode后端/gainode/config/arbitrage.php`（套利引擎配置）
- `0.5代码/gainode后端/gainode/sql/database.sql`（数据表结构）
- `_existing_prod/gainode_h5/src/`（V1.x H5 架构分析）
- `_existing_prod/gainode_admin/src/`（V1.x Admin 架构分析）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`
- `0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/`（V2.4.1 治理包）
- `0.5代码/admin-proto/`（Admin 交互原型）

## 待确认事项

- [ ] 结果源（Prediction 赛果确认主/备服务商）
- [ ] 通知渠道 PUSH/SMS 服务商
- [ ] 汇率源、KYC 证据服务商
- [ ] API Gateway 实现方案
- [ ] 区块链链确认（Tron/BSC/Ethereum 全保留？）
- [x] H5 组件库：Vant 4（OWNER_DIRECTIVE 2026-08-12）
- [x] Admin 组件库：Element Plus（OWNER_DIRECTIVE 2026-08-12）
- [x] 数据迁移策略：一刀切 Big Bang（OWNER_DIRECTIVE 2026-08-12）
- [ ] H5 Vant 4 集成方案（按需引入、CSS Variables 主题映射）
- [ ] Admin Element Plus 迁移方案（Schema 组件 `<lay-*>` → `<el-*>` 改造、#009688 色彩映射）
- [ ] 迁移沙盒演练计划（至少 3 次全量迁移 + 回滚验证）
- [ ] V1.x 生产数据库 Schema 完整提取与 V2.0 DDL 审计
- [ ] 开发启动时间
