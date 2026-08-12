# V1.x Production Code Baseline Review

> 文档类型：`00_EXISTING_BASELINE_REVIEW`（治理规则 Section 5 要求）
> 审查范围：V1.x 线上运行的三块代码 — 后端 API、Mobile H5、Admin Web
> 审查目的：为 V2.0 升级提供基线起点，识别可复用资产、已知风险和升级清单

---

## 1. 审查概要

| 项目 | 值 |
|---|---|
| 审查日期 | 2026-08-12 |
| 代码来源 | `https://github.com/Xfd100/gainode_h5.git`, `https://github.com/Xfd100/gainode_admin.git`, `https://github.com/Xfd100/gainode_api.git` |
| 本地镜像 | `_existing_prod/gainode_h5`, `_existing_prod/gainode_admin`, `_existing_prod/gainode_api` ↔ `0.5代码/gainode后端/gainode`（完全一致，8188 文件 byte-level identical） |
| 审查方法 | 静态代码分析（目录树、依赖清单、配置文件、API 层、路由、状态管理、组件结构） |
| 审查深度 | 架构层（非逐行审计） |

---

## 2. 后端 API（gainode后端/gainode）

### 2.1 技术基线

| 维度 | 值 | 评估 |
|---|---|---|
| 语言 | PHP 8.2 | 满足 V6.1 要求 |
| 框架 | Webman (Workerman) | 高性能、常驻内存、事件驱动，适合 V6.1 高并发场景 |
| 数据库 | MySQL 8.4.9 | 版本新，支持 JSON、窗口函数 |
| ORM | illuminate/database | Laravel Eloquent 独立版，成熟稳定 |
| 缓存 | Redis 3 实例 | default/cache/stack 分离，架构合理 |
| 队列 | Redis Queue (webman/redis-queue) | 满足异步任务需求 |
| 定时任务 | workerman/crontab (DB 驱动) | 任务可运行时管理，非硬编码 |
| 认证 | JWT (firebase/php-jwt) + Google 2FA | 标准方案，需扩展 MFA/OTP |
| 权限 | Casbin RBAC + RESTful | 策略存 DB，支持动态调整 |
| 日志 | Monolog 多通道 | 日志分类清晰 |
| 容器化 | Docker Compose (本地 0.5代码/ 副本有，Git 仓库 `gainode_api.git` 中**无**) | 生产部署方式待确认（裸机 vs Docker） |

### 2.2 现有表结构（database.sql，60+ 张表）

| 域 | 表 | V2.0 处理 |
|---|---|---|
| **Auth** | member_user, member_user_token, member_user_google_secret | 保留 + 扩展（MFA/OTP） |
| **Wallet** | member_wallet, member_wallet_account, member_wallet_recharge, member_wallet_withdraw, wallet_network, wallet_token | 改造为四账分离 |
| **Team** | member_user_team, team_* | 保留 + 扩展 Affiliate/Agent |
| **KYC** | member_user_kyc | 扩展多级状态机 |
| **Mining** | member_user_mining_*, mining_* | 改造为 Robot 系统 |
| **Signal/Arbitrage** | signal_*, arbitrage_* | 改造为 AI 经济引擎（内部） |
| **RedEnvelope** | red_envelope_* | 改造为 Reward 发放通道 |
| **Content** | article_*, article_category_* | 保留 |
| **System** | sys_admin, sys_role, sys_menu, sys_dept, sys_dict, sys_config, sys_route, sys_casbin_*, sys_operation_logs, sys_login_logs | 保留 + 扩展（Parameter Center 版本化） |
| **Language** | sys_lang | 保留 + 扩展 7 语言 |
| **Crontab** | sys_crontab | 保留 |

### 2.3 可复用基础设施

| 组件 | 路径 | 复用评估 |
|---|---|---|
| Controller 基类 | `support/extend/Controller.php` | **直接复用** |
| Service 基类 | `support/extend/Service.php` | **直接复用**（新增 DAO 代理注入） |
| DAO 基类 | `support/extend/Dao.php` | **直接复用** |
| Model 基类 | `support/extend/Model.php` | **直接复用** |
| Auth 中间件 | `support/middleware/Auth.php` | **直接复用** + 扩展 |
| Cors 中间件 | `support/middleware/Cors.php` | **直接复用** |
| 动态路由加载 | `support/extend/Bootstrap/Router.php` | **直接复用** |
| API 签名验证 | `support/middleware/Sign.php`（推断） | **直接复用** |
| BetBurger 客户端 | `support/arbitrage/BetBurgerClient.php` | **保留改造** |
| API-Football 客户端 | `support/arbitrage/ApiFootballClient.php` | **保留改造** |
| 统一响应格式 | `support/extend/Response.php`（推断） | **直接复用** |

### 2.4 已知风险

| 风险 | 严重度 | 描述 | 建议 |
|---|---|---|---|
| R1-API 签名密钥 | P1 | API 签名密钥 `projectApi` 在代码中硬编码 | V2.0 迁移至环境变量/Secret Manager |
| R2-Web3 私钥 | P0 → **CLOSED** | 区块链私钥存储位置不明。OWNER_DIRECTIVE 2026-08-12：V2.0 APT 改为纯中心化账本，不保留任何链上能力。Signer contract = N/A | N/A — 链上能力已移除 |
| R3-数据库密码 | P1 | 数据库密码在 config/database.php 中 | 迁移至 .env |
| R4-无自动化测试 | P2 | 未发现 PHPUnit 测试代码 | STAGE-01 同步建立测试框架 |
| R5-Docker 端口暴露 | P2 | 6 个端口全暴露 + Git 仓库中无 Dockerfile/docker-compose | 确认生产部署方式，Docker 文件同步至 Git 仓库 |
| R20-生产部署方式不明 | P1 | `gainode_api.git` 中无容器化文件，V1.x 可能裸机部署 | V2.0 决定是否容器化，统一部署方案 |

---

## 3. Mobile H5（gainode_h5）

### 3.1 技术基线

| 维度 | V1.x 值 | V2.0 目标 | 差距 |
|---|---|---|---|
| Vue | 3.5.40 | 3.5+ (保持) | **无差距** |
| TypeScript | 6.0 | 5+ (可降级兼容) | TypeScript 6.0 太新，降级至 5.x 更稳定 |
| Vite | 8.1.5 | 5/6 (降级) | Vite 8 太新，降级至稳定版 |
| 状态管理 | 自定义 reactive + localStorage | Pinia 2 + persistedstate | **需迁移** |
| i18n | 自定义轻量引擎 (~260 keys) | vue-i18n (7 languages) | **需迁移 + 扩展** |
| 组件库 | 无（全部手写） | Vant 4（已决策） | **需集成** |
| CSS 预处理 | Sass (sass-embedded) | 保留 | 无差距 |
| HTTP | Native fetch | 保留或 Axios | 无差距 |
| S3 上传 | @aws-sdk/client-s3（硬编码密钥） | 后端预签名 URL（V2：`POST /api/upload/presigned-url` → S3 presigned PUT） | **V2 已决策** |
| 测试 | 无 | Vitest + Playwright | **需新增** |
| .env | 无 | .env.development / .env.production | **需新增** |
| 包管理器 | npm | 保留 | 无差距 |
| 代码格式化 | Prettier | 保留 + 添加 ESLint | 需扩展 |

### 3.2 页面清单（19 views / 22 routes total：1 root + 4 tab children + 17 standalone top-level）

| 页面 | 路由 | V2.0 处理 |
|---|---|---|
| MainLayout (4 Tab) | `/`, `/home`, `/robot`, `/team`, `/my` | 保留结构，替换内容 |
| LoginView | `/login` | 保留 + 扩展（MFA/OTP） |
| RegisterView | `/register` | 保留 |
| LangView | `/lang` | 扩展至 7 语言 |
| HomeView | `/home` | 重构（V6.1 新首页：Robot+Prediction 入口） |
| AgentView (Robot) | `/robot` | 重构（V6.1 Robot 56 级系统） |
| MyAgentsView | `/my-agents` | 重构 |
| SignalsView | `/signals` | **移除**（不对 C 端暴露） |
| ArbitrageRecordsView | `/arbitrage-records` | **移除**（不对 C 端暴露） |
| TeamView | `/team` | 保留 + 扩展 Affiliate |
| MyTeamView | `/my-team` | 保留 |
| InviteView | `/invite` | 保留 |
| MyView | `/my` | 重构（V6.1 四账资产视图） |
| DepositView | `/deposit` | 保留 + 扩展 |
| WithdrawView | `/withdraw` | 保留（新增 Power 消耗） |
| LedgerView | `/ledger` | 重构（V6.1 四账 Appended Ledger） |
| DepositRecordsView | `/deposit-records` | 保留 |
| WithdrawRecordsView | `/withdraw-records` | 保留 |
| ClaimCenterView | `/claim-center` | 重构（V6.1 Reward Claim） |
| RedemptionRecordsView | `/redemption-records` | 保留 |
| HelpCenterView | `/help` | 保留 + 扩展 |
| ProfileEditView | `/profile-edit` | 保留 |

**V2.0 需新增页面**（至少 25 个，来自 V6.1 P0 清单）：
- Prediction 相关（赛事列表、竞猜下单、订单列表、结算明细）
- OTC 相关（买卖挂单、订单簿、历史）
- Robot 扩展（升级、Reward Claim 细化）
- KYC 增强（多级认证流程）
- 通知中心
- Affiliate Portal 入口

### 3.3 可复用资产

| 资产 | 评估 | 复用方式 |
|---|---|---|
| 暗色主题设计系统 | 配色一致、风格统一 | **直接复用**色板作为 V2.0 暗色主题基线 |
| SCSS 变量模式 | 重复定义，需集中化 | **重构**为全局 `_variables.scss` |
| 无线滚动模式 | IntersectionObserver 实现良好 | **直接复用** |
| 底部弹窗 (Bottom Sheet) | CountryPicker 等实现完善 | **直接复用**交互模式 |
| Toast 通知 | 轻量实现 | **保留**或迁移至组件库 |
| ConfirmDialog | Modal 交互 | **保留**或迁移至组件库 |
| 钱包连接组件 | MetaMask/TronLink/Phantom 三链 | **保留**（确认 V6.1 是否全需要） |
| API 服务分层 | `api/services.ts` 按域组织 | **直接复用**组织结构 |
| 路由守卫 | `beforeEach` 检查 token | **直接复用** |

### 3.4 已知风险

| 风险 | 严重度 | 描述 | 建议 |
|---|---|---|---|
| R6-S3 硬编码密钥 | P0 → **FULLY CLOSED 2026-08-12** | `src/utils/s3Upload.ts` 第 6-7 行硬编码 AWS Access Key + Secret Key。旧 Key 已在 AWS IAM 控制台轮换。V2.0 改用后端预签名 URL（`POST /api/upload/presigned-url` → S3 presigned PUT），前端不再持有任何 AWS 凭据 | ✅ Key 已轮换 + V2 contract 冻结 |
| R7-包名不符 | P3 | `package.json` name 为 "quiz" 而非 "gainode" | 修正为 "gainode-h5" |
| R8-自定义 MD5 | P2 | 200+ 行手写 MD5，浏览器兼容性风险 | 替换为 crypto-js 或 Web Crypto API |
| R9-无构建环境变量 | P1 | Vite proxy target 硬编码为 `https://api.gainode.com` | 使用 `VITE_API_BASE_URL` |
| R10-Node 版本要求高 | P3 | `engines: ^22.18.0 \|\| >=24.12.0`，太新 | 降级至 LTS (20.x / 22.x) |
| R11-ClaimCenter 硬编码数据 | P2 | mock 数据未清理 | V2.0 接入真实 API |

---

## 4. Admin Web（gainode_admin）

### 4.1 技术基线

| 维度 | V1.x 值 | V2.0 目标 | 差距 |
|---|---|---|---|
| Vue | 3.3.4 | 3.4+ | 需升级 |
| TypeScript | 4.5.4 | 5.x | **需升级** |
| Vite | 4.3.5 | 5.x/6.x | **需升级** |
| 状态管理 | Pinia 2.1.7 + persistedstate 3.2.3 | Pinia 2 (保留) | 无差距 |
| 组件库 | Layui Vue 2.23.3 | Element Plus（已决策，迁自 Layui Vue） | **需迁移** |
| 表单 | JSON Schema Form 1.0.16 | JSON Schema Form (保留) | 无差距 |
| i18n | vue-i18n (3 语言) | vue-i18n (7 语言) | **需扩展** |
| HTTP | Axios 1.5.1 | 保留 | 无差距 |
| 密码加密 | crypto-js AES-CBC | 保留（密钥外置） | **需修复** |
| 图表 | ECharts 5.4.3 | 保留 | 无差距 |
| 路由 | Hash History | 保留或改 History | 需决策 |
| Mock | MockJS 1.1.0 | **移除** | 生产代码不应含 Mock |
| 测试 | 无 | Vitest | **需新增** |
| .env | 无 | .env | **需新增** |
| 包管理器 | pnpm 8.14.0 | pnpm (保留) | 无差距 |

### 4.2 页面清单（46 routes / ~64 .vue files）

| 模块 | 路由 | V2.0 处理 |
|---|---|---|
| **WorkSpace** | `/workspace/workbench`, `/workspace/console`, `/workspace/analysis`, `/workspace/monitor` | 保留 + V6.1 Dashboard 重构 |
| **Error** | `/error/401`, `/error/403`, `/error/404`, `/error/500` | 保留 |
| **System** | `/system/admin` (CRUD), `/system/role`, `/system/menu`, `/system/dept`, `/system/dictionary`, `/system/file`, `/system/login`, `/system/option`, `/language/index` | 保留 + 扩展（V6.1 13 角色 + Casbin 策略 + Parameter Center） |
| **Content** | `/content/list`, `/content/classification` | 保留 |
| **User** | `/user/index`, `/user/grade` | 保留 + 扩展（KYC 多级审核流程） |
| **Assets** | `/assets/recharge`, `/assets/withdraw` | 保留 + 扩展（四账 Audit View） |
| **Mining** | `/mining/project`, `/mining/order` | **改造**为 Robot 管理（56 级配置+监控） |
| **Signal** | `/signal/arbitrage`, `/signal/signal` | **改造**为 AI Economic Engine 内部监控 |
| **Team** | `/team/relationship` | 保留 + 扩展 Affiliate/Agent Portal |
| **KYC** | `/kyc/kyc` | 扩展多级状态机审核 |
| **Configuration** | `/configuration/arbitrage`, `/configuration/funds`, `/configuration/other`, `/configuration/payment`, `/configuration/storage`, `/configuration/system` | 保留 + 扩展 Approval 工作流 |
| **RedEnvelope** | `/redEnvelope/index` | 改造为 Reward 管理 |
| **Demo Pages** | `/table/*`, `/form/*`, `/result/*`, `/component/*`, `/directive/*` | **移除**（V1.x 开发者演示页，非生产功能） |
| **Account** | `/account/profile`, `/account/message` | 保留 |
| **Login** | `/login` | 保留 + 扩展 MFA |

**V2.0 需新增页面**（来自 Admin 原型 58 Page ID）：
- Prediction 管理（赛事 CRUD、结果确认、退款/更正）
- OTC 管理（订单监控、撮合对账、异常处理）
- AI 运营（策略模拟、建议管线、参数调优）
- Approval Engine（工作流配置、审批队列、历史）
- Support/Ticket（工单管理）
- Affiliate/Agent（4 页 Agent 管理 + 7 页 Agent Portal 视图）
- Audit Trail（按维度的全量审计）

### 4.3 可复用资产（高价值）

| 资产 | 评估 | 复用方式 |
|---|---|---|
| **Schema 驱动架构** | Admin 的核心竞争力。后端下发 Schema 定义表格列、搜索表单、创建/编辑表单，前端动态渲染 | **核心保留**：V2.0 58 页全部走 Schema 驱动 |
| Pinia 持久化 | `pinia-plugin-persistedstate` 成熟方案 | **直接复用** |
| v-permission 指令 | 元素级权限控制 | **直接复用** |
| 多 Tab 导航 + Keep-alive | `useTab` composable 成熟 | **直接复用** |
| 面包屑 | `GlobalBreadcrumb` | **直接复用** |
| 响应式布局 | 768px 断点 + `mobile.css` | **保留升级** |
| 主题系统 | 暗色/亮色/灰色模式 + 三套标签风格 + 自定义主题变量 | **核心保留**：V2.0 Admin 需暗色/亮色双主题 |
| 子域模式 (Sub-field) | 一级菜单垂直图标栏 + 二级面板 | **保留**：适配 8 Root 导航 |
| 菜单动态加载 | `loadMenus()` 从 API 获取菜单树 | **直接复用** |
| AES 密码加密 | `crypto-js` AES-CBC 登录加密 | **保留**（密钥外置） |

### 4.4 已知风险

| 风险 | 严重度 | 描述 | 建议 |
|---|---|---|---|
| R12-AES 密钥硬编码 | P1 | `src/api/http.ts` 中 AES key `f080a463654b2279` 硬编码 | V2.0 迁移至环境变量 |
| R13-API 签名密钥硬编码 | P1 | SIGN_PRIVATE_KEY = 'projectApi' 硬编码 | V2.0 迁移至环境变量 |
| R14-Vite 版本过旧 | P2 | Vite 4.3.5，TS 4.5.4 | 升级至稳定版 |
| R15-MockJS 未禁用 | P2 | MockJS 拦截了 file/upload, user/menu, user/permission | 移除 MockJS，使用真实 API 或 MSW |
| R16-Demo 页面混入 | P3 | `/table/*`, `/form/*`, `/result/*`, `/component/*`, `/directive/*` 是开发者演示页 | 清理 |
| R17-未注册路由 | P2 | `permissions/admin/`, `permissions/role/`, `permissions/menu/` 目录存在但未注册 | 清理或注册 |
| R18-Hash History | P3 | 使用 `createWebHashHistory`，SEO 不友好 | 评估是否改为 History mode |
| R19-Layui Vue 依赖风险 | P1 | Layui Vue 社区活跃度和长期维护不确定性 | **已决策：迁移至 Element Plus** |

---

## 5. 三端一致性评估

### 5.1 共享约定

| 约定 | H5 | Admin | 后端 | 一致性 |
|---|---|---|---|---|
| API 签名 (MD5 + projectApi) | ✅ 手写 MD5 | ✅ crypto-js | ✅ | **一致**，密钥外置即可 |
| 响应格式 `{code, message, data}` | ✅ | ✅ | ✅ | **一致** |
| Token 头 | ✅ `Token` 头 | ✅ `Token` 头 | ✅ | **一致** |
| Language 头 | ✅ `Language` 头 | ✅ `Language` 头 | ✅ | **一致** |
| S3 上传 | ✅ → V2 后端预签名 URL | ✅ ImageUpload | ✅ | **V2 已决策：后端预签名** |

### 5.2 不一致问题

| 问题 | 影响 | 建议 |
|---|---|---|
| H5 无 .env，Admin 无 .env | 环境切换需改代码 | V2.0 两端统一 .env + VITE_ 前缀 |
| H5 自定义 i18n，Admin vue-i18n | 翻译文件格式不统一 | V2.0 统一使用 vue-i18n |
| H5 自定义 store，Admin Pinia | 状态管理模式不统一 | V2.0 H5 迁移至 Pinia |
| H5 暗色主题 only，Admin 双主题 | 用户端体验割裂 | H5 仅暗色（符合用户端定位），Admin 支持双主题 |
| H5 手写 UI，Admin Layui Vue | 组件库不统一 | 各自独立选型，分别适配 C 端和管理端需求 |

---

## 6. V2.0 升级优先级矩阵

| 优先级 | 类别 | 具体项 | 影响 Stage |
|---|---|---|---|
| **P0** | 安全 | S3 硬编码密钥已决策轮换 (R6) ✅ | STAGE-00 — **OWNER_DIRECTIVE 2026-08-12** |
| **P0** | 安全 | 所有硬编码密钥外置 (R1, R3, R12, R13)。R2/R6 已关闭 | STAGE-00 — R1/R3 STAGE-01 迁移至 .env |
| **P0** | 架构 | H5 组件库选型 ✅ | STAGE-00 — **已决策：Vant 4** |
| **P0** | 架构 | Admin 组件库选型 ✅ | STAGE-00 — **已决策：Element Plus** |
| **P1** | 数据 | 数据迁移策略制定 ✅ | STAGE-01 — **已决策：一刀切 Big Bang** |
| **P1** | 前端 | 两端 .env 环境变量管理 | STAGE-01 |
| **P1** | 前端 | H5 迁移至 Pinia | STAGE-02 |
| **P1** | 前端 | H5 迁移至 vue-i18n + 7 语言 | STAGE-02 |
| **P1** | 前端 | Admin TS/Vite 版本升级 | STAGE-01 |
| **P1** | 后端 | 数据库迁移方案制定 | STAGE-01 |
| **P2** | 前端 | Admin MockJS 移除 | STAGE-01 |
| **P2** | 前端 | Admin Demo 页面清理 | STAGE-01 |
| **P2** | 前端 | 前端测试框架搭建 | STAGE-02 |
| **P2** | 后端 | 后端测试框架搭建 | STAGE-01 |
| **P3** | 前端 | H5 package name 修正 (quiz → gainode-h5) | STAGE-01 |
| **P3** | 前端 | Admin Hash History 评估 | STAGE-01 |
| **P3** | 前端 | H5 Node 版本降级至 LTS | STAGE-01 |

---

## 7. 数据迁移风险地图

V1.x 生产数据库包含用户、钱包、团队等核心数据。V2.0 需要迁移至新的四账分离 + Robot + Prediction 结构。

| 数据域 | V1.x 表 | V2.0 目标表 | 迁移风险 | 建议 |
|---|---|---|---|---|
| 用户账户 | member_user | member_user (保留) | 低 | 直接复用，扩展字段 |
| 钱包余额 | member_wallet, member_wallet_account | apt_ledger, power_account | **高** | 快照+审计，逐条迁移至 append-only 账本 |
| 团队关系 | member_user_team | member_user_team + agent_* | 中 | 保留关系，扩展 Agent 属性 |
| KYC 状态 | member_user_kyc | member_user_kyc (扩展) | 低 | 保留数据，扩展状态字段 |
| 矿机订单 | member_user_mining_* | robot_* | **高** | V1.x Mining 逻辑与 V2.0 Robot 不兼容，需映射 |
| 信号/套利 | signal_*, arbitrage_* | ai_signal_*, ai_recommendation_* | 中 | V2.0 为内部引擎，历史数据可归档 |
| 系统配置 | sys_config, sys_dict | sys_config_versioned, sys_dict_versioned | 中 | 需版本化快照后增量导入 |

**数据迁移前置条件**：
- V1.x 生产数据库完整 Schema 导出（含索引、约束、触发器）
- V1.x 生产数据库样本数据导出（脱敏）
- V2.0 目标 DDL 冻结（所有新表定义完成）
- 迁移脚本编写 + 回滚方案
- 迁移测试（沙盒环境全量迁移验证）

---

## 8. 信息来源

- `_existing_prod/gainode_h5/` (`package.json`, `src/router/index.ts`, `src/api/http.ts`, `src/api/services.ts`, `src/stores/`, `src/i18n/`, `src/components/`, `src/views/`, `vite.config.ts`, `tsconfig*.json`)
- `_existing_prod/gainode_admin/` (`package.json`, `src/router/index.ts`, `src/router/module/base-routes.ts`, `src/api/http.ts`, `src/api/module/`, `src/store/`, `src/layouts/`, `src/views/`, `src/directives/permission.ts`, `src/lang/`, `vite.config.ts`, `tsconfig.json`)
- `0.5代码/gainode后端/gainode/` (`composer.json`, `sql/database.sql`, `config/`, `Dockerfile`, `docker-compose.yml`)
- 用户聊天对话 2026-08-12

## 9. 待确认事项

- [ ] V1.x 生产数据库连接信息 + 完整 Schema 提取
- [ ] V1.x 生产环境 URL（H5 / Admin / API 域名）
- [ ] V1.x 生产数据库样本数据脱敏导出（用于迁移测试）
- [x] 数据迁移策略：Big Bang 一刀切（OWNER_DIRECTIVE 2026-08-12），沙盒演练待执行
- [x] H5 组件库：Vant 4（OWNER_DIRECTIVE 2026-08-12）
- [x] Admin 组件库：Element Plus（OWNER_DIRECTIVE 2026-08-12）
- [ ] Admin 路由 History Mode 确认
- [x] S3 密钥轮换：2026-08-12 Owner 已完成 AWS IAM 控制台轮换；V2 改用后端预签名 URL
- [ ] S3 旧凭据在 AWS 控制台执行 deactivate/delete（待 Owner 手动执行）
- [ ] 所有硬编码密钥外置执行（R1/R3 等）
