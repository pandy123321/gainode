# Gainode Project Bootstrap V1.0

> 基于 `E:\github\sports\通过agent开发前规则\PROJECT_BOOTSTRAP_TEMPLATE.md` 填写。
> 填写依据：现有生产代码（gainode_h5 / gainode_admin）、后端源码（0.5代码/gainode后端/gainode）、V6.1 规格文档。

---

# 1. Project Identity

```text
PROJECT_NAME = Gainode
PROJECT_ID = GAINODE-2.0
PROJECT_DESCRIPTION = AI 驱动的体育分析与竞猜平台。V2.0 基于 V1.x 上线版本（gainode_h5 / gainode_admin）升级，后端基于现有 PHP/Webman 代码扩展。
REPOSITORY = E:\github\sports
BASE_BRANCH = master
PROJECT_ROOT = E:\github\sports
CURRENT_BASELINE_COMMIT = 0188276dddd121849becca98c19da2a93325b0d8
CURRENT_FREEZE_VERSION = V6.1
```

---

# 2. Owners

> OWNER_DIRECTIVE 2026-08-12：本项目为单人开发，所有 Owner 角色由同一人兼任。以下视为 Agent Signoff 时的对应责任人标识。

```text
PROJECT_OWNER = OWNER (TBC — 填入正式姓名)
PRODUCT_OWNER = OWNER
ARCHITECTURE_OWNER = OWNER
DATABASE_OR_DATA_OWNER = OWNER
API_OWNER = OWNER
EVENT_STATE_OWNER = OWNER
ENVIRONMENT_OWNER = OWNER
SECURITY_OWNER = OWNER
RBAC_OR_SIGNER_OWNER = N/A（V2.0 无链上资产，APT 纯中心化账本）
DEPENDENCY_OWNER = OWNER（每次引入新库需记录 SBOM）
LEGAL_OR_OPEN_SOURCE_OWNER = OWNER
```

Signoff 格式：每项需 Date / Files / Scope / Risk / Exception / Change Process。

---

# 3. Agent / Review System

```text
EXECUTION_AGENT = Cursor Agent (Claude-based) / Can be replaced
INDEPENDENT_REVIEW_AGENT = ChatGPT (via External Review Bridge)
EXTERNAL_REVIEW_TOOL = cursor-app-control MCP "AI Code Review" bridge
REVIEW_BRIDGE_REQUIRED = YES
EXECUTOR_REVIEWER_SEPARATION = MANDATORY
```

---

# 4. Current Lifecycle State

```text
CURRENT_PHASE = PRE_DEVELOPMENT
CURRENT_STAGE = STAGE-01_BACKEND_DOMAIN_OBJECTS
STAGE_STATUS = IN_PROGRESS
FROZEN_FOR_DEVELOPMENT = YES
DEVELOPMENT_START = NO
DEPLOYMENT_APPROVAL = NO
PRODUCTION = NO-GO
```

---

# 5. Project Risk / Special Boundaries

```text
HIGH_RISK_SYSTEM = YES
FUNDS_OR_FINANCIAL_LOGIC = YES
BLOCKCHAIN_OR_SIGNER = NO（V2.0 纯中心化账本，Web3 能力已移除。OWNER_DIRECTIVE 2026-08-12）
PII_OR_SENSITIVE_DATA = YES
PRODUCTION_DATABASE = YES（V1.x 生产数据库存在，V2.0 需迁移策略）
EXTERNAL_REGULATED_SYSTEM = YES（体育竞猜涉及博彩合规，需多法域法律审查）
```

特殊禁止事项：

```text
FORBIDDEN_ACTIONS =
- 前端不得自行判断资格（必须读 allowed_actions/entitlement）
- 前端不得用 JS float 做资产计算
- 后端不得覆盖或删除历史账本记录（修正用 reversal 追加）
- 参数不得绕过 Approval 直接修改生效
- 业务状态不得因通知失败回滚
- 同一申请人不得审批自己的申请
- 用户端不得出现 APR/APY/固定收益/保本/博彩化词汇
- TBC 生产参数不得用本地默认值补齐
- 不得删除 sys_route 表中的数据库路由记录
- FINAL_AUTHORIZATION = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD
- 不得在生产环境启用 file_monitor 进程
- V1.x 生产数据不得在迁移方案冻结前直接修改
- V1.x API 签名密钥（projectApi）不得明文出现在 V2.0 代码中
```

---

# 6. Authoritative Evidence Order

按项目填写：

```text
L1 运行时不可变事实 / 已部署代码 / 数据库结构 / 生产运行证据
L2 固定 Commit、源码、构建产物、数据库对象、机器规范
L3 Owner Decision / Independent Review / Runtime Evidence
L4 Product / Architecture / Design / Freeze 文档
    L4a. V6.1 冻结规格文档（Gainode_Development_Ready_V6.1_Latest/）— 需求权威源
    L4b. V2.4.1 Admin 治理包（0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/）— Admin 契约权威源
    L4c. Admin 交互原型（0.5代码/admin-proto/）— Admin HIFI 交互权威源
    L4d. HIFI 设计系统文档（0.5代码/Gainode_Mobile_H5_Design_System_*.md）
L5 Self Review / Historical Report（.project-ai/v1-baseline-review.md）
L6 Agent Summary / Conversation / Oral Description（历史文档/仅追溯）
```

默认不得低层证据覆盖高层事实。

| 层级 | 示例（Gainode 项目） |
|---|---|
| L1 | V1.x 生产数据库实际 Schema、生产 API 实际响应格式、链上部署记录 |
| L2 | composer.json, package.json, vite.config.ts, database.sql, process.php |
| L3 | manifest.yaml decisionSources 中 provenance=OWNER_DIRECTIVE 的项、Independent Review 结论 |
| L4a-d | V6.1 01-08 规格、Admin Contract Register/Page Map、admin-proto/ HTML、H5 设计 MD |
| L5 | v1-baseline-review.md、历史自检记录 |
| L6 | 聊天确认的推断（context.md "基于代码的推断"）、历史文档 |

---

# 7. Existing System / Migration

```text
EXISTING_SYSTEM = YES
MIGRATION_STRATEGY = INCREMENTAL_UPGRADE：后端在现有 PHP/Webman 代码上扩展新模块；前端 H5 和 Admin 基于 V1.x 生产代码重构升级为 Vue 3 + TypeScript + 新组件库
OLD_SYSTEM_ROLE = V1.x 生产代码作为 V2.0 的基线起点。两个前端仓库（gainode_h5, gainode_admin）和现有后端（gainode后端/gainode）共同构成 V2.0 升级基础
NEW_SYSTEM_ROLE = V2.0 以 V6.1 规格为需求基准，在 V1.x 代码上增量扩展 10 个后端模块 + 58 Admin 页面 + 44 P0 H5 页面 + Flutter App
DATA_MIGRATION_REQUIRED = YES（V1.x 用户数据、钱包数据、团队数据需迁移至 V2.0 新账本结构）
DATA_REBUILD_REQUIRED = PARTIAL（arbitrage 引擎数据表改造为 AI 经济引擎结构；APT 账本从 V1.x wallet 系统迁移至四账分离模型）
DATA_CUTOVER = BIG_BANG — 一刀切全量迁移，~4 小时计划停机，至少 3 次沙盒全量排练 + 回滚验证
DUAL_WRITE_ALLOWED = NO — V1.x wallet 平表 → V2.0 四账 append-only 数据结构根本性不兼容；V1.x Mining 算力/周期 → V2.0 Robot 56级AI代理语义不兼容，双写在技术上不可行
CODEBASE_UPGRADE = INCREMENTAL — 代码层在后端现有 PHP/Webman 上扩展新模块，前端 H5/Admin 基于 V1.x 代码重构升级
```

### V1.x 现有系统清单

| 系统 | 仓库 | 技术栈 | 状态 |
|---|---|---|---|
| **后端 API** | `0.5代码/gainode后端/gainode` | PHP 8.2 + Webman + MySQL 8.4 + Redis | 现有运行中 |
| **Mobile H5** | `_existing_prod/gainode_h5` | Vue 3.5 + TS 6.0 + Vite 8 + 自定义 store + 自定义 i18n | 线上运行 |
| **Admin Web** | `_existing_prod/gainode_admin` | Vue 3.3 + TS 4.5 + Vite 4 + Pinia 2 + Layui Vue + Schema 驱动 | 线上运行 |

### V1.x → V2.0 关键技术差异

| 维度 | V1.x (现有生产) | V2.0 (目标) |
|---|---|---|
| **H5 组件库** | 手写所有 UI | Vant 4（移动端优先，70+ 组件） |
| **H5 i18n** | 自定义轻量引擎 (~260 keys) | vue-i18n（7 语言） |
| **H5 状态管理** | 自定义 reactive 对象 + localStorage | Pinia 2 + pinia-plugin-persistedstate |
| **H5 API 签名** | 手写 MD5 (200+ 行 JS) | crypto-js / Web Crypto API（密钥外置 .env） |
| **H5 Vite** | 8.1 | 5.x 稳定版 |
| **H5 TS** | 6.0 | 5.x LTS |
| **Admin 组件库** | Layui Vue 2.23 + JSON Schema Form | Element Plus（迁自 Layui Vue，Schema 驱动改为 `<el-*>` 渲染） |
| **Admin 路由** | Hash History | Hash History（保持不变，SEO 非管理端需求） |
| **Admin Vite** | 4.3 | 需升级至 5.x/6.x |
| **Admin TS** | 4.5 | 需升级至 5.x+ |
| **Admin Schema 驱动** | 后端下发 schema 控制表格/表单 | 保留并扩展此架构模式 |
| **Admin 语言** | 3 种（zh_CN, en_US, ko） | 扩展至 7 种（V6.1 要求） |
| **前端 .env** | 均无 | 需要环境变量管理 |
| **前端测试** | 均无 | 需要 Vitest / Playwright |
| **后端** | 现有 arbitrage 引擎 | 改造为 AI 经济引擎 + 新增 V6.1 模块 |

---

# 8. Product Freeze

```text
PRODUCT_BASELINE_FILE = Gainode_Development_Ready_V6.1_Latest/
APPROVED_CAPABILITIES = Robot (56级AI代理), Prediction (P0 Football 1X2), APT (四账分离+1000亿上限), Power (消耗/恢复), OTC (用户间撮合), Affiliate/Agent, AI运营 (策略模拟+建议管线), Approval Engine, Parameter Center, Audit/Support
OUT_OF_SCOPE = Prediction P2+ 赛事类型, APT-C 链上形态, 第三方支付网关直连（除 Crypto 外）, 开源
ROADMAP_NOT_APPROVED = V6.2 及其后功能
BUSINESS_RULES = 见 V6.1 05_DATA_STATE_PERMISSION_API_CONTRACT.md
UNRESOLVED_BUSINESS_DECISIONS = 结果源服务商, 通知渠道服务商, 汇率源, KYC 证据服务商, 生产参数具体数值
```

---

# 9. Architecture Freeze

```text
ARCHITECTURE_FILE = .project-ai/architecture.md
MODULES = Mobile/H5 (Vue3), App (Flutter), Admin Web (Vue3), Auth (JWT+Casbin), KYC, AI Economic Engine (原 arbitrage), Robot & Reward, Prediction, APT & Power & OTC, Affiliate/Agent, AI Operations, Parameter Center, Approval Engine, Audit, Notification, Support
PROCESSES = Webman\App (8787), task_server (8786), crontab_task, arb_task (改造), channel_server (2206), pusher_server (8888), proxy_server (8989), task
DATABASES = MySQL 8.4.9 (webman + gainode), Redis (3 instances: default/cache/stack)
QUEUES = Redis Queue (webman/redis-queue)
EXTERNAL_SERVICES = BetBurger (已签署), API-Football (已签署), 结果源 (待确认), 通知渠道 (待确认), 汇率源 (待确认), KYC 服务 (待确认)。链上服务 (BSC/ETH/TRON RPC) — 已移除，V2.0 不保留
AUTHORITATIVE_WRITERS = 每个数据实体有且仅有一个 Service 作为 Authoritative Writer
TRUST_BOUNDARIES = Client→API Gateway→Webman→Service→DAO→DB; External API→ArbitrageTask→Service; Admin→独立认证域（Admin URL 与 C 端 URL 分离）
```

---

# 10. Database Freeze

```text
DATABASE_ENGINE = MySQL
DATABASE_VERSION = 8.4.9
DATABASE_NAME = webman（默认）, gainode（业务）
SCHEMA = 0.5代码/gainode后端/gainode/sql/database.sql（60+ 张表，V1.x 基线）
MIGRATION_PATH = sql/YYYYMMDD_description.sql（两阶段：先 SQL 文件，DDL 变更超10次后迁移至 Phinx）
RUNTIME_ROLES = APP_RUNTIME (SELECT/INSERT/UPDATE), APP_MIGRATION (DDL), APP_READONLY (SELECT)
ROLE_LOGIN_MODEL = 应用层账户（non-root），权限最小化
```

必须明确：

```text
DDL_OWNER = TBC（需确认谁有权执行生产 DDL）
RUNTIME_WRITERS = 各模块 Service 层（通过 DAO→Model→PDO）
READONLY_ROLES = 报表/审计查询使用只读连接
IMMUTABLE_EVIDENCE = APT 账本（append-only，修正用 reversal entry），operational_logs, financial_audit_trail
```

---

# 11. API Freeze

```text
API_SPEC = OpenAPI 3.1（手动维护 + sys_route 表扫描校验）
PUBLIC_BASE_PATH = /v1/api/（C 端）
ADMIN_BASE_PATH = /v1/（管理后台）
AUTH_MODEL = JWT（firebase/php-jwt），Token 15天，Refresh 60天，Google 2FA备用
IDEMPOTENCY_MODEL = Idempotency-Key header（所有写操作）
ERROR_MODEL = JSON { code, message, data }，标准错误类型：VALIDATION_ERROR(400), AUTH_UNAUTHENTICATED(401), AUTH_FORBIDDEN(403), KYC_REQUIRED(403), POLICY_DENIED(403), FEATURE_CLOSED(403), IDEMPOTENCY_CONFLICT(409), OBJECT_VERSION_CONFLICT(409), QUOTE_EXPIRED(409), INSUFFICIENT_APT(422), INSUFFICIENT_POWER(422), MARKET_LOCKED(422), RESULT_UNKNOWN(202)
GENERATED_TYPES = TBC（OpenAPI → TypeScript types / PHP DTO 自动生成方案待定）
```

### API 签名约定（继承自 V1.x）

每个请求包含 Headers：
```
Timestamp, Version=1.0, Language, TraceId (UUID v4), Token (JWT), Sign (MD5)
```

Sign 算法：`MD5(sorted(header key=value pairs) & Key=projectApi).toUpperCase()`

**注意**：V2.0 需将 `projectApi` 签名密钥从代码中移除，改为环境变量或 Secret Manager 注入。

---

# 12. Event / State Freeze

```text
EVENT_CATALOG = TBC（需定义完整的 Event 字典：UserRegistered, KYCSubmitted, KYCApproved, RobotStarted, RobotStopped, RewardClaimed, PredictionPlaced, PredictionSettled, APTTransferred, OTC_Matched 等）
STATE_MACHINE_SPEC = 见 V6.1 05_DATA_STATE_PERMISSION_API_CONTRACT.md（KYC 多级状态机、Robot 状态机、Prediction 状态机、OTC 状态机、Approval 工作流状态机）
CONFIRMATION_POLICY = TBC（事件确认策略：at-least-once / exactly-once）
RETRY_POLICY = TBC（事件重试策略：指数退避、最大重试次数、死信队列）
CANCELLATION_POLICY = TBC（事件取消策略：超时取消、人工取消、关联事件级联）
REORG_OR_ROLLBACK_POLICY = TBC（区块链 reorg 策略、业务回滚策略）
```

---

# 13. Environment Freeze

```text
LOCAL = Docker Compose（php:8.2-cli + MySQL + Redis），开发者本地
DEVELOPMENT = TBC（共享开发环境，用于前后端联调）
TEST = TBC（自动化测试环境，CI 触发）
STAGING = TBC（预发布环境，生产数据脱敏镜像）
PRODUCTION = V1.x 生产环境（当前运行中），V2.0 部署后分阶段切换
```

Secret 来源：

```text
SECRET_SOURCE = .env 文件（本地）+ CI/CD Secret Manager（CI/CD 环境）+ 生产密钥管理服务（生产环境）
SECRET_LOGGING = FORBIDDEN
```

**V1.x 已知 Secret 风险**：
- `gainode_h5/src/utils/s3Upload.ts` 硬编码 AWS Access Key + Secret
- `gainode_admin` 未使用 .env，baseURL 和签名密钥硬编码
- V2.0 必须全部迁移至环境变量

---

# 14. Dependency Governance

```text
DEPENDENCY_RULE_FILE = E:\github\sports\通过agent开发前规则\开源项目通用引用准入规则V1.0.md
NO_DOWNLOAD_AUTHORIZED = NO（开发期允许，生产前需全部批准）
APPROVED_DEPENDENCIES = PHP: webman/workerman, illuminate/database, firebase/php-jwt, casbin/casbin, monolog/monolog, workerman/redis-queue, workerman/crontab; H5: Vue 3, TypeScript, Vite, Pinia, vue-i18n, Vant 4; Admin: Vue 3, TypeScript, Vite, Pinia, vue-i18n, Element Plus; App: Flutter
PENDING_DEPENDENCIES = H5/Admin/App exact version lock + SBOM, 测试框架（Vitest/Playwright）, Dart/Flutter 依赖包, 前端 ESLint/Prettier/Stylelint 配置
```

每个批准依赖必须有 exact version/tag/commit。

---

# 15. Required Pre-Development Documents

勾选：

```text
[x] README / INDEX (.project-ai/context.md ≈ README+INDEX)
[x] Existing Baseline Review (V1.x 生产代码分析 — 本次 task 完成)
[x] Architecture Freeze (.project-ai/architecture.md — V1.x→V2.0 migration architecture documented)
[ ] Database/Data Freeze — 第一批（STAGE-01 前）：从 05 提取 8 核心实体 DDL；第二批：非核心实体
[ ] API Freeze (需补充 OpenAPI 3.1 契约文件 + Admin Schema 契约 — 推迟至 STAGE-02，STAGE-01 不定义 API)
[ ] Event/State Freeze — 第一批（STAGE-01 前）：05 §4 7 个实体的 canonical enum 已对齐到 TASK-20260811；第二批：Event Catalog 异步补
[x] Business Rules (V6.1 05_DATA_STATE_PERMISSION_API_CONTRACT.md)
[ ] Environment Freeze — STAGE-01 并行配置 dev container + .env template
[x] Framework/Dependencies (H5: Vant 4, Admin: Element Plus; exact versions pending)
[x] Rules/Decision Register (.project-ai/manifest.yaml decisionSources)
[x] Self Review（STAGE-00 Self Review 已完成 2026-08-12，详见 stg00-self-review.md）
[x] Independent Review（GAINODE-STAGE00-IR-20260812-001 已提交 → CHANGES_REQUIRED → 本轮 Remediation 关闭 13/15 Finding → GAINODE-STAGE00-IR-20260812-002 CONDITIONAL_APPROVAL（1 residual P3 documented））
[x] Responsible Owner Freeze (bootstrap.md Section 2 — OWNER_DIRECTIVE 2026-08-12：单人兼任 11 角色，Signer=N/A)
[x] Runtime Gate Plan (bootstrap.md Section 20)
[x] Stage Plan (STAGE-00_PLANNING_AND_FREEZE 已定义，详见下文 Section 16)
```

Machine Contract 分两批策略（OWNER_DIRECTIVE 2026-08-12）：

| 批次 | Contract | 时机 | 预计耗时 |
|---|---|---|---|
| **第一批** | DB Freeze（8 核心实体 DDL）+ Canonical State Freeze | STAGE-01 开始前 | 1.5 天 |
| **第二批** | OpenAPI 3.1 + Admin Schema Contract + Event Catalog + Environment Freeze | STAGE-01 ~ STAGE-02 并行 | 3 天 |

---

# 16. Stage Plan

## STAGE-00: Planning & Freeze

```text
STAGE_ID = STAGE-00
STAGE_NAME = Planning & Document Freeze
GOAL = 完成 V1.x 基线审查，冻结所有开发前文档，通过 Independent Review，获得 Owner Signoff

INPUTS = V6.1 规格, V1.x 生产代码 (gainode_h5, gainode_admin, gainode后端), V2.4.1 Admin 治理包, Admin 交互原型, HIFI 设计系统, PROJECT_BOOTSTRAP_TEMPLATE, MASTER_PROJECT_GOVERNANCE

ALLOWED_PATHS =
- .project-ai/**
- 0.5代码/（只读）
- _existing_prod/（只读，V1.x 生产代码镜像）
- 通过agent开发前规则/（只读，治理规则源）

FORBIDDEN_PATHS =
- 任何业务代码创建/修改
- 数据库 DDL/DML 执行
- API 端点创建
- 前端页面/组件创建
- git push / deploy

NON_GOALS =
- 不执行任何业务代码开发
- 不创建数据库对象
- 不部署任何服务

DEPENDENCIES = V1.x 代码镜像已克隆（✅）, V6.1 规格已就绪（✅）, V2.4.1 Admin 治理包已就绪（✅）, Admin 原型已冻结 57/58 页（✅）, HIFI 设计系统已就绪（✅）

IMPLEMENTATION_REQUIREMENTS =
- 填写 PROJECT_BOOTSTRAP_TEMPLATE 全部字段（bootstrap.md）
- 补充 V1.x→V2.0 迁移架构（architecture.md）
- 补充 V1.x 生产代码基线分析（context.md）
- 创建缺失的预开发文档（DB Freeze, API Freeze, Event/State Freeze, Environment Freeze, Dependencies）
- 更新 manifest.yaml（新增 V1.x baseline 引用）
- 生成 Self Review 包

VALIDATION_REQUIREMENTS =
- 所有 "TBC" 字段已确认或记录为待确认
- 所有 Forbidden Actions 已列出
- 所有决策来源可追溯
- AUTHORITATIVE_WRITERS 已分配
- TRUST_BOUNDARIES 已定义

REVIEW_REQUIREMENTS =
- Independent Review Required
- Review Package Required
- Manifest Required
- Package SHA256 Required

EXIT_CRITERIA =
- bootstrap.md 所有字段填写完毕（TBC 项记录为待确认）
- 所有预开发文档创建/更新完毕
- manifest.yaml contextVersion ≥ 13（绑定当前 Freeze）
- Self Review 完成
- Independent Review 提交
- Review 结果 APPROVED
- Owner Signoff 完成
- FROZEN_FOR_DEVELOPMENT = YES

NEXT_STAGE = STAGE-01_BACKEND_DOMAIN_OBJECTS
```

## STAGE-01: Backend Domain Objects

```text
STAGE_ID = STAGE-01
STAGE_NAME = Backend Domain Object Skeletons
GOAL = 在现有 PHP/Webman 代码上为 V6.1 全部 10 个模块建立 Model/DAO/Service 骨架

INPUTS = V6.1 05_DATA_STATE_PERMISSION_API_CONTRACT.md, V1.x 后端代码结构, STAGE-00 冻结文档

ALLOWED_PATHS =
- 0.5代码/gainode后端/gainode/library/model/
- 0.5代码/gainode后端/gainode/library/dao/
- 0.5代码/gainode后端/gainode/library/service/
- 0.5代码/gainode后端/gainode/sql/（增量 migration 文件）
- 0.5代码/gainode后端/gainode/config/

FORBIDDEN_PATHS =
- 前端代码（H5/Admin/App）
- V1.x 已有模块的内部逻辑修改（仅新增，不重构）

NON_GOALS = 不实现业务逻辑细节，不集成外部 API，不写前端页面

DEPENDENCIES = STAGE-00 完成并通过 Review

IMPLEMENTATION_REQUIREMENTS =
- 按模块顺序：Auth/KYC → User/Eligibility → Robot/Reward → APT Ledger → Prediction → OTC/Power → Affiliate/Agent → AI Operations → Approval/Parameter → Support/Audit
- 每个模块：定义 Model（表映射 + 关联）、DAO（查询封装）、Service（接口骨架 + 事务边界）
- 所有 Service 继承 support\extend\Service
- 每个 Service 声明 Authoritative Writer
- 增量 DDL 文件（sql/YYYYMMDD_description.sql）
- 每个模块创建后更新 .project-ai/tasks/ 状态

VALIDATION_REQUIREMENTS =
- php -l 语法检查通过
- 所有 Model 有 TABLE 常量
- 所有 DAO 有 docblock 类型提示
- 所有 Service 有 @authoritative_writer 注解

REVIEW_REQUIREMENTS =
- Independent Review Required
- Review Package Required
- Manifest Required
- Package SHA256 Required

EXIT_CRITERIA = 10 个模块骨架建完并通过 Review

NEXT_STAGE = STAGE-02_FRONTEND_INTEGRATION
```

后续阶段（骨架，待正式冻结）：
- `STAGE-02`: Frontend Integration（H5/Admin 基于 V1.x 代码升级，逐个接入后端 API）
- `STAGE-03`: App Development（Flutter 独立开发）
- `STAGE-04`: Sandbox E2E（沙盒端到端测试）
- `STAGE-05`: Staging & Production Deployment

---

# 17. Review Package Policy

```text
PAYLOAD_MANIFEST = AUTHORITATIVE
PACKAGE_SHA256 = REQUIRED
SECRET_SCAN = REQUIRED
REVIEW_BINDING = REQUIRED
OLD_PACKAGE_REUSE = FORBIDDEN
```

**V1.x Secret 扫描要点**：
- `gainode_h5/src/utils/s3Upload.ts` 中的 AWS 密钥
- `gainode_admin/src/api/http.ts` 中的 `SIGN_PRIVATE_KEY = 'projectApi'`
- 任何 .env 文件中的密码/密钥

---

# 18. Finding Policy

```text
P0_BLOCKS_STAGE = YES
P1_BLOCKS_STAGE = YES
P2_REQUIRES_DISPOSITION = YES
P3_INFORMATIONAL_BY_DEFAULT = YES
```

执行者二次判定：

```text
CORRECT_ACTIONABLE
PARTIALLY_CORRECT_LIMITED_ACTION
INCORRECT_DO_NOT_EXECUTE
UNVERIFIABLE_PAUSE
OUT_OF_SCOPE_HUMAN_CONFIRMATION_REQUIRED
```

---

# 19. Owner Freeze

```text
OWNER_FREEZE_REQUIRED = YES
OWNER_FREEZE_FILE = .project-ai/bootstrap.md（Section 2）
OWNER_FREEZE_STATUS = COMPLETE # 11 roles all assigned to OWNER (single-owner project, verified by IR-002)
```

每项 Signoff 必须记录 Owner / Date / Scope / Risk / Exception / Change Process。

---

# 20. Runtime Gates

按适用性：

```text
DATABASE_MIGRATION_GATE = REQUIRED（每次 DDL 变更需独立审批）
ROLE_RUNTIME_GATE = REQUIRED（新角色/权限必须审批）
BUILD_GATE = REQUIRED（前端 Build 必须通过）
TEST_GATE = REQUIRED（PHPUnit + Vitest + Playwright 必须通过）
EXTERNAL_READBACK_GATE = REQUIRED（生产部署后需外部监控确认）
SECURITY_GATE = REQUIRED（Secret 扫描 + 依赖漏洞扫描）
DEPLOYMENT_GATE = REQUIRED（生产部署必须独立授权）
```

---

# 21. High-Risk Authorization

默认单独授权：

```text
PRODUCTION_MIGRATION = FORBIDDEN_UNTIL_APPROVED
PRODUCTION_DEPLOYMENT = FORBIDDEN_UNTIL_APPROVED
PRIVATE_KEY_ACCESS = FORBIDDEN_UNTIL_APPROVED（区块链私钥访问）
SIGNER_ACTIVATION = FORBIDDEN_UNTIL_APPROVED（交易签名激活）
ONCHAIN_BROADCAST = FORBIDDEN_UNTIL_APPROVED（链上广播）
CONTRACT_REDEPLOYMENT = FORBIDDEN_UNTIL_APPROVED（智能合约重部署）
```

---

# 22. Auto-Advance Policy

```text
AUTO_ADVANCE_ENABLED = YES
AUTO_ADVANCE_ONLY_WITHIN_FROZEN_PLAN = YES
HUMAN_CONFIRMATION_FOR_NORMAL_STAGE_TRANSITION = NO
```

自动推进必须满足：

```text
FINAL_REVIEW_VERDICT = APPROVED
REVIEW_COMPLETENESS = COMPLETE
ADJUDICATION = ACCEPTED
P0_OPEN = 0
P1_OPEN = 0
BLOCKING_P2 = 0
ACCEPTANCE_CRITERIA = PASS
NEXT_STAGE_DEFINED = YES
```

---

# 23. Change Control

任何 Freeze 修改必须：

```text
CHANGE_REQUEST_ID = 唯一 ID（CR-YYYYMMDD-NNN）
REQUESTED_CHANGE = 变更描述
REASON = 变更原因
IMPACT = 影响范围评估
AFFECTED_FILES = 受影响文件清单
AFFECTED_STAGES = 受影响 Stage
OWNER_DECISION = APPROVED / REJECTED / DEFERRED
INDEPENDENT_REVIEW_REQUIRED = YES / NO
NEW_FREEZE_VERSION = 新冻结版本号
```

---

# 24. Initial Gate Status

```text
DESIGN_GATE = OPEN（Admin 57/58 页已冻结，H5 HIFI 设计系统已就绪，V6.1 规格已冻结）
OWNER_FREEZE_GATE = OPEN（待填入所有 Owner 字段）
DEPENDENCY_GATE = OPEN（前端组件库选型、测试框架选型待确认）
STATIC_REVIEW_GATE = OPEN（V1.x 代码审查待完成）
RUNTIME_GATE = CLOSED（无运行时环境待验证）
TEST_GATE = CLOSED（无测试待运行）
SECURITY_GATE = OPEN（V1.x Secret 扫描待完成，V2.0 安全架构待定义）
DEPLOYMENT_GATE = CLOSED（不允许部署）
RELEASE_GATE = CLOSED（不允许发布）
```

---

# 25. Project Start Contract

只有项目正式记录：

```text
DOCUMENT_BASELINE = FROZEN（待完成所有预开发文档 + Independent Review）
MACHINE_CONTRACTS = FROZEN（待完成 OpenAPI + DB DDL + State Machine 冻结）
INDEPENDENT_REVIEW = PASSED（待执行）
OWNER_SIGNOFF = COMPLETE（待执行）
REQUIRED_PRE_DEVELOPMENT_RUNTIME_GATES = PASSED_OR_FORMALLY_STAGED（待执行）
OPEN_P0 = 0（待确认）
OPEN_P1 = 0（待确认）
OPEN_P2 = DISPOSITIONED（待确认）
DEPENDENCIES = APPROVED（待前端组件库选型确认）
SECRETS_POLICY = FROZEN（待 V1.x Secret 清理后冻结）
DEPLOYMENT_POLICY = FROZEN（待定义后冻结）
STAGE_PLAN = FROZEN（STAGE-00 和 STAGE-01 已定义，后续待定）
EXTERNAL_REVIEW_PROTOCOL = READY（cursor-app-control MCP bridge 就绪）
```

后，才可以由有权责任人设置：

```text
FROZEN_FOR_DEVELOPMENT = YES
```

`DEVELOPMENT_START = YES` 应按项目计划另行授权。

---

## 信息来源

- `E:\github\sports\通过agent开发前规则\PROJECT_BOOTSTRAP_TEMPLATE.md`（模板源）
- `E:\github\sports\通过agent开发前规则\MASTER_PROJECT_GOVERNANCE.md`（治理母版）
- `E:\github\sports\_existing_prod\gainode_h5\`（V1.x H5 生产代码）
- `E:\github\sports\_existing_prod\gainode_admin\`（V1.x Admin 生产代码）
- `E:\github\sports\0.5代码\gainode后端\gainode\`（现有后端代码）
- `E:\github\sports\Gainode_Development_Ready_V6.1_Latest\`（V6.1 规格）
- `E:\github\sports\0.5代码\Gainode_Admin_Prototype_Planning_V2.4.1_CN\`（Admin 治理包）
- `E:\github\sports\0.5代码\admin-proto\`（Admin 交互原型）
- 用户聊天对话 2026-08-11/12（Owner Decisions）

## 待确认事项

- [ ] Section 2：所有 Owner 字段填入实际人员
- [ ] Section 1：CURRENT_BASELINE_COMMIT 确认
- [x] Section 7：DATA_CUTOVER = BIG_BANG，DUAL_WRITE_ALLOWED = NO（OWNER_DIRECTIVE 2026-08-12）
- [ ] Section 11：GENERATED_TYPES（OpenAPI → TypeScript/PHP DTO 自动生成方案）
- [ ] Section 12：Event Catalog 全部定义
- [ ] Section 12：所有状态机确认/恢复/取消策略
- [ ] Section 13：各环境详细地址和访问方式
- [ ] Section 14：前端组件库选型（H5 和 Admin 的 UI 组件库）
- [ ] Section 14：测试框架选型确认
- [ ] Section 15：6 个缺失的预开发文档创建计划
- [ ] Section 24：各 Gate 当前状态确认
- [ ] V1.x 生产数据库连接信息（用于 Schema 提取和迁移计划）
- [ ] V1.x 生产环境 URL（H5 / Admin / API 域名）
- [ ] 第三个 Git 仓库（用户列出两个 gainode_admin 重复，是否有第三个如 gainode_backend？）
