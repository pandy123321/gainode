# 项目上下文

## 已确认信息

### 项目目标

Gainode 是一个 AI 驱动的体育分析与竞猜平台，围绕以下 5 个用户问题组织：

1. **我现在能用什么？** — 登录、KYC、地区、风险和资格。
2. **我的 Robot 是什么状态？** — 等级、能力、启动/停止、升级、Reward/Claim。
3. **我能参与什么赛事？** — P0 足球赛前 1X2，状态、规则、锁定、结算、退款、更正。
4. **我的 APT 到底在哪里？** — 可用、冻结、待确认、流水、Power、OTC。
5. **出了问题怎么办？** — 通知、工单、申诉、后台复核和审计。

每个页面都应回答：**现在是什么状态、为什么、下一步能做什么**。

### V2.0 与 V1.x 的关系

**Gainode V2.0 基于 V1.x 线上运行版本升级开发**，不是从零新建。V1.x 三块代码构成 V2.0 的基线起点：

| 系统 | 仓库 | 线上状态 | V2.0 定位 |
|---|---|---|---|
| **Mobile H5** | `https://github.com/Xfd100/gainode_h5.git`（已克隆至 `_existing_prod/gainode_h5`） | 线上运行 | 重构升级为 Vue 3 + Pinia + 组件库 + 7 语言 |
| **Admin Web** | `https://github.com/Xfd100/gainode_admin.git`（已克隆至 `_existing_prod/gainode_admin`） | 线上运行 | 重构升级为 Vue 3 + 58 页 + 7 语言 + 审批工作流 |
| **后端 API** | `0.5代码/gainode后端/gainode`（已存在） | 线上运行 | 扩展 V6.1 的 10 个新模块，arbitrage 改造为 AI 经济引擎 |

### V1.x 生产代码关键发现

#### H5 (gainode_h5)
- **技术栈**：Vue 3.5 + TypeScript 6.0 + Vite 8，无 Pinia（自定义 reactive stores），无 vue-i18n（自定义 ~260 keys），无组件库（全部手写），无测试，无 .env
- **架构问题**：S3 密钥硬编码（已决策：V2 改用后端预签名 URL），`package.json` name 为 "quiz"（非 "gainode"），ClaimCenterView 含硬编码 mock 数据
- **优点**：Vue 3 Composition API 规范，统一暗色主题，良好 API 模块分层，完整的无线滚动模式，3 链钱包集成（MetaMask/TronLink/Phantom）
- **页面**：19 个视图 / 22 条路由，4 Tab（Home/Robot/Team/My）+ 17 独立页

#### Admin (gainode_admin)
- **技术栈**：Vue 3.3 + TypeScript 4.5 + Vite 4，Pinia 2（persistedstate），Layui Vue 2.23 组件库，Schema 驱动架构（后端下发表格列/表单字段定义），Hash History 路由，3 语言（zh_CN/en_US/ko），v-permission 指令，ECharts
- **架构问题**：无 .env 管理，签名密钥硬编码，密码 AES 加密密钥硬编码（`f080a463654b2279`），无测试，MockJS 未完全禁用
- **优点**：成熟的 Pinia 状态持久化，Schema 驱动架构（后端控制前端展示），完善的多 Tab + 面包屑 + 主题系统，良好的权限指令系统，丰富的布局（侧栏/子域模式/响应式）
- **页面**：46 条路由 / ~64 个 .vue 文件，覆盖用户/资产/配置/信号/矿机/团队/系统管理等

#### 后端 (gainode后端)
- **技术栈**：PHP 8.2 + Webman，MySQL 8.4.9（60+ 张表），3 实例 Redis，JWT + Casbin RBAC，DB 驱动路由（sys_route 表），BetBurger + API-Football 集成，Docker 化部署。**V2.0 不保留链上能力**（OWNER_DIRECTIVE 2026-08-12），APT 为纯中心化账本。
- **现有模块**：Auth（手机+邮箱登录）、Wallet（充提）、Team、Mining（矿机订单）、Signal/Arbitrage、RedEnvelope、Content、KYC、System（用户/角色/菜单/部门/字典）、Configuration

### 开发阶段

**STAGE-00：Planning & Document Freeze — 已完成。** Independent Review 第二轮（GAINODE-STAGE00-IR-20260812-002）结果 = **CONDITIONAL_APPROVAL**。15 项 Finding 全部闭合（0 P0 / 0 P1 / 0 blocking P2）。Owner Freeze 完成（11/11 角色已指派）。前置「Machine Contract 第一批（MC1）」已通过独立审核并经 Owner Signoff 正式置 **FROZEN**。**STAGE-01 = IN_PROGRESS**。

Admin 原型（`0.5代码/admin-proto/`）已完成交互验证阶段（8 一级导航、35 CONTRACT_FROZEN + 22 CONTRACT_GAP + 1 FUTURE、17 个交互式 Modal、全部中文 UI）。

- V1.x → V2.0 迁移策略：**增量升级**（非重写）。后端在现有代码上扩展新模块；前端 H5/Admin 基于 V1.x 代码重构升级。
- STAGE-01 Backend Domain Objects：已创建 task（TASK-20260812-002），10 模块 Model/DAO/Service 骨架搭建。
- Machine Contract 第一批（STAGE-01 前）：DB DDL（8 核心实体）+ Canonical State Freeze — **FROZEN（2026-08-13，Owner Signoff）**。独立审核 GAINODE-MC1-IR 三轮通过（记录 541/542/543，最终建议合并 0 P0/P1/P2）。8 核心实体 DDL 已落盘 `0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql`，Freeze 文档 `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`。
- **STAGE-01 第一批已落地（2026-08-14，commit `5fb3d01`）**：8 个 MC1 冻结核心实体的 Model/DAO/Service 骨架（24 文件）已创建，目录 `library/{model,dao,service}/{robot,ledger,prediction,otc,power}/`。状态枚举严格取自 MC1 Freeze；主键 Snowflake（`$incrementing=false` + `$keyType='string'`）；`apt_ledger_entries` append-only（`$timestamps=false` + `UPDATED_AT=null`）。状态转移矩阵未实现，一律 FAIL_CLOSED，待 Machine Contract 第二批（Event Catalog + Ledger Mutation Contract）。
- Ledger append-only 的 Model/Builder/DAO 防护与 CLI 回归已落地；已记录测试证据为 `67 pass / 0 fail`。这是已完成成果，除有效 Finding 外不得重写。
- **MC2 当前进度**：Owner 22 项 + 2 项财务裁决已完成；IR 686 的 1 P1 + 3 P2 修复提交到 `2795e38`，独立复审 Round 7（IR `GAINODE-S01P01-MC2-IR-20260816-001`）已返回 **APPROVED**（0 P0 / 0 P1 / 0 blocking P2；1 非阻塞 P3 = `aggregate_dispute_hold` 标注 DERIVED，后续顺带修正）。MC2 已置 **FROZEN（2026-08-16）**。
- **S01-P02（2B-1 状态合同补齐）已完成（2026-08-16，commit `2707938`）**：9 对象状态合同已产出（task `TASK-20260816-001`）。Result/Settlement 复制 05 §4 canonical enum + 转移矩阵（RS1-RS5 / ST1-ST7，候选，待 State Machine gate）；AuditEvent 复用 MC2 `audit_events` DDL；6 缺 enum 实体（SettlementBatch/RefundCase/CorrectionCase/OtcTrade/RobotUpgradeOrder/ConsentReceipt）仅生成 Owner Decision Matrix（2B1-ENUM-01..06），未自创状态，FAIL_CLOSED 不建表。Result `official` ≠ Settlement `paid`；Result confirmer ≠ Settlement approver。
- 当前执行包：`S01-P04-2B2-STATE-CONTRACT`。S01-P01 已 FROZEN，S01-P02 Round 2 APPROVED，S01-P03 Round 1 APPROVED，均禁止重做；S01-P04 已有实现提交 `884cdf9`，当前需按最新 Git/工作树生成干净 Snapshot 后提交 Quality。其后按 V3.2 冻结路线执行 S01-P05～S01-P09。
- 正式后续阶段：STAGE-02 OpenAPI/Environment/Backend Core；STAGE-03 H5+Admin；STAGE-04 Flutter；STAGE-05 Sandbox E2E；STAGE-06 Release Readiness。
- 详细且唯一的执行路线见 `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.2；状态为 `FROZEN_FOR_EXECUTION`，Freeze ID=`GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.2-20260816`。40 个工作包均具备目标、固定步骤、验证、停止条件和验收；Development、Quality 和复审 Agent 必须按该文件执行，V3.1 及更早路线均为 `SUPERSEDED_DO_NOT_EXECUTE`。改变路线必须先获得 Owner 明确批准并升级版本、Freeze ID 和冻结凭证。
- 开发采用一个 Development Agent 串行工作包；Quality 锁定快照后，如下一包路径不重叠且不消费未冻结合同，Developer 可继续，不把“继续开发”误写成“当前包审核/合并已通过”。
- Production Real-Value：NO-GO，需生产参数批准后开放。

### 技术栈（已确认）

| 层级 | 技术 | 版本/说明 |
|---|---|---|
| **后端语言** | PHP | ≥8.2（与 Dockerfile php:8.2-cli 一致） |
| **后端框架** | Webman (Workerman) | 事件驱动、非阻塞、常驻内存 |
| **数据库** | MySQL | 8.4.9，PDO via illuminate/database，两个库：`webman`（默认）+ `gainode` |
| **缓存** | Redis | 3 实例（default/cache/stack） |
| **队列** | Redis Queue | webman/redis-queue |
| **定时任务** | workerman/crontab | DB 驱动，任务定义在 `sys_crontab` 表 |
| **认证** | JWT (firebase/php-jwt) + Google 2FA | Token 有效期 15 天，Refresh 60 天 |
| **权限** | Casbin | RBAC + RESTful，策略存储在 `sys_casbin_rbac` / `sys_casbin_restful` 表 |
| **日志** | Monolog | 多通道：default/api/library/task/crontab/queue/change_logs |
| **校验** | Laravel Illuminate Validation | ~10.48 |
| **路由** | DB 驱动动态路由 | 路由存储在 `sys_route` 表，启动时从 DB 加载 |
| **容器化** | Docker + docker-compose | php:8.2-cli 基础镜像，6 个暴露端口 |
| **包管理** | Composer | PSR-4 autoloading |
| **外部 API** | BetBurger + API-Football | 体育数据源（合同均已签署） |
| **前端-H5** | Vue 3 + TypeScript | Mobile H5（4 Tab，375/390/430 三尺寸）。V1.x 基线：Vue 3.5 + TS 6.0 + Vite 8 |
| **前端-App** | Flutter | iOS / Android 原生 App（V2.0 全新） |
| **前端-Admin** | Vue 3 + TypeScript | 桌面管理后台（8 Root，58 Page ID）。V1.x 基线：Vue 3.3 + TS 4.5 + Vite 4 + Layui Vue + Pinia 2 |

### V1.x → V2.0 前端升级清单

| 升级项 | V1.x 现状 | V2.0 目标 | 优先级 |
|---|---|---|---|
| H5 状态管理 | 自定义 reactive + raw localStorage | Pinia 2 + persistedstate | P0 |
| H5 i18n | 自定义 ~260 keys | vue-i18n + 7 语言 | P0 |
| H5 组件库 | 全部手写 | Vant 4（移动端优先） | P0 |
| H5 组件库集成 | 无 | 按需引入 + CSS Variables 主题定制 | P1 |
| H5 环境变量 | 无 .env | .env + VITE_ 前缀 | P1 |
| H5 测试 | 无 | Vitest + Playwright | P1 |
| H5 API 签名 | 手写 MD5 (200+ 行) | 统一 SDK 或 crypto-js | P2 |
| Admin 版本升级 | Vite 4, TS 4.5 | Vite 5/6, TS 5+ | P0 |
| Admin 组件库 | Layui Vue 2.23 | Element Plus（迁移） | P1 |
| Admin 语言 | 3 种 | 扩展至 7 种 | P0 |
| Admin 环境变量 | 无 .env | .env | P1 |
| Admin 测试 | 无 | Vitest | P1 |
| Admin Schema 驱动 | 已有 | 保留 + 扩展至全部 58 页 | P0 |
| 两端密钥管理 | 硬编码 | 环境变量 / Secret Manager | P0 |
| 两端 S3 上传 | 硬编码 AWS 密钥 | 后端预签名 URL（V2：`POST /api/upload/presigned-url`） | P0 — **已决策 2026-08-12** |

### 后端代码结构与现有模块

后端源码位于 `0.5代码/gainode后端/gainode`：

```text
app/                # 控制器层（admin / api / command / queue）
library/            # 业务逻辑层（model/ dao/ service/ dict/ event/）
                    #   STAGE-01 已新增模块子目录：model|dao|service/{robot,ledger,prediction,otc,power}
support/            # 框架基础设施（arbitrage/ extend/ utils/ middleware/）
process/            # Workerman 长驻进程（ArbitrageTask、CrontabTask、ChannelServer、Pusher）
sql/database.sql    # V1.x 全量数据库结构（60+ 张表）
sql/YYYYMMDD_*.sql  # V2.0 增量 DDL（MC1 8 核心实体：20260813_machine_contract_batch1_8_core_entities.sql）
```

### 产品核心领域

- **Robot**：56 级 AI 代理，`standard_capacity × daily_reward_coefficient = pending APT`（动态 Reward，系数可为 0）。
- **Prediction**：P0 仅 Football Pre-match 1X2（Home/Draw/Away），90 分钟+伤停补时，不含加时/点球。中文用户端统一显示「竞猜」。
- **APT**：系统内部数量代币，总量上限 1000 亿。V2.0 纯中心化账本（不涉及链上发行），四账分离模型（数量×估值×收入×预算）
- **Power**：可消耗、可恢复操作资源，用于 OTC Sell、Withdrawal、Robot Start。容量由 Robot 等级决定。
- **OTC**：用户间受控撮合，非平台固定回购。
- **Notice**：通知体系，与业务事务解耦，通过 Outbox/异步投递。

### 四账分离

| 账 | 记录什么 |
|---|---|
| APT 数量账 | available/frozen/pending/held/payable/claimed/burned |
| APT 参考估值账 | quantity × reference price |
| 功能货币收入账 | 实际收到并有证据的 USDT/USDC/法币等 |
| Reward/预算账 | AI/Prediction 的预算、候选、负债、支付 |

四账不得静默互相补贴。

### 用户与 Admin 角色

游客 / 已登录未 KYC / 已准入用户 / 受限用户 / 客服 / 运营 / 风控/审核 / 财务/账本 / 参数/发布角色 / 审计。

Admin 13 个角色：`END_USER / SUPPORT_AGENT / OPS_OPERATOR / KYC_REVIEWER / RISK_ANALYST / RISK_APPROVER / LEDGER_OPERATOR / FINANCE_REVIEWER / PARAM_EDITOR / PARAM_APPROVER / RELEASE_OPERATOR / AUDITOR / ADMIN_SECURITY`。严格职责分离，SoD 为 Actor-level Invariant。

### Admin 合同状态

参照 V2.4.1 Contract Gap Register + Page Map JOIN（唯一权威源）：

| 状态 | 页数 | 说明 |
|---|---|---|
| CONTRACT_FROZEN | 35 | 05/06 契约已正式冻结，可进入 HIFI → 开发 |
| CONTRACT_GAP | 22 | 05/06 契约未冻结（含 BLOCKING + NON_BLOCKING） |
| FUTURE | 1 | A-MIGRATION-001（默认关闭） |

**Owner 已决策纳入 V6.1 产品范围的 16 页**（Affiliate 4+7、AI 运营、Data Provider 等）目前仍为 CONTRACT_GAP，需在 05/06 Contract Freeze 完成后才能进入开发，不得将"产品范围确认"等同于"机器合同冻结"。

| 决策 | Owner 状态 | Machine Contract | 可开发 |
|---|---|---|---|
| Affiliate/Agent 4+7 页 | IN_SCOPE | CONTRACT_GAP (GAP-001/002) | **否** |
| AI 策略模拟 + 建议管线 | IN_SCOPE | CONTRACT_GAP (GAP-010/011/012) | **否** |
| Data Provider (API-Football/BetBurger 已签) | CONTRACT_SIGNED | CONTRACT_GAP (GAP-003-009 等) | **否** |
| Asset Adjustment (仅 ADMIN_SECURITY) | POLICY_CONFIRMED | CONTRACT_GAP (GAP-015) | **否** |

### 开发策略（已确认）

**前后端分离，后端领域对象全量建好后前端逐个接入。V2.0 在 V1.x 代码基础上升级，非重写。**

1. 后端先行：Auth/KYC → User/Eligibility → Robot/Reward → APT Ledger → Prediction → OTC/Power → Affiliate/Agent → AI 运营 → Approval/Parameter → Support/Audit，共 10 个模块
2. 前端跟进：核心主流程 → 资产线 → 竞猜线 → 安全线
3. 接口对接：通过 OpenAPI 3.1 契约文件对齐

**V1.x 现有模块的处理原则**：
- 保留：Auth（扩展 MFA）、Wallet（改造为四账分离）、Team（保留并扩展 Affiliate）、KYC（扩展多级状态机）、System/RBAC（保留并扩展 Casbin 策略）
- 改造：Mining（改造为 Robot 系统）、Signal/Arbitrage（改造为 AI 经济引擎内部模块，不对 C 端暴露）、RedEnvelope（改造为 Reward 发放通道）
- 新增：Prediction（完全重写）、OTC/Power、AI 运营、Approval Engine、Support/Ticket

### I18N / L10N

7 语言：zh-CN、en-US、ja-JP、ko-KR、th-TH、de-DE、fr-FR。固定不译术语：Gainode、APT、APT-I、APT-C、Robot、OTC、Power、1X2、MFA、KYC、OTP、AI。

**V1.x 现状**：H5 只有 2 语言（自定义引擎），Admin 只有 3 语言（vue-i18n）。V2.0 均需扩展至 7 语言。

### 已确认的技术决策

| 决策项 | 决定 |
|---|---|
| arbitrage 模块处置 | 方案 B：保留为 AI 经济引擎基础，不对 C 端暴露 |
| 数据库 migration | 两阶段：SQL 文件 + 日期命名 → Phinx（DDL 变更超 10 次后） |
| 测试框架 | PHPUnit 10+，tests/Unit|Integration|Feature |
| OpenAPI 文档 | 手动维护 + sys_route 表扫描校验 |
| CI/CD 门禁 | 三步渐进式：语法检查 → php-cs-fixer + DDL 纪律 → PHPUnit + i18n 扫描 |
| Affiliate 范围 | 纳入 V6.1（4+7 页） |
| API-Football/BetBurger 合同 | 均已签署 |
| 资产调整权限 | 仅 ADMIN_SECURITY 可执行 |
| Owner Override | 紧急：ADMIN_SECURITY 单人可执行，需 MFA + 48 小时内向独立审计方提交 case_id/reason/evidence。非紧急：SELF_APPROVAL = FORBIDDEN，需第二人审批 |
| AI 策略模拟/建议管线 | 纳入 V6.1 |
| Contract Gap | 35 CONTRACT_FROZEN + 22 CONTRACT_GAP + 1 FUTURE（Owner 确认 16 页入产品范围，Machine Contract 未冻结，≠ 可开发） |
| V1.x 生产代码 | 作为 V2.0 升级基线，增量重构（非重写） |
| H5 组件库 | Vant 4 |
| Admin 组件库 | Element Plus（迁自 Layui Vue） |
| V1.x 数据迁移 | 一刀切迁移（Big Bang） |
| API 签名密钥 | V2.0 从代码硬编码迁移至环境变量 |

### V1.x 生产仓库清单（已全部确认）

| 仓库 | 线上地址 | 本地位置 | 说明 |
|---|---|---|---|
| H5 | `https://github.com/Xfd100/gainode_h5.git` | `_existing_prod/gainode_h5` | Vue 3.5 + TS 6.0 + Vite 8 |
| Admin | `https://github.com/Xfd100/gainode_admin.git` | `_existing_prod/gainode_admin` | Vue 3.3 + Vite 4 + Layui Vue + Pinia 2 |
| Backend API | `https://github.com/Xfd100/gainode_api.git` | `0.5代码/gainode后端/gainode` | **完全一致的副本**（8188 文件，byte-level identical） |

注意：`gainode_api` Git 仓库中无 Dockerfile/docker-compose，Docker 部署文件仅存在于本地 `0.5代码/gainode后端/gainode` 副本中（本地开发添加）。

## 基于代码的推断

- 现有 arbitrage 套利引擎（BetBurger + API-Football，合同均已签署）将改造为"内部 AI 经济引擎"，`ArbitrageTask` 进程保留。
- 现有 Service 层已有 `extends support\extend\Service` 的统一基类模式，新模块可以直接复用。
- Docker 化部署已就绪，生产部署只需调整 `APP_PROCESS_LIST` 环境变量。
- V1.x H5 的自定义 store 可以在不改变 API 接口的前提下替换为 Pinia（接口签名相同）。
- V1.x Admin 的 Schema 驱动架构是成熟的模式，V2.0 应保留并扩展：58 个新页面均走 Schema 驱动。
- V1.x Admin 的多 Tab + 面包屑 + 主题系统设计成熟，V2.0 可保留核心逻辑，替换底层组件库。
- V1.x 前后端共享的 MD5 签名机制可保留，只需将密钥外置。

## 待确认事项

- [ ] 正式生产参数的具体数值（分三批人工批准：开发→集成→上线前）
- [ ] 敏感文案的最终法律审核签核（不阻塞原型开发）
- [ ] PHP 版本在 `composer.json` 中正式改为 `>=8.2`（当前 `composer.json` 仍为 `>=8.1`，Dockerfile 为 `php:8.2-cli`，两者不一致）
- [ ] `composer.json` 中 `web3p/web3.php`、`web3p/ethereum-tx` 依赖清理（Web3 移除决策已定，但依赖尚未从 composer.json 移除，与「V2.0 不保留链上能力」的目标状态仍有差距）
- [ ] CI/CD 管线的具体配置（GitHub Actions / GitLab CI 选型）
- [ ] H5 Vant 4 集成方案（按需引入、CSS Variables 主题映射到 V1.x 暗色配色）
- [ ] Admin Element Plus 迁移方案（Schema 驱动组件 `<lay-*>` → `<el-*>` 改造、#009688 色彩映射、v-permission 指令适配）
- [ ] 一刀切迁移沙盒演练计划（至少 3 次全量迁移 + 回滚验证）
- [ ] 前端 ESLint / Prettier / Stylelint 配置规范
- [ ] Flutter App 的 Dart 编码规范和 Widget 测试框架
- [ ] 结果源、通知渠道、汇率源、KYC 证据服务商选定
- [ ] V1.x 生产数据库连接信息（用于 Schema 提取和迁移计划）
- [ ] V1.x 生产环境 URL（H5/Admin/API 域名）
- [x] 开发启动时间（STAGE-01 已授权启动，状态 = IN_PROGRESS，2026-08-13）

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/` 下 01–08 号文档 + i18n/ + assets/logo/
- `0.5代码/gainode后端/gainode/composer.json`（技术栈依赖清单）
- `0.5代码/gainode后端/gainode/sql/database.sql`（60+ 张表）
- `0.5代码/gainode后端/gainode/config/`（app.php / database.php / process.php / arbitrage.php）
- `_existing_prod/gainode_h5/`（V1.x 生产 H5 代码，克隆自 https://github.com/Xfd100/gainode_h5.git）
- `_existing_prod/gainode_admin/`（V1.x 生产 Admin 代码，克隆自 https://github.com/Xfd100/gainode_admin.git）
- `0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/`（V2.4.1 Admin 治理包）
- `0.5代码/admin-proto/`（Admin 交互原型）
- `通过agent开发前规则/PROJECT_BOOTSTRAP_TEMPLATE.md`（项目治理模板）
- `通过agent开发前规则/MASTER_PROJECT_GOVERNANCE.md`（AI 项目治理母版）
- `历史文档/` 仅用于历史追溯，不具备需求权威性
