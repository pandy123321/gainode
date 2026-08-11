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

### 开发阶段

项目处于 **开发就绪阶段（V6.1 基线）**。Admin 治理基线已升级至 **V2.4.1**（审核 #491 零 Finding，已合入 04）。

- Development / Sandbox：允许立即开始。
- Production Real-Value：NO-GO，需生产参数批准后开放。

### 技术栈（已确认）

| 层级 | 技术 | 版本/说明 |
|---|---|---|
| **后端语言** | PHP | ≥8.2（锁定 8.2，与 Dockerfile php:8.2-cli 一致） |
| **后端框架** | Webman (Workerman) | ~2.1，事件驱动、非阻塞、常驻内存 |
| **数据库** | MySQL | 8.4.9，PDO via illuminate/database，两个库：`webman`（默认）+ `gainode` |
| **缓存** | Redis | 3 实例（default/cache/stack） |
| **队列** | Redis Queue | webman/redis-queue ^1.3 |
| **定时任务** | workerman/crontab | DB 驱动，任务定义在 `sys_crontab` 表 |
| **认证** | JWT (firebase/php-jwt) + Google 2FA | Token 有效期 15 天，Refresh 60 天 |
| **权限** | Casbin | RBAC + RESTful，策略存储在 `sys_casbin_rbac` / `sys_casbin_restful` 表 |
| **日志** | Monolog | 多通道：default/api/library/task/crontab/queue/change_logs |
| **校验** | Laravel Illuminate Validation | ~10.48 |
| **Web3** | web3.php | BSC/ETH/TRON RPC，智能合约交互，USDT 转账 |
| **路由** | DB 驱动动态路由 | 路由存储在 `sys_route` 表，启动时从 DB 加载 |
| **容器化** | Docker + docker-compose | php:8.2-cli 基础镜像，6 个暴露端口 |
| **包管理** | Composer | PSR-4 autoloading |
| **外部 API** | BetBurger + API-Football | 体育数据源（现有套利引擎依赖） |
| **前端-H5** | Vue 3 + TypeScript | Mobile H5（4 Tab，375/390/430 三尺寸） |
| **前端-App** | Flutter | iOS / Android 原生 App |
| **前端-Admin** | Vue 3 + TypeScript | 桌面管理后台（8 Root，58 Page ID） |

### 后端代码结构（已确认）

现有后端源码位于 `E:\github\sports\0.5代码\gainode后端\gainode`，采用分层架构：

```text
app/                # 控制器层
  admin/controller/  # 后台控制器（arbitrage/ member/ sys/）
  api/controller/    # C 端 API 控制器
  command/           # CLI 命令（crontab/ init/ make/ arbitrage/）
  queue/redis/       # Redis 队列消费者

library/            # 业务逻辑层
  model/             # 数据模型（extends support\extend\Model）
  dao/               # 数据访问层
  service/           # 业务服务层（extends support\extend\Service）
  dict/              # 常量/字典
  event/             # 事件处理器

support/            # 框架基础设施
  arbitrage/         # 现有套利引擎核心（BetBurgerClient、ApiFootballClient、FixtureMatcher、
                       ArbitrageEngine）
  extend/            # 基础封装（Model、Dao、Service、Controller、Cache、Redis 等）
  utils/             # 工具类（JwtToken、Captcha、Curl、Encrypt、Snowflake ID 等）
  middleware/         # 中间件（Cors、ActionHook、BasicAuth、RateLimiter）

process/            # Workerman 长驻进程
  ArbitrageTask.php  # 套利引擎（信号采集、比赛同步、下单、结算）
  CrontabTask.php    # 定时任务调度器
  ChannelServer.php  # 内部发布/订阅通道（frame://0.0.0.0:2206）
  Pusher.php         # WebSocket 推送（ws://0.0.0.0:8888）

sql/database.sql    # 全量数据库结构（55+ 张表，无 migration 框架）
```

### 现有业务模块 vs V6.1 需求

| V6.1 模块 | 现有状态 | 差距等级 |
|---|---|---|
| Auth/登录 | 有：账号密码/OAuth/验证码/JWT/登录日志 | 低（缺 MFA 多因素认证、OTP、Session 管理） |
| KYC | 有：`member_user_kyc` 表、后台审核 | 中（缺多级 KYC 状态机、补件流程） |
| 用户/资格 | 有：用户、等级体系 | 高（缺 FeatureEntitlement、Global P、地区准入） |
| Robot | 无 | 全新模块 |
| Prediction | 有 sports arbitrage 引擎，但概念不同 | 完全重写（套利≠竞猜） |
| APT 账本 | 有 wallet 系统，但非 append-only | 重大改造 |
| Power | 无 | 全新模块 |
| OTC | 无 | 全新模块 |
| Notice | 有 `sys_notice` 表 | 重大改造（需 Outbox Pattern） |
| Approval | 部分（Casbin RBAC） | 重大改造（缺完整工作流引擎） |
| Parameter Center | 有 `sys_dict`/`sys_config` | 重大改造（缺 Release 生命周期） |
| 通知渠道 | 有 SwiftMailer/SMS/Telegram | 中（需统一 Outbox 投递框架） |
| 审计 | 有 `sys_operation_logs` | 中（需按 request/object/approval 追踪） |
| i18N | 有 7 语言 DB 存储 | 低（需补齐 V6.1 新增 key） |

### 产品核心领域

- **Robot**：56 级 AI 代理，`standard_capacity × daily_reward_coefficient = pending APT`（动态 Reward，系数可为 0）。
- **Prediction**：P0 仅 Football Pre-match 1X2（Home/Draw/Away），90 分钟+伤停补时，不含加时/点球。提交后不可撤销/换向/
  减额。中文用户端统一显示「竞猜」。
- **APT**：系统内部数量代币，总量上限 1000 亿。APT-I 为内部数量账，APT-C 为链上形态（Future）。APT-I→APT-C 数量 1:1 映射，不代表 1 APT = 1 USD。
- **Power**：可消耗、可恢复操作资源，用于 OTC Sell、Withdrawal、Robot Start。不用于 Prediction P0。容量由 Robot 等级决定。
- **OTC**：用户间受控撮合，非平台固定回购。状态：draft/review/matching/partial/completed/cancelled/expired/rejected/disputed。
- **Notice**：通知体系，与业务事务解耦，通过 Outbox/异步投递。

### 四账分离

| 账 | 记录什么 |
|---|---|
| APT 数量账 | available/frozen/pending/held/payable/claimed/burned |
| APT 参考估值账 | quantity × reference price |
| 功能货币收入账 | 实际收到并有证据的 USDT/USDC/法币等 |
| Reward/预算账 | AI/Prediction 的预算、候选、负债、支付 |

四账不得静默互相补贴。

### 用户角色

游客 / 已登录未 KYC / 已准入用户 / 受限用户 / 客服 / 运营 / 风控/审核 / 财务/账本 / 参数/发布角色 / 审计。

### Admin 角色（13 个，严格职责分离）

`END_USER / SUPPORT_AGENT / OPS_OPERATOR / KYC_REVIEWER / RISK_ANALYST / RISK_APPROVER / LEDGER_OPERATOR / FINANCE_REVIEWER / PARAM_EDITOR / PARAM_APPROVER / RELEASE_OPERATOR / AUDITOR / ADMIN_SECURITY`

关键分离：参数编辑≠批准≠激活；风险分析≠高危处置批准；更正申请≠更正批准；Result 确认≠Settlement 批准；申请人不能审批自己的申请。SoD 为 Actor-level Invariant：同一 Workflow Object 的冲突阶段必须由不同 Actor ID 执行，不可通过切换 active role 绕过。

### Admin 授权模型

最终授权公式（非纯 RBAC）：

```text
FINAL_AUTHORIZATION = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD
```

- Canonical Role 是 RBAC Role Identity 的唯一权威来源（非 API 权限唯一来源）
- UI Persona 限导航展示（PRESENTATION_ONLY），不授予 Role
- ABAC / Policy 失败 = FAIL_CLOSED
- 页面权限 ≠ 字段权限 ≠ 数据范围权限

### 客户端

| 端 | 技术栈 | 说明 |
|---|---|---|
| Mobile / H5 | Vue 3 + TypeScript | 4 个底部导航：首页 / Robot / 竞猜 / 我的；375/390/430 三尺寸兼容 |
| App (iOS/Android) | Flutter | 原生 App，后续阶段开发 |
| Admin Web | Vue 3 + TypeScript | 8 个一级导航，58 个 Page ID（51 Admin + 7 Agent Portal） |

**Admin 合同状态感知**：
- FROZEN = 35 页（05 契约已冻结，可进入 HIFI 实现）
- CONTRACT_GAP = 22 页（含 P1_CONDITIONAL，仅 Preview / FAIL_CLOSED）
- FUTURE = 1 页（A-MIGRATION-001，默认关闭）
- 关键 P1_CONDITIONAL 页面：A-USER-004（资产调整，Preview-Only，执行按钮不可用）

### I18N / L10N

7 语言：zh-CN、en-US、ja-JP、ko-KR、th-TH、de-DE、fr-FR。
固定不译术语：Gainode、APT、APT-I、APT-C、Robot、OTC、Power、1X2、MFA、KYC、OTP、AI。
中文端 Prediction 显示「竞猜」，内部 Page ID/API/Domain 保持 canonical Prediction。

### 产品表达禁止项

禁止：稳赚、固定收益、保本、固定回本、官方保价、无限流动性、提交订单=一定成交、APR/APY、下注/投注/博彩/赔率/盘口/押注。

### 视觉风格

Western / Premium / Sports-Tech / Operational。降低 Generic Card Feel。浅色主题为主。

### 运行方式

```bash
# 开发环境（Windows）
php windows.php

# 生产环境（Linux/Docker）
php start.php start -d

# Docker
docker-compose up -d
```

主端口：8787（HTTP）/ 8786（Task Server）/ 8686（协程）/ 8888（WebSocket）/ 2206（Channel）/ 8989（Proxy）

## 已确认的技术决策

| 决策项 | 决定 | 说明 |
|---|---|---|
| **arbitrage 模块处置** | 方案 B：保留为 AI 经济引擎基础 | 保留核心代码和 10 张 arb 表。`confirmed_profit` 计算结果写入内部经济引擎（`reference_profit → mapped_apt_budget`），不对 C 端暴露套利细节 |
| **数据库 migration** | 两阶段：SQL 文件 + 日期命名 → Phinx | DDL 变更超过 10 次后引入 Phinx；当前阶段 `sql/YYYYMMDD_description.sql` |
| **测试框架** | PHPUnit 10+ | `tests/` 根目录，分 Unit / Integration / Feature 三层 |
| **OpenAPI 文档** | 手动维护 + 路由表扫描 | 初期手写 `openapi.yaml`；中期扫描 `sys_route` 表辅助校验 |
| **CI/CD 门禁** | 三步渐进式 | 语法检查 → php-cs-fixer + DDL 纪律 → PHPUnit + i18n 扫描 |
| **生产参数签核** | 分三批 | 开发阶段→集成测试→上线前，从 06 提取 TBC 清单分批人工签核 |
| **敏感文案** | 原型阶段保持 PENDING_HUMAN_REVIEW | 上线前统一法务/合规签核，不阻塞原型开发 |
| **术语翻译** | Power / Robot / OTC / OTP / MFA / KYC / AI / APT / 1X2 保持英文 | Prediction 中文端统一「竞猜」 |

## 基于代码的推断

- 现有 arbitrage 套利引擎（BetBurger + API-Football）将改造为 "内部 AI 经济引擎"（`confirmed_profit → reference_profit → mapped_apt_budget`），拆除对 C 端的直接暴露路径，`ArbitrageTask` 进程保留。
- 现有路由 DB 驱动架构可以作为 V6.1 Parameter Center 的启发——参数也可存储在 DB 中，通过 Release 版本控制生效。
- 现有 Service 层已有 `extends support\extend\Service` 的统一基类模式，新模块（Robot/Power/OTC/Prediction）可以直接复用这套分层约定。
- Docker 化部署已就绪，生产部署只需调整 `APP_PROCESS_LIST` 环境变量即可控制启用的后台进程。

## 待确认事项

- [ ] 正式生产参数的具体数值（所有 TBC 参数需分三批人工批准：开发阶段→集成测试→上线前）
- [ ] 敏感文案的最终法律审核签核（`sensitive-copy-review.json` 中标记 PENDING_HUMAN_REVIEW 的条目，原型阶段不阻塞）
- [ ] PHP 版本在 `composer.json` 中正式改为 `>=8.2`
- [ ] PHPUnit 测试目录结构约定和覆盖率目标
- [ ] CI/CD 管线的具体配置（GitHub Actions / GitLab CI 选型）
- [ ] 前端 ESLint / Prettier / Stylelint 配置规范
- [ ] Flutter App 的 Dart 编码规范和 Widget 测试框架

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/` 下 01–08 号文档 + i18n/ + assets/logo/
- `0.5代码/gainode后端/gainode/composer.json`（技术栈依赖清单）
- `0.5代码/gainode后端/gainode/config/app.php`（核心配置）
- `0.5代码/gainode后端/gainode/config/database.php`（数据库配置）
- `0.5代码/gainode后端/gainode/config/process.php`（进程拓扑）
- `0.5代码/gainode后端/gainode/config/arbitrage.php`（套利引擎配置）
- `0.5代码/gainode后端/gainode/Dockerfile` / `docker-compose.yml`
- `0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/`（V2.4.1 Admin 治理包，审核 #491）
- `历史文档/` 仅用于历史追溯，不具备需求权威性
