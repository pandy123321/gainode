# Gainode Admin Prototype Planning V2.4.1 CN
# Final Consolidation & Governance Alignment

> **DOCUMENT_STATUS = READY_FOR_INDEPENDENT_REVIEW**
> **DATE = 2026-08-11**
> **UPSTREAM = Gainode Admin Prototype Planning V2.4 CN**
> **SOURCE_OF_TRUTH = 01–08 + /i18n + /assets/logo**
> **NOT_A_PRODUCT_REDESIGN = YES**

---

## 0. 版本定位

本版是对 `Gainode_Admin_Prototype_Planning_V2.4_CN` 的 **STRUCTURE + CONTRACT + GOVERNANCE + HIFI EXECUTION CLOSURE** 修订。

V2.4 的运营能力方向是正确的。本轮不是减少功能，而是：

- 把新增能力正确放回 Gainode 已冻结的后台治理体系
- 修复 14 个一级导航与冻结 8 一级导航的冲突
- 闭合高风险操作 SoD / Self-Approval
- 建立完整的 Page ID Migration Matrix
- 重新分类 Priority（不再把几乎所有页标 P0）
- 补充高风险页面 Write State Variant
- 纠正 Provider / AI Evidence 状态声明
- 修复中文 UI 英文污染
- 升级 QA 为 Evidence-Based QA

---

## 1. 执行原则

```text
KEEP_GOOD_V2_4_CONTENT = TRUE
REWRITE_FROM_SCRATCH = FALSE
NO_PRODUCT_REDESIGN = TRUE
NO_ECONOMIC_RULE_INVENTION = TRUE
NO_API_INVENTION = TRUE
NO_PARAMETER_INVENTION = TRUE
NO_SILENT_PAGE_DELETE = TRUE
NO_UNAUTHORIZED_P0_PROMOTION = TRUE
EVIDENCE_FIRST = TRUE
FAIL_CLOSED = TRUE
```

---

## 2. ADMIN_ROOT_NAV — 导航恢复为固定 8 个

```text
ADMIN_ROOT_NAV_COUNT = 8
ADMIN_ROOT_NAV_MUTATION = FORBIDDEN
```

### 8 Root 最终结构

```
01 工作台
├── 运营总览 / 关键异常 / 核心 KPI
├── Provider Health 摘要
├── AI / Data Health 摘要
├── 待审批摘要
└── 系统异常摘要

02 用户与准入
├── 用户列表 / 用户360
├── KYC 审核队列
├── 用户限制与恢复
├── 用户资产调整（超级管理员）
├── 代理总览 / 代理列表 / 代理详情
├── 推荐关系与代理统计（只读）
└── 客服工单中心

03 资产与账本
├── APT 资产总览
├── APT 账户与流水
├── 池子 / 对账 / 冲正
├── Reward 与经济记录
├── Asset Adjustment / Correction / Journal
└── 资产相关 Audit Entry

04 机器人与权益
├── Robot 列表 / 详情与运行监控
├── Reward / Claim 监控
├── 升级与 Power Cap 变化
├── Robot 状态 / 资格
└── Robot Benefit

05 OTC 与 Power
├── OTC 订单中心
├── OTC 订单详情 / 审核
├── 撮合 / 争议 / Power 监控
├── Power 账户与流水
└── Power Cap / Freeze / Consume / Release / Recover

06 赛事预测
├── 赛事 / 竞猜列表
├── 竞猜详情 / 参与订单管理
├── 结果 / 结算 / 退款 / 更正
├── 数据质量管理
├── 足球数据管理
├── 市场 / 赔率数据
└── Signal 与数据质量

07 风控 / 审批 / 参数 / 策略
├── Risk Case
├── 审批中心
├── 参数 Definition / Candidate
├── Parameter Release / Snapshot
├── 地区 / KYC / 保护策略
├── AI 策略 / 套利模拟
├── AI 运营建议 / 市场分析
├── 紧急操作
└── 运营与经济报表

08 客服 / 审计 / 运维
├── 全量操作日志
├── 敏感操作审计
├── 异步任务 / 系统状态
├── Provider 监控
├── 数据源管理
├── AI 竞猜运营助手 / 用户客服风险助手
├── RBAC 角色
├── 语言管理
├── 系统配置
└── APT Migration（Future）
```

---

## 3. Priority 重分类

### P0 定义

只有同时满足以下条件的页面才能标 P0：

```text
BUSINESS_DEFINED + DATA_DEFINED + STATE_DEFINED +
PERMISSION_DEFINED + API_OR_READ_MODEL_DEFINED +
PRODUCTION_USE_REQUIRED
```

### 分类体系

| Priority | 含义 |
|---|---|
| P0 | 上线 Admin 必须存在 |
| P1 | 产品已确认方向，实现可推迟 |
| P1_CONDITIONAL | 产品方向认可，但上游 Contract 未冻结 |
| FUTURE | 后续版本规划 |

### 重新分类汇总

| 旧 P0 页面数 | 新 P0 数 | 新 P1 数 | 新 P1_CONDITIONAL 数 | 新 FUTURE |
|---|---|---|---|---|
| 49 | 32 | 8 | 10 | 1 |

### 详细 Priority 重分类

**降为 P1_CONDITIONAL 的页面**（上游 05/06 未冻结对应 Contract）：

| Page ID | 中文页面 | 原因 |
|---|---|---|
| A-AFF-001 | 代理总览 | Affiliate Object 未冻结于 05 |
| A-AFF-002 | 代理列表 | Affiliate Object 未冻结于 05 |
| A-AFF-003 | 代理详情 | Affiliate Object 未冻结于 05 |
| A-AFF-004 | 推荐关系与代理统计 | AffiliateRelation 未冻结于 05 |
| A-DATA-002 | 数据源管理 | DataProvider / ProviderHealth 未冻结于 05 |
| A-DATA-004 | 市场/赔率/套利原始数据 | MarketFeed / ArbitrageOpportunity 未冻结于 05 |
| A-DATA-005 | Signal与数据质量 | AISignal 未冻结于 05 |
| A-AI-003 | AI市场分析 | AI 分析对象未冻结于 05 |
| A-AI-004 | AI套利策略模拟 | SimulationRun / AIStrategy 未冻结于 05 |
| A-AI-005 | AI竞猜运营助手 | 依赖 AI 建议 Pipeline 未冻结 |

**降为 P1 的页面**：

| Page ID | 中文页面 | 原因 |
|---|---|---|
| A-DATA-001 | 数据驾驶舱 | P0 运营总览已覆盖摘要；数据驾驶舱可后续上线 |
| A-DATA-003 | 足球数据管理 | 05 仅定义数据流方向，对象未正式冻结 |
| A-AI-001 | AI运营驾驶舱 | 05 未冻结 AI 运营对象 |
| A-AI-002 | AI运营建议 | 05 未冻结 AIRecommendation 对象 |
| A-AI-006 | AI用户/客服/风险助手 | 确认 P1 |
| A-OTC-003 | 撮合/争议/Power监控 | P0 订单中心+详情已覆盖核心；监控页可后上 |
| A-ROBOT-004 | 升级与Power Cap变化 | 信息可在 Robot 详情 Tab 内展示 |
| A-REPORT-001 | 运营与经济报表 | P0 运营总览覆盖核心指标 |

**保持 FUTURE 的页面**：

| Page ID | 中文页面 |
|---|---|
| A-MIGRATION-001 | APT Migration |

### 最终 Priority Counts

```text
P0_COUNT = 32
P1_COUNT = 8
P1_CONDITIONAL_COUNT = 10
FUTURE_COUNT = 1
TOTAL = 51
```

---

## 4. AGENT_PORTAL_PRIORITY

7 个 Agent Portal 页面统一标记为：

```text
AGENT_PORTAL_STATUS = P1_CONDITIONAL
NOT_PRODUCTION_READY = TRUE
```

原因：Affiliate / AffiliateRelation / Agent Permission Contract 未进入 05 正式冻结。

---

## 5. HIGH-RISK SoD — 正式闭合

### 全局硬规则

```text
SELF_APPROVAL = FORBIDDEN
REQUESTER_ID != APPROVER_ID
APPROVED != EXECUTED
```

### 必须 SoD 的操作

以下所有操作必须明确 Requester ≠ Approver：

1. Asset Adjustment（资产调整）
2. Ledger Correction（账本冲正）
3. Parameter Release（参数发布）
4. High-Risk Parameter Change（高风险参数变更）
5. Settlement（结算）
6. Settlement Correction（结算更正）
7. Refund Correction（退款更正）
8. Major User Restriction（重大用户限制）
9. Major Permission Change（重大权限变化）
10. Emergency Economic Operation（紧急经济操作）

### 统一高风险流程

```text
Create Proposal
→ Validation
→ Preview
→ Impact Assessment
→ Reason / Evidence
→ Submit for Approval
→ Independent Approval (MFA)
→ Execution
→ Processing
→ Success / Failed / Result Unknown
→ Timeline / Ledger / Audit
```

### Owner Override 重新定义

```text
OWNER_OVERRIDE_CANNOT_BYPASS_LEDGER = TRUE
OWNER_OVERRIDE_CANNOT_ERASE_AUDIT = TRUE
OWNER_OVERRIDE_CANNOT_BYPASS_SOD_BY_DEFAULT = TRUE
```

Emergency Override 至少在以下条件下可用：

- Trigger Condition 明确
- Eligible Role 限定
- Reason Required + Evidence Required
- MFA Required
- Post-review Required（事后补审期限 + 超时升级）

如果当前权威基线不存在正式 Override Contract：

```text
OWNER_OVERRIDE_CONTRACT_STATUS = CONTRACT_GAP
```

---

## 6. 高风险页面 Write State Variant 补充

### 标准 Write State（按适用场景）

```text
DEFAULT / INVALID / PREVIEW / CONFIRM
SUBMITTING / PROCESSING / UNDER_REVIEW
SUCCESS / FAILED / RESULT_UNKNOWN
CONFLICT / STATE_CHANGED / EXPIRED / RESTRICTED
NO_PERMISSION / DEPENDENCY_UNAVAILABLE
```

### 各高风险页面 State 覆盖要求

**A-KYC-001（KYC 审核）**

| State | 是否适用 |
|---|---|
| DEFAULT / INVALID / PREVIEW | ✓ |
| SUBMITTING / PROCESSING / UNDER_REVIEW | ✓ |
| SUCCESS / FAILED | ✓ |
| STATE_CHANGED（已由他人处理） | ✓ |
| CONFLICT（档案在审核中已变更） | ✓ |

**A-USER-004（用户资产调整）**

| State | 是否适用 |
|---|---|
| PREVIEW / CONFIRM | ✓ |
| SUBMITTING / PROCESSING | ✓ |
| SUCCESS / FAILED / RESULT_UNKNOWN | ✓ |
| STATE_CHANGED（账户状态在审批中变更） | ✓ |
| NO_PERMISSION（非超级管理员） | ✓ |

**A-PREDICT-004（结果/结算/退款/更正）**

| State | 是否适用 |
|---|---|
| PREVIEW / CONFIRM | ✓ |
| UNDER_REVIEW（Settlement 审批中） | ✓ |
| SUCCESS / FAILED / RESULT_UNKNOWN | ✓ |
| STATE_CHANGED（赛事结果已更新） | ✓ |
| CONFLICT / EXPIRED | ✓ |

**A-OTC-002（OTC 订单详情/审核）**

| State | 是否适用 |
|---|---|
| UNREAD / REVIEW | ✓ |
| SUBMITTING / PROCESSING | ✓ |
| SUCCESS / FAILED | ✓ |
| STATE_CHANGED（订单状态已变化） | ✓ |
| CONFLICT / EXPIRED | ✓ |

**A-CONFIG-002（Parameter Release/Snapshot）** 完整生命周期：

```text
Draft → Review → Approved → Scheduled → Active →
Paused → Rolled Back → Failed → Unknown
```

**A-RISK-001（Risk Case）/ A-APPROVAL-001（审批中心）**

| State | 是否适用 |
|---|---|
| OPEN / IN_PROGRESS / WAITING_EVIDENCE | ✓ |
| PENDING_APPROVAL / APPROVED / REJECTED | ✓ |
| ESCALATED / RESOLVED / CLOSED | ✓ |
| EXPIRED / STATE_CHANGED | ✓ |

### RESULT_UNKNOWN 处理

所有可能发生"请求已提交但客户端不知道结果"的写操作必须：

```text
NO_REPEAT_SUBMIT
KEEP_IDEMPOTENCY_KEY
STATUS_QUERY
REFERENCE_ID
CORRELATION_ID
VIEW_HISTORY
OPEN_SUPPORT
```

---

## 7. Provider / AI Evidence 状态纠正

将 `COMPLETE` 声明拆分为 UI_SPEC / PROVIDER_CONTRACT / RUNTIME 三个维度：

| 能力 | UI Spec | Provider Contract | Runtime |
|---|---|---|---|
| API-Football 监控 | VERIFIED_PASS | CONTRACT_GAP | NOT_YET_EXECUTED |
| BetBurger 监控 | VERIFIED_PASS | CONTRACT_GAP | NOT_YET_EXECUTED |
| AI 套利策略模拟 | VERIFIED_PASS | CONTRACT_GAP | NOT_YET_EXECUTED |
| AI 市场分析 | VERIFIED_PASS | CONTRACT_GAP | NOT_YET_EXECUTED |
| AI 竞猜运营助手 | VERIFIED_PASS | CONTRACT_GAP | NOT_YET_EXECUTED |
| AI 用户/客服/风险助手 | VERIFIED_PASS | CONTRACT_GAP | NOT_YET_EXECUTED |

```text
AI_REAL_EXECUTION = DISABLED（不变）
```

---

## 8. 中文 UI 本地化规则

### 产品锁定词（保持英文）

Gainode / Robot / APT / APT-I / APT-C / OTC / Power / 1X2 / MFA / KYC / AI

### 必须中文化的普通操作词

| 旧用词 | 修正为 |
|---|---|
| Reward/Claim监控 | 奖励与领取监控 |
| Signal与数据质量 | 信号与数据质量 |
| Parameter Release/Snapshot | 参数发布与快照 |
| Risk Case | 风险事件 |
| Failed | 失败 |
| Unknown | 结果未知 |
| Partial | 部分完成 |
| Active | 生效中 / 已启用（按语境） |
| Preview | 影响预览 |
| Ledger | 流水 |
| Audit | 审计日志 |
| Evidence | 证据 |
| Settlement | 结算 |
| Correction | 更正 |
| Rollback | 回滚 |
| Pause | 暂停 |
| Candidate（参数） | 候选值 |
| Release（参数） | 发布 |
| Snapshot（参数） | 快照 |
| Dispatch | 派发 |

### 禁止英文短语出现在用户可见界面

- ~~"Select Asset Type"~~ → "选择资产类型"
- ~~"Submit for Approval"~~ → "提交审批"
- ~~"Impact Preview"~~ → "影响预览"
- ~~"Idempotency Key"~~ → "幂等键"

内部 enum / object / API field 可保持英文。

---

## 9. Page Spec 模板统一

51 个 Admin 页面统一使用以下模板：

```text
PAGE_ID / PAGE_NAME / PRIORITY / CONTRACT_STATUS
ROOT / GROUP / GOAL
DATA_SOURCE / READ_MODEL
LAYOUT / KEY_FIELDS / FILTERS
ACTIONS / ALLOWED_ACTIONS / FORBIDDEN_ACTIONS
STATES（必须覆盖 Write State）
ROUTE / RETURN
PERMISSION / AUDIT_REQUIREMENT
I18N / RESPONSIVE / ACCEPTANCE
```

高风险页面额外：

```text
PREVIEW / IMPACT / APPROVAL
MFA / IDEMPOTENCY / UNKNOWN_RESULT
CORRELATION_ID
```

---

## 10. Agent Portal 每页补充 Spec 粒度

7 个 Agent Portal 页面统一补充：

```text
PAGE_ID / PAGE_NAME / PRIORITY = P1_CONDITIONAL
ROOT / PORTAL = AGENT_PORTAL
GOAL / DATA_SCOPE / LAYOUT
KEY_FIELDS / READ_MODEL
ALLOWED_ACTIONS / FORBIDDEN_ACTIONS
STATES / ROUTE / RETURN
PERMISSION / ERROR / EMPTY / NO_PERMISSION
AUDIT / RESPONSIVE / I18N / ACCEPTANCE
```

### Agent Data Scope（Fail Closed）

```text
AGENT_SCOPE = OWN_AFFILIATE_DOMAIN_ONLY
DATA_ACCESS = DENY（无法解析 Scope 时）
NO_CROSS_AGENT_DATA = TRUE
NO_GLOBAL_ASSET_ADJUSTMENT = TRUE
NO_GLOBAL_ECONOMIC_MODEL = TRUE
NO_GLOBAL_AUDIT = TRUE
NO_REFERRAL_REBIND = TRUE
```

---

## 11. V2.4 已正确内容全部保留

以下方向已经通过审核，禁止重新设计：

### 11.1 运营闭环
发现问题→定位对象→查看影响→执行操作→审批→处理结果→Timeline→Audit

### 11.2 用户管理
UID/手机号/邮箱/注册方式/直推/上级/推荐码/Robot 状态/KYC/APT/Power

### 11.3 用户限制
账户/余额/OTC/Robot 分别控制，不得合并为 Disable User

### 11.4 资产调整
Ledger Based / Append-only / Correction / Journal，不得 SET balance

### 11.5 AI
AI_SIGNAL / AI_ANALYSIS / AI_RECOMMENDATION / AI_SIMULATION / HUMAN_IN_LOOP
AI_REAL_EXECUTION = DISABLED

### 11.6 OTC
CONTROLLED_MATCHING，不是 Crypto Exchange Order Book

### 11.7 Power
Available / Frozen / Consumed / Released / Recovering / Cap

### 11.8 Audit
Append-only / Sensitive Data Masking / Actor/Action/Object/BeforeAfter/Reason/Evidence

### 11.9 Agent Scope
Affiliate Scope Isolation / NO_CROSS_AGENT_DATA

---

## 12. 后台视觉方向（保持不变）

- 中文界面 / 深蓝 Sidebar + 白/浅灰内容区
- 1440px Desktop First / Table First
- 复杂详情独立页 / 快速预览 480/640px Drawer
- 高风险动作集中在详情 Action Panel
- 红色只用于真实风险/失败
- AI 模块：建议 + 依据 + 影响对象 + 操作
- 数字右对齐 / Sticky Filter / 虚拟滚动
- Saved View + Column Preference

---

## 13. 不做的功能（保持不变）

- 万能 SQL 查询器 / 直接改数据库 / 直接改 Ledger
- 直接改 Reward / 直接改比赛结果 / 直接改 Robot Level
- AI 自动真实交易/下注 / AI 自动批准高风险动作
- 用户/代理财富排行榜
- 第二套 Parameter Center / 第二套 Audit

---

## 14. 最终 Gate

```text
ADMIN_ROOT_NAV_COUNT = 8
ADMIN_PAGE_COUNT = 51
AGENT_PORTAL_PAGE_COUNT = 7
DUPLICATE_PAGE_ID = 0
PAGE_MIGRATION_MATRIX = COMPLETE
SILENT_PAGE_DELETE = 0
HIGH_RISK_SOD_RULE = PRESENT
SELF_APPROVAL = FORBIDDEN
OWNER_OVERRIDE = CONTROLLED_OR_CONTRACT_GAP
P0_WITH_UNRESOLVED_CONTRACT_GAP = 0
HIGH_RISK_STATE_MODEL = PRESENT
RESULT_UNKNOWN = PRESENT
AGENT_SCOPE = FAIL_CLOSED
AI_REAL_EXECUTION = DISABLED
PACKAGE_QA_EVIDENCE_LEVEL = SEPARATED
CHINESE_UI_LOCALIZATION = PASS_WITH_FINDINGS
```

```text
V2_4_1_DOCUMENT_STATUS = READY_FOR_INDEPENDENT_REVIEW
```

> ⚠ 这仍不等于 MERGED_INTO_04，也不等于 PRODUCTION_READY。下一步是独立审核。

---

## 15. 配套文件清单

本包输出了以下文件：

| 文件 | 内容 |
|---|---|
| `Gainode_Admin_Prototype_Planning_V2.4.1_CN.md` | 本文档 — 主规划文件 |
| `GAINODE_ADMIN_PAGE_MAP_V2.4.1.md` | 8 Root Page Map |
| `GAINODE_ADMIN_NAVIGATION_MIGRATION_V2.4_TO_V2.4.1.md` | 14→8 导航迁移矩阵 |
| `GAINODE_ADMIN_PAGE_ID_MIGRATION_MATRIX_V2.4.1.md` | 全量 Page ID 迁移矩阵 |
| `GAINODE_ADMIN_CONTRACT_GAP_REGISTER_V2.4.1.md` | Contract Gap 登记册 |
| `GAINODE_ADMIN_HIFI_INTERACTION_SPEC_V2.4.1_CN.md` | 更新后的交互规格（引用 V2.4 + V2.4.1 修正） |
| `GAINODE_ADMIN_PERMISSION_MATRIX_V2.4.1_CN.md` | 更新后的权限矩阵（含 SoD 规则） |
| `GAINODE_ADMIN_V2.4.1_CHANGELOG.md` | 变更日志 |
| `GAINODE_ADMIN_V2.4.1_SELF_CHECK.md` | 自检报告 |
| `GAINODE_ADMIN_SPEC_INDEPENDENT_REVIEW_PROMPT_V1.1.md` | 更新后的独立审核提示词（含 4 硬 Gate） |
| `PACKAGE_QA_V2.4.1.md` | Evidence-Based QA |
| `README.md` | 包说明 |
