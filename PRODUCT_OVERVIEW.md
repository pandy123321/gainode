# Gainode 产品总览（交接版）

> 面向对象：接手 Gainode 项目的工程师 / 产品 / 测试
> 文档性质：**交接辅助文档，非权威规范**。权威真源永远是 `Gainode_Development_Ready_V6.1_Latest/` 下的 01–08 号文档。
> 本文目标是「半天读懂产品在做什么、核心概念是什么、业务规则红线在哪、去哪里查细节」。
> 生成时间：2026-08-20

---

## 目录

1. [一句话定位](#1-一句话定位)
2. [产品要回答的 5 个问题](#2-产品要回答的-5-个问题)
3. [产品表达边界：能说什么 / 不能说什么](#3-产品表达边界能说什么--不能说什么)
4. [核心概念速查](#4-核心概念速查)
5. [经济模型核心](#5-经济模型核心)
6. [用户角色与权限](#6-用户角色与权限)
7. [产品信息架构](#7-产品信息架构)
8. [核心业务对象](#8-核心业务对象)
9. [关键业务规则（红线）](#9-关键业务规则红线)
10. [技术栈](#10-技术栈)
11. [代码目录结构](#11-代码目录结构)
12. [开发现状](#12-开发现状)
13. [权威文档索引（读什么）](#13-权威文档索引读什么)
14. [需要注意的坑 / TBC / 最近决策](#14-需要注意的坑--tbc--最近决策)

---

## 1. 一句话定位

**Gainode 是一个 AI 驱动的体育分析与预测（竞猜）平台。** 用户拥有一个可成长的 **Robot（AI 代理）**，Robot 等级决定其在 **AI Reward 预算池** 中的分配权重，产出动态的 **APT（平台内部数量代币）**；用户用 APT 参与**足球赛事预测（1X2）**、在 **OTC** 市场做用户间撮合交易，并消耗/恢复 **Power（操作资源）** 参与部分高价值动作。

> 关键认知：Gainode **不是**「稳赚收益看板」，也不是「挖矿套利盘」。Reward 是**动态的、可能为 0** 的预算分配权益，不是固定产出。

---

## 2. 产品要回答的 5 个问题

整个产品围绕 5 个用户问题组织，每个页面都应让用户知道三件事：**现在是什么状态、为什么、下一步能做什么**。

| # | 用户问题 | 对应模块 |
|---|---|---|
| 1 | 我现在能用什么？ | Auth、KYC、地区、风险、资格 |
| 2 | 我的 Robot 是什么状态？ | 等级、能力、启停、升级、Reward/Claim |
| 3 | 我能参与什么赛事？ | P0 足球赛前 1X2、状态、规则、锁定、结算、退款、更正 |
| 4 | 我的 APT 到底在哪里？ | 可用/冻结/待确认、流水、Power、OTC |
| 5 | 出了问题怎么办？ | 通知、工单、申诉、后台复核、审计 |

---

## 3. 产品表达边界：能说什么 / 不能说什么

这是**合规红线**，前端文案、UI、Mock 都必须遵守。

**可以说**：
- AI 体育数据、AI 分析、Robot 等级能力、动态 Reward、赛事预测、APT 数量、OTC 撮合。

**不能说**：
- 稳赚、固定收益、保本、固定回本、官方保价、无限流动性、提交订单 = 一定成交。

**Reward 必须表达为动态**：系数可能变化，也可能为 0。

**禁止**从旧 Figma、旧 Flutter、旧 Admin 代码、`历史文档/` 反推新需求。

---

## 4. 核心概念速查

### 4.1 Robot（机器人 / AI 代理）
- 共 **56 级**（Lv.1–56），UI 可分组展示（1–9 / 10–19 / … / 50–56），分组只影响 UI 不影响业务。
- 等级越高 → 分配权重 `standard_capacity` 越高 → 在预算池中的分配能力越强。
- **不等于**每天固定产出。

### 4.2 Reward（奖励）
- 核心公式：`pending_apt = standard_capacity × daily_reward_coefficient`
- `daily_reward_coefficient` 是**当天服务端系数，允许等于 0**。
- `pending_apt` 是「待领取」，不是「已到账」。
- 状态流：`CANDIDATE → HELD/PENDING_CLAIM → CLAIMING → CLAIMED`，或 `EXPIRED_RETURNED / REVIEW / REVERSED`。
- 领取成功必须先有账本 posting；超时/失败不得重复扣发；冲正保留原记录再追加反向记录。

### 4.3 APT（平台内部数量代币）
- **总量上限 1000 亿 APT**（`100,000,000,000`）。
- 数量不变量：`期初 + 批准激活 + 外部回流 - 正式销毁 = 期末 ≤ 1000 亿`。
- **`APT-I`**：系统内部数量账，P0 使用。
- **`APT-C`**：链上形态，Future（V2.0 **不保留链上能力**，纯中心化账本）。
- APT-I → APT-C 的 1:1 只是**数量映射**，不代表 1 APT = 1 USD，也不代表平台刚兑。

### 4.4 Power（操作资源）
- **可消耗、可恢复**的操作资源，**不是**手续费、不是 Reward、不是「收益算力」。
- 总容量由 Robot 等级/规则快照决定：`power_cap = resolve_power_cap(robot_level, active_rule_snapshot)`。
- 状态：`available / frozen / consumed / released / recovering / cap`。
- 使用场景：**OTC Sell**（冻结→成交消耗→未成交释放）、**Withdrawal**。
- **任何需要 Power 的写操作必须先返回 `PowerImpactPreview` 再由用户确认**；具体数值/时点永远来自服务端参数，前端不得写死。

### 4.5 OTC（场外交易）
- **用户间受控撮合**，不是平台固定回购。
- 订单状态（Canonical 见 05 §4）：`draft / review / matching / partial / completed / cancelled / expired / rejected / disputed`。
- 关键语义：`expired`（自然到期）≠ `cancelled`（主动取消）；`partial + cancelled/expired` 只释放 remaining 部分；`disputed` 保持冻结。
- 铁律：**下单 ≠ 成交；参考价 ≠ 官方兑付价；流动性不保证**。

### 4.6 Prediction（预测 / 竞猜）
- P0 首发玩法：**Football Pre-match 1X2**（主胜/平/客胜）。
- 赛果口径：**90 分钟 + 伤停补时**，不含加时/点球。
- 中文用户端统一显示「竞猜」。

### 4.7 Global P（身份/成长层）
- `global_p_level` 与 `ai_reward_eligibility`、`prediction_eligibility` 是**三个独立字段**。
- Global P 是身份成长层，**不自动**打开 AI Reward 或 Prediction。

---

## 5. 经济模型核心

### 5.1 四账分离（最重要的财务概念）

| 账 | 记录什么 | 不是什么 |
|---|---|---|
| APT 数量账 | available/frozen/pending/held/payable/claimed/burned | 不是现金收入 |
| APT 参考估值账 | quantity × reference price | 不是官方兑付价格 |
| 功能货币收入账 | 实际收到并有证据的 USDT/USDC/法币等 | 不是 APT 数量 |
| Reward/预算账 | AI/Prediction 的预算、候选、负债、支付 | 不等于用户可用余额 |

**四账不得静默互相补贴。** 页面上「10,000 APT」不代表平台赚了对应美元，也不代表用户能按固定价卖掉。

### 5.2 收入确认

平台只有在「真实收到 + 能匹配 + 证据完整 + 汇率来源有效 + 对账为 0」时才确认功能货币收入。**APT 服务费数量本身 ≠ 收入。**

### 5.3 AI 经济引擎（内部，不对外）

- 内部保留「可审计执行结果 → Reward Budget」的经济逻辑，但 **C 端不显示成「套利固定收益」**。
- 预算计算：`daily_ai_budget = min(mapped_apt_budget, stage_expected_budget, stage_hard_cap, cash_support_cap, human_approved_cap)`。
- 所有比例/上限来自 Parameter Release，前端不写死。

### 5.4 AI 与 Prediction 隔离

- `AI budget → Prediction = FORBIDDEN`；`Prediction funds → AI budget = FORBIDDEN`。
- 跨生态必须走新模型 + 审批 + 账本 + 回滚，不是参数开关。

---

## 6. 用户角色与权限

### 6.1 C 端角色

| 角色 | 能做什么 | 不能做什么 |
|---|---|---|
| 游客 | 看公开说明、公开赛事 | 真实价值写操作 |
| 已登录未 KYC | 看账户/公开内容、开始 KYC | 默认无 Prediction/OTC 权限 |
| 已准入用户 | 按服务端资格用 Robot/Prediction/APT/OTC | 绕过地区/风险/额度 |
| 受限用户 | 看历史、合法退款、工单、允许的资产取回 | 新的受限写操作 |

### 6.2 Admin 13 角色（严格职责分离 / SoD）

`END_USER / SUPPORT_AGENT / OPS_OPERATOR / KYC_REVIEWER / RISK_ANALYST / RISK_APPROVER / LEDGER_OPERATOR / FINANCE_REVIEWER / PARAM_EDITOR / PARAM_APPROVER / RELEASE_OPERATOR / AUDITOR / ADMIN_SECURITY`

**SoD（职责分离）是 Actor-level Invariant**，关键几条：
- 结算 Requester ≠ Approver；批准 Requester ≠ Approver；Approved ≠ Executed。
- 结果确认人 ≠ 结算审批人（Result confirmer ≠ Settlement approver）。
- 资产调整仅 `ADMIN_SECURITY` 可执行。
- Owner Override：紧急时 `ADMIN_SECURITY` 单人可执行，需 MFA + 48h 内向独立审计方提交 case_id/reason/evidence；非紧急时禁止自审自批。

### 6.3 功能资格统一规则

任何真实价值按钮都**不由前端自己判断**。服务端统一返回：

```json
{ "allowed": false, "reason_code": "KYC_REQUIRED", "reason_text": "...", "next_action": "OPEN_KYC", "rule_version": "...", "policy_version": "..." }
```

前端只负责**显示 + 执行 `next_action`**。

---

## 7. 产品信息架构

### 7.1 C 端（H5）

```text
Home     — 账户/准入状态 + Robot 摘要 + 推荐赛事 + 重要通知
Robot    — 概览 / 启停 / 等级地图 / 升级 / Reward 领取 / 活动记录
Prediction — 赛事列表 / 1X2 详情 / 确认 / 我的竞猜 / 订单详情 / 退款更正
Notice   — 通知列表（未读/全部）/ 详情 / 关联对象深链
Me       — 资料 / APT 账户 / 流水 / Power / OTC / 安全 / 帮助工单 / 设置
```

### 7.2 Admin（8 导航 → 33 权威页）

```text
01 工作台            — 运营总览 / 今日待办
02 用户与准入        — 用户列表 / 用户360 / KYC 队列 / 资产调整(条件页)
03 资产与账本        — 资产总览 / APT账户与流水 / 池子与对账 / 更正申请
04 机器人与权益      — Robot 列表 / Robot 详情 / Reward·Claim
05 OTC 与 Power      — OTC 订单 / 订单详情·审核 / Power 账户流水
06 赛事预测          — Market/Event / Market 详情 / Result/Settlement / Refund/Correction
07 风控·审批·参数·策略 — Risk Case / 审批中心 / 参数中心 / 地区·KYC·保护策略
08 客服·审计·运维    — 工单 / 工单详情 / 审计日志 / 异步·对账·系统状态
```

另有 **7 个 DEFERRED 页**（`A-AI-*`、`A-DATA-*`）仅占位不 404，**不计入验收**。

---

## 8. 核心业务对象

| 对象 | 作用 |
|---|---|
| User / AuthSession / KycCase / FeatureEntitlement | 身份、会话、KYC、功能资格 |
| Robot / RobotUpgradeOrder / AIReward | Robot 等级、升级订单、奖励 |
| AptAccount / AptLedgerEntry | APT 数量账 + 每笔流水（append-only） |
| PowerPosition / PowerLedgerEntry | Power 余额/冻结 + 流水 |
| Market / PredictionOrder / Result / Settlement / SettlementBatch / RefundCase / CorrectionCase | 预测全链路 |
| OtcOrder / OtcTrade | OTC 挂单/成交 |
| ApprovalRequest / ParameterRelease / ParameterSnapshot | 审批、参数发布/快照 |
| RiskCase / Ticket / TicketMessage / TicketAttachment | 风控、工单 |
| AuditLog / LoginAudit / NotificationDelivery / ConsentReceipt | 审计、登录、通知投递、确认回执 |

> 说明：标「投影」的对象（如 OtcEligibility/OtcCapacity/SecurityProfile）是**每次评估结果，非持久实体**。

---

## 9. 关键业务规则（红线）

1. **账本不可变（append-only）**：任何冲正/更正都走「保留原记录 + 追加反向记录（reversal）」，**禁止删除/覆盖历史流水**。Ledger 表仅 `state/audit_event_id/object_version` 三列可受控变更 + CAS 乐观锁。
2. **SoD**：发起人 ≠ 审批人；批准 ≠ 已执行；结果确认 ≠ 结算审批。
3. **参数化规则**：Power Cap、升级成本、reward 系数、冷却时间、领取有效期等数值**全部来自 Parameter Release**，前端/后端业务代码不得写死。
4. **Fail-Closed**：未冻结/未批准/无数值的参数，对应写操作一律 FAIL_CLOSED（拒绝执行），不得用 Mock 或默认值放行。
5. **页面五态**：`Loading / Content / Empty / Error / Restricted`；**Restricted ≠ Error**（「系统坏了」和「你没权限」不能同一提示）。
6. **写操作七态**：`Default / Submitting / Accepted-Processing / Success / Failed-No-Effect / Failed-Needs-Review`。
7. **Prediction 提交后**：不可撤销、不可减少、不可换方向；锁定前可同方向追加，锁定后不可提交。
8. **退款/更正**：退款需账本记录 + 原因 + 时间线；更正保留原 snapshot + 新建 version + 旧结算 reversal + 新结算重新 posting。
9. **升级必须报价**：升级前服务端返回报价快照（当前/目标等级、成本、capacity/power 差异、冷却、规则版本、报价过期时间），用户确认后才提交。**禁止前端先扣 APT 再等后端补状态**。
10. **通知与事务解耦**：Notice 通过 Outbox/异步投递，不阻塞业务事务。

---

## 10. 技术栈

| 层级 | 技术 |
|---|---|
| 后端语言/框架 | PHP ≥8.2 + Webman (Workerman，事件驱动常驻内存) |
| 数据库 | MySQL 8.4.9（illuminate/database ORM），双库 `webman` + `gainode` |
| 缓存/队列/定时 | Redis（3 实例）+ redis-queue + workerman/crontab（DB 驱动） |
| 认证/权限 | JWT (firebase/php-jwt) + 2FA；Casbin RBAC + RESTful |
| 路由 | DB 驱动动态路由（`sys_route` 表） |
| 校验/日志 | Laravel Validation + Monolog 多通道 |
| 外部数据源 | BetBurger + API-Football（合同已签） |
| H5 前端 | Vue 3 + TS + Vant 4 + Pinia + vue-i18n |
| Admin 前端 | Vue 3 + TS + Element Plus + Pinia |
| App | Flutter（全新） |
| 部署 | Docker + docker-compose |
| 文档 | OpenAPI 3.1 契约 |

---

## 11. 代码目录结构

```text
E:\github\sports\
├─ 0.5代码/gainode后端/gainode/    # 后端（PHP/Webman）
│   ├─ app/                         # 控制器（admin/api/command/queue）
│   ├─ library/                     # model/dao/service（业务层）
│   ├─ support/                     # 框架基础设施
│   ├─ process/                     # 长驻进程
│   └─ sql/                         # database.sql + YYYYMMDD_*.sql 增量 DDL
├─ gainode_admin_v2/                # Admin 前端（Vue3 + Element Plus）
├─ gainode_h5_v2/                   # H5 前端（Vue3 + Vant）
├─ Gainode_Development_Ready_V6.1_Latest/   # 权威规范 01–08（真源）
├─ .project-ai/                     # 共享上下文 / 治理（manifest/context/tasks/reviews）
├─ 0.5代码/admin-proto/             # Admin 交互原型（参考）
├─ 0.5代码/Gainode_Admin_Prototype_Planning_V2.4.1_CN/  # Admin 规划（参考）
├─ _existing_prod/                  # V1.x 线上三仓库 clone（只读参考，不入库）
├─ 通过agent开发前规则/             # AI 项目治理母版
└─ start-dev.ps1                    # 一键启动脚本
```

---

## 12. 开发现状

- **执行计划**：`07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.4，状态 `FROZEN_FOR_EXECUTION`，40 个工作包分 6 个 Stage。
- **已完成**：
  - STAGE-00 规划/文档冻结（独立审核 CONDITIONAL_APPROVAL，15 项 Finding 全闭合）。
  - STAGE-01 后端领域对象（Machine Contract MC1/MC2 FROZEN + 43 对象骨架 + 状态机）。
  - STAGE-02 后端核心（S02-P01~P08 全 APPROVED：通用内核/Auth/KYC/Ledger/Robot/Prediction/OTC/Power/治理/内部 AI 引擎）。
  - STAGE-03 H5 页面（H5-01~H5-11 全批次落地）+ Admin 基础设施（Element Plus + 33 页骨架 + 数据源三页真实接入）。
- **当前状态**：前端（H5 + Admin + Flutter）已移交同事开发；后端代码已 push 到 `feature/gainode-v3-serial-development` 分支。
- **开发模型**：`MERGED_DEV_REVIEW_SINGLE_ACTOR`（开发/审核合并，单人逐模块开发 + 自测，不再自动触发外部审核同步）。
- **关键分支**：`feature/gainode-v3-serial-development`（唯一活跃主线）；`master` 为过时中间态，忽略。

---

## 13. 权威文档索引（读什么）

> 遇到冲突时按此优先级。**不要**回 `历史文档/`、旧 Figma、旧代码去反推需求。

| 主题 | 文档 |
|---|---|
| 产品功能 | `01_PRODUCT_FUNCTIONAL_BASELINE.md` |
| 经济模型 / Power 规则 | `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md` |
| H5 页面 | `03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md` |
| Admin 页面 | `04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md` |
| 数据/状态/权限/API | `05_DATA_STATE_PERMISSION_API_CONTRACT.md` |
| 参数 | `06_PARAMETER_DICTIONARY.md` |
| 开发/验收 | `07_DEVELOPMENT_AND_ACCEPTANCE.md`（V3.4） |
| 视觉/交互/i18n | `08_VISUAL_DESIGN_SYSTEM_V2.4.md` |
| 实际用户字符串 | `/i18n` |
| Logo | `/assets/logo` |
| 共享上下文/治理 | `.project-ai/context.md`、`.project-ai/manifest.yaml` |

---

## 14. 需要注意的坑 / TBC / 最近决策

1. **Power 与 Robot 启停的关系有历史变更**：早期文档（02 V2.2、README）写「Robot Start 消耗 Power」，但 **CR-20260818-003 裁决为「Robot 启动/停止不消耗/释放 Power」**，`RobotService::start/stop` 已改为纯状态转移。**以 CR 裁决为准**，接手时遇到旧文档相关表述需注意。
2. **`.env` 不入库**：后端 `.env`（含 JWT_KEY、DB、Redis、数据源凭证）被 `.gitignore` 排除，接手需从 `.env.example` 复制并自填。
3. **数据源凭证为空**：BetBurger/API-Football 的 `api_key/access_token` 当前为空，`arbitrage_*` 表 0 数据；填凭证后需跑采集命令才有数据。
4. **TBC 参数**：① AI Reward 预算具体数值（`ai_reward_budget_cap`/`period_cap`）② reward 系数依赖的「当日预算」数值源 → 依赖预算的 `hold/completeClaim/expire/reverse` 仍 FAIL_CLOSED。
5. **GitHub 访问需代理**：本机 `127.0.0.1:10809` 代理工具必须运行才能 push/pull。
6. **`_existing_prod/` 是 V1.x 旧代码**，仅参考；2.0 开发只认仓库内三个子项目。
7. **Schema 驱动策略**：Admin 走「DTO 元数据」新路，**不碰 V1.x `sys_table_field` 老路**（字段语义不匹配）。
8. **生产参数分三批批准**（开发→集成→上线前），TBC 不允许自行填正式值。

---

*本文由交接整理生成，用于快速理解产品；任何细节以 `Gainode_Development_Ready_V6.1_Latest/01–08` 及 `.project-ai/` 为准。*
