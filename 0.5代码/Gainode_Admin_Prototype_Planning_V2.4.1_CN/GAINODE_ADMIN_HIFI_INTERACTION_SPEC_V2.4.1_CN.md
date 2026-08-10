# Gainode 中文运营后台高保真原型交互规格 V2.4.1

> **THIS_DOCUMENT_IS_AN_AMENDMENT_TO_V2.4**
> **BASE_SPEC = `../Gainode_Admin_Prototype_Planning_V2.4_CN/GAINODE_ADMIN_HIFI_INTERACTION_SPEC_V2.4_CN.md`**
>
> 本文件仅记录 V2.4 → V2.4.1 的交互规格变更。未提及的页面规格保持 V2.4 原文不变。

---

## 0. 全局变更

### 0.1 导航结构

V2.4 的 14 个一级导航在 V2.4.1 中恢复为 8 个。详见 `GAINODE_ADMIN_NAVIGATION_MIGRATION_V2.4_TO_V2.4.1.md`。

具体页面路由变更见 `GAINODE_ADMIN_PAGE_ID_MIGRATION_MATRIX_V2.4.1.md`。

### 0.2 State 模型升级

所有具有 Write Action 的页面必须增加以下 UI 操作状态（非领域状态）：

- SUBMITTING / PROCESSING / UNDER_REVIEW（异步操作）
- RESULT_UNKNOWN（客户端不知道请求结果 — 不等于领域对象状态 "Unknown"）
- STATE_CHANGED（数据在操作中被他人修改）
- CONFLICT / EXPIRED

> **关键区分**：
> - **DOMAIN_OBJECT_STATE**：对象在系统中的权威状态（对齐 05 canonical enum）
> - **UI_OPERATION_STATE**：页面操作的临时状态（SUBMITTING/PROCESSING/SUCCESS/FAILED）
> - **REQUEST_RESOLUTION_STATE**：客户端对请求结果的认知（KNOWN/RESULT_UNKNOWN）
>
> RESULT_UNKNOWN 不得作为任何领域对象的 Domain State。处理方式：用原 Idempotency-Key 查询结果（05 §7）。

### 0.3 SoD 规则

所有高风险操作的确认按钮必须走 Proposal → Approval 路径。详见 `GAINODE_ADMIN_PERMISSION_MATRIX_V2.4.1_CN.md`。

### 0.4 中文 UI

V2.4 中英文混合的页面标题和按钮文案改为纯中文。锁定词（Gainode / Robot / APT / OTC / Power / MFA / KYC / AI）保留英文。

---

## 1. 逐页交互修正（仅列出变更页面）

### A-USER-004 用户资产调整（重大修正）

V2.4 原始流程中缺少 Proposal Status / Requester / Approver / Execution Status。

> **权威状态**：05 中 AssetAdjustment 全链路 Object 未冻结（GAP-014）。本页面功能标记为 CONTRACT_GAP / P1_CONDITIONAL。以下字段仅作为 HIFI UI 候选占位，不构成 API Contract。

**修正**：

新增字段（全部标记为 UI_CANDIDATE_ONLY / NON_AUTHORITATIVE / NOT_API_CONTRACT）：

- Proposal Status（UI_CANDIDATE_ONLY — Draft / Submitted / Under Review / Approved / Executed / Failed；此枚举非 05 领域状态）
- Requester ID（UI_CANDIDATE_ONLY — 发起人，必须 ≠ Approver）
- Approver ID（UI_CANDIDATE_ONLY — 审批人，必须 ≠ Requester）
- Approval Requirement（UI_CANDIDATE_ONLY — 审批条件）
- Execution Status（UI_CANDIDATE_ONLY — 执行结果）
- Idempotency Reference（UI_CANDIDATE_ONLY — 幂等参考）

UI 操作状态（非领域状态）：
```text
SUBMITTING / PROCESSING / RESULT_UNKNOWN / SUCCESS / FAILED
```

修正流程（CONTRACT_GAP 期间仅作 UI Spec，不执行）：
```text
Create Proposal [PREVIEW_ONLY]
→ Validation [PREVIEW_ONLY]
→ Impact Preview [PREVIEW_ONLY]
→ Reason / Evidence [PREVIEW_ONLY]
→ MFA + UID Confirm [PREVIEW_ONLY]
→ Submit for Approval [DISABLED/CONTRACT_GAP]
→ Independent Approval [DISABLED/CONTRACT_GAP]
→ Execution [DISABLED/CONTRACT_GAP]
→ Processing [DISABLED/CONTRACT_GAP]
→ Success / Failed / Result Unknown [DISABLED/CONTRACT_GAP]
→ Ledger Entry + Audit [DISABLED/CONTRACT_GAP]
```

> 在 05 AssetAdjustment Contract 冻结前，A-USER-004 仅提供资产查看（Preview），不执行调整操作。

### A-CONFIG-002 参数发布与快照（重大修正）

V2.4 中缺少完整生命周期和 Creator ≠ Approver 规则。

**修正**：

#### 领域对象状态（DOMAIN_OBJECT_STATE — 必须对齐 05 §4 canonical enum）

```text
05_CANONICAL_PARAMETERRELEASE_STATE:
draft → pending_approval → approved → scheduled → active → paused → rolled_back → archived
```

> V2.4.1 不得自行加入 Review、Failed、Unknown 作为 ParameterRelease 领域状态。  
> `approved` ≠ `active`：批准后可排期延迟生效。  
> `archived` 仅供审计查询，不可再激活。

#### UI 操作状态（UI_OPERATION_STATE — 页面临时状态，非领域状态）

```text
SUBMITTING / PROCESSING / SUCCESS / FAILED
```

> `FAILED` 是操作执行失败，不是 ParameterRelease.status = Failed。领域对象保持操作前状态不变。

#### 请求结果状态（REQUEST_RESOLUTION_STATE — 客户端对请求结果的认知）

```text
KNOWN / RESULT_UNKNOWN
```

> RESULT_UNKNOWN 表示"客户端不知道请求最终结果"，不是 ParameterRelease Domain State 的 "Unknown"。  
> 处理方式：用原 Idempotency-Key 查询原请求结果（05 §7）。

#### 页面字段（UI 字段，非 API Contract）

新增字段：
- Proposal Creator（创建人）
- Reviewer（审核人）
- Approver（批准人，必须 ≠ Creator）
- Executor（执行人）
- Diff from Previous Version
- Impact Scope（影响范围）
- Affected Modules（受影响模块）
- Effective Time（生效时间）
- Rollback Target（回滚目标版本）
- Reason + Evidence + Audit

### A-PREDICT-004 结果/结算/退款/更正（重大修正）

**修正**：

State 增加：
- RESULT_UNKNOWN（异步结算）
- STATE_CHANGED（赛果在审批中被更新）

流程增加：
```text
Result Confirm → Settlement Calculation → Settlement Preview → Submit Approval → Independent Review → Execute Settlement → Success/Failed/Unknown → Reconciliation
```

### A-OTC-002 OTC 订单详情/审核（重大修正）

**修正**：

State 增加：
- STATE_CHANGED（订单状态在审核中变化）
- CONFLICT（多管理员并发审核）

修正确认按钮：
- 不再直接 Approve/Reject
- 改为 Submit Review → 显示 Impact → Confirm

### A-KYC-001 KYC 审核（重大修正）

**修正**：

State 增加：
- STATE_CHANGED（档案已被他人处理）
- CONFLICT（档案在审核中资料变更）

修正流程：Case → Evidence → Decision Preview → Reason → Confirm → Result。

### A-RISK-001 风险事件（重大修正）

**修正**：

State 增加：ESCALATED / PENDING_APPROVAL / EXPIRED

修正流程：Case → Evidence → Analysis → Recommendation → Approval → Action → Resolution。

### A-APPROVAL-001 审批中心（重大修正）

**修正**：

- 批准按钮必须验证 Requester != Approver
- 批准 → 显示 Processing → Execution Result（非即时 Success）
- 不得把 Approved 显示为 Executed

---

## 2. Agent Portal 每页 Spec 补充

### AG-HOME-001 代理首页

```
PAGE_ID = AG-HOME-001
PAGE_NAME = 代理首页
PRIORITY = P1_CONDITIONAL
ROOT/PORTAL = AGENT_PORTAL
GOAL = 代理查看自己代理域的用户增长、活跃、业务使用概览
DATA_SCOPE = 当前代理自己的代理域（FAIL_CLOSED）
LAYOUT = 统计摘要 + 用户列表 + 最近活动
KEY_FIELDS = 直属用户数、团队用户数、今日新增、活跃用户数、Robot 用户数、竞猜参与用户、OTC 使用用户、待处理工单
ALLOWED_ACTIONS = 查看用户、查看运营数据、打开工单
FORBIDDEN_ACTIONS = 查看全局经济模型、查看其他代理数据、修改推荐关系、调整用户资产
STATES = Loading / Content / Empty / Error / No Permission
```

（其余 AG-USER-001/002, AG-TEAM-001, AG-DATA-001, AG-SUPPORT-001, AG-ACCOUNT-001 同 V2.4 规格 + ALLOWED_ACTIONS / FORBIDDEN_ACTIONS / DATA_SCOPE = FAIL_CLOSED）

---

## 3. 页面注册表 Priority 更新

详见 `GAINODE_ADMIN_PAGE_MAP_V2.4.1.md`。

---

## 4. 验收 Gate 更新

```text
TOTAL_ADMIN_PAGE_REGISTERED = 51
AGENT_PORTAL_PAGE_REGISTERED = 7

ADMIN_ROOT_NAV_COUNT = 8--------------------------------PASS
P0_WITH_BLOCKING_CONTRACT_GAP = 0 [DERIVED]-------------PASS（A-USER-004 已降为 P1_CONDITIONAL）
SELF_APPROVAL = FORBIDDEN-------------------------------PASS
HIGH_RISK_WRITE_STATE_MODEL = PRESENT-------------------PASS
RESULT_UNKNOWN = PRESENT--------------------------------PASS
CHINESE_UI_LOCALIZATION = PASS_WITH_FINDINGS------------PASS

剩余 Gate 同 V2.4
```
