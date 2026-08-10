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

所有具有 Write Action 的页面必须增加以下 State（V2.4 仅有 6 个基础 State）：

- SUBMITTING / PROCESSING / UNDER_REVIEW（异步操作）
- RESULT_UNKNOWN（客户端不知道结果）
- STATE_CHANGED（数据在操作中被他人修改）
- CONFLICT / EXPIRED

### 0.3 SoD 规则

所有高风险操作的确认按钮必须走 Proposal → Approval 路径。详见 `GAINODE_ADMIN_PERMISSION_MATRIX_V2.4.1_CN.md`。

### 0.4 中文 UI

V2.4 中英文混合的页面标题和按钮文案改为纯中文。锁定词（Gainode / Robot / APT / OTC / Power / MFA / KYC / AI）保留英文。

---

## 1. 逐页交互修正（仅列出变更页面）

### A-USER-004 用户资产调整（重大修正）

V2.4 原始流程中缺少 Proposal Status / Requester / Approver / Execution Status。

**修正**：

新增字段：
- Proposal Status（Draft / Submitted / Under Review / Approved / Executed / Failed）
- Requester ID（发起人）
- Approver ID（审批人，必须 ≠ Requester）
- Approval Requirement（审批条件）
- Execution Status（执行结果）
- Idempotency Reference（幂等参考）

修正流程：
```text
Create Proposal
→ Validation
→ Impact Preview
→ Reason / Evidence
→ MFA + UID Confirm
→ Submit for Approval
→ Independent Approval（Requester ≠ Approver）
→ Execution
→ Processing
→ Success / Failed / Result Unknown
→ Ledger Entry + Audit
```

禁止：点击"确认"直接调整余额。

### A-CONFIG-002 参数发布与快照（重大修正）

V2.4 中缺少完整生命周期和 Creator ≠ Approver 规则。

**修正**：

完整生命周期：
```text
Draft → Review → Approved → Scheduled → Active → Paused → Rolled Back → Failed → Unknown
```

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
P0_WITH_CONTRACT_GAP = 0--------------------------------PASS
SELF_APPROVAL = FORBIDDEN-------------------------------PASS
HIGH_RISK_WRITE_STATE_MODEL = PRESENT-------------------PASS
RESULT_UNKNOWN = PRESENT--------------------------------PASS
CHINESE_UI_LOCALIZATION = PASS_WITH_FINDINGS------------PASS

剩余 Gate 同 V2.4
```
