# Design: Machine Contract 第二批 2B-2（合同补齐）

## 状态

- **Owner Signoff：未完成（3 缺 enum 对象 Owner Decision Matrix 待裁决）**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE（未 FROZEN）**

## 权威依据与角色（05 canonical）

- 角色（05 §8，MC2 §2 已确认）：`END_USER`、`SUPPORT_AGENT`、`OPS_OPERATOR`、`KYC_REVIEWER`、`RISK_ANALYST`、`RISK_APPROVER`、`LEDGER_OPERATOR`、`FINANCE_REVIEWER`、`PARAM_EDITOR`、`PARAM_APPROVER`、`RELEASE_OPERATOR`、`AUDITOR`、`ADMIN_SECURITY`。
- 职责分离（05 §8/§11.3 已确认）：
  - **`PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR`**（参数编辑/批准/激活三人分离）。
  - **`RISK_ANALYST != RISK_APPROVER`**（风险分析与高危处置批准分离）。
  - 申请人不得审批本人申请。
- 冻结规则：本文件不新增 canonical state、不新增角色；复用对象 enum 复制自 05 §4/§2.2。

---

## Part A — 复用已有 canonical enum 的对象（不新增状态值）

### A.1 ApprovalRequest（enum 复制 05 §4 Approval）

> canonical enum（05:798）：`draft / pending / changes_requested / approved / rejected / executing / executed / failed`
> 语义（05 §4 Approval 运营/后台展示映射）：`draft`=创建未提交；`pending`=待审批；`changes_requested`=要求修改；`approved`=已批准待执行；`rejected`=驳回；`executing`=执行中；`executed`=已执行；`failed`=执行失败。

| 项 | 值 |
|---|---|
| 初态 | `draft` |
| TRUE_TERMINAL | `executed`（执行完成）、`rejected`（驳回终态） |
| RETRYABLE_TERMINAL | `failed`（可升级/重试，形成新执行对象，不覆盖旧 Approval） |
| INTERMEDIATE | `pending / changes_requested / approved / executing` |

**转移矩阵（候选，待 State Machine gate）**：

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 幂等 | 并发 | 审计 | 账本效果 |
|---|---|---|---|---|---|---|---|---|---|
| AR1 | `draft` → `pending` | APPROVAL_SUBMITTED | 申请人（END_USER/OPS/ADMIN） | 申请对象存在、字段完整 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AR2 | `pending` → `changes_requested` | CHANGES_REQUESTED | 审批人（PARAM_APPROVER/RISK_APPROVER） | 审批人 ≠ 申请人 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AR3 | `changes_requested` → `pending` | RESUBMITTED | 申请人 | 修改后重新提交，不篡改原审批记录 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AR4 | `pending` → `approved` | APPROVED | 审批人 | 审批人 ≠ 申请人 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AR5 | `pending` → `rejected` | REJECTED | 审批人 | 审批人 ≠ 申请人 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AR6 | `approved` → `executing` | EXECUTION_STARTED | 系统/审批人 | 批准完成，触发执行 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无（副作用在 executed） |
| AR7 | `executing` → `executed` | EXECUTION_COMPLETED | 系统 | 执行成功 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 依 request_type（如 ParameterRelease 激活） |
| AR8 | `executing` → `failed` | EXECUTION_FAILED | 系统 | 执行异常 | ApprovalService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无（冻结，不半执行） |

**非法转移（FAIL_CLOSED）**：`executed → *`；`rejected → *`（重新提交需新 Approval）；`approved → changes_requested`；跨状态越级（如 `draft → approved`）。

### A.2 ParameterRelease（enum 复制 05 §4 Parameter Release）

> canonical enum（05:801）：`draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived`
> 语义（05 §4 ParameterRelease 运营/后台展示映射）：`approved ≠ active`；`scheduled`=已排期；`active`=已生效；`paused`=暂停；`rolled_back`=回滚到上一版本；`archived`=归档。
> 关键不变量：**`approved` 不等于 `active`**，批准后可排期延迟生效；历史对象使用 snapshot（`ParameterSnapshot`）。

| 项 | 值 |
|---|---|
| 初态 | `draft` |
| TRUE_TERMINAL | `archived`（不可再激活，仅审计查询） |
| STABLE | `active`（可 `paused`/`rolled_back`） |
| INTERMEDIATE | `pending_approval / approved / scheduled / paused` |

**转移矩阵（候选，待 State Machine gate）**：

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 幂等 | 并发 | 审计 | 账本效果 |
|---|---|---|---|---|---|---|---|---|---|
| PR1 | `draft` → `pending_approval` | RELEASE_SUBMITTED | PARAM_EDITOR | 参数值完整、diff 有效 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| PR2 | `pending_approval` → `approved` | RELEASE_APPROVED | PARAM_APPROVER | **PARAM_APPROVER ≠ PARAM_EDITOR** | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| PR3 | `pending_approval` → `changes_requested` | CHANGES_REQUESTED | PARAM_APPROVER | 同 AR2 语义 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| PR4 | `changes_requested` → `pending_approval` | RESUBMITTED | PARAM_EDITOR | 修改后重提 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| PR5 | `approved` → `scheduled` | RELEASE_SCHEDULED | RELEASE_OPERATOR | **RELEASE_OPERATOR ≠ PARAM_APPROVER**；scheduled_at 有效 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| PR6 | `approved` → `active` | RELEASE_ACTIVATED | RELEASE_OPERATOR | **RELEASE_OPERATOR ≠ PARAM_APPROVER**；批准后可直接激活（无排期） | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 生成 `ParameterSnapshot`（active snapshot） |
| PR7 | `scheduled` → `active` | RELEASE_ACTIVATED | 系统（定时）/RELEASE_OPERATOR 提前激活 | scheduled_at 到期或提前激活 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 生成 `ParameterSnapshot` |
| PR8 | `active` → `paused` | RELEASE_PAUSED | RELEASE_OPERATOR | 临时停用；暂停不删除历史 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无（snapshot 保留） |
| PR9 | `paused` → `active` | RELEASE_RESUMED | RELEASE_OPERATOR | 恢复 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| PR10 | `active`/`paused` → `rolled_back` | RELEASE_ROLLED_BACK | RELEASE_OPERATOR | 回滚到上一版本；保留审计链 + 新 Snapshot | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 生成回滚 `ParameterSnapshot` |
| PR11 | `rolled_back`/`scheduled` → `archived` | RELEASE_ARCHIVED | RELEASE_OPERATOR | 不可再激活 | ParameterReleaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |

**非法转移（FAIL_CLOSED）**：`archived → *`；`rolled_back → active`；`active → approved`；`pending_approval → scheduled`（必须经 `approved`）。

### A.3 AuthSession（enum 复制 05 §2.2）

> canonical enum（05 §2.2）：`active / mfa_required / restricted / expired / revoked`

| 项 | 值 |
|---|---|
| 初态 | `active`（登录成功；MFA 未验证则 `mfa_required`） |
| TRUE_TERMINAL | `expired`（自然到期）、`revoked`（主动撤销） |
| INTERMEDIATE | `mfa_required / restricted` |

**转移矩阵（候选，待 State Machine gate）**：

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 幂等 | 并发 | 审计 | 账本效果 |
|---|---|---|---|---|---|---|---|---|---|
| AS1 | `active` → `mfa_required` | MFA_CHALLENGE_REQUIRED | 系统 | 敏感操作触发 MFA | AuthSessionService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AS2 | `mfa_required` → `active` | MFA_VERIFIED | END_USER | MFA challenge 通过 | AuthSessionService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AS3 | `active` → `restricted` | SESSION_RESTRICTED | ADMIN_SECURITY/系统 | 安全策略触发 | AuthSessionService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AS4 | `restricted` → `active` | SESSION_UNRESTRICTED | ADMIN_SECURITY | 解除限制 | AuthSessionService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AS5 | `active`/`mfa_required`/`restricted` → `expired` | SESSION_EXPIRED | 系统 | expires_at 到期 | AuthSessionService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| AS6 | `active`/`mfa_required`/`restricted` → `revoked` | SESSION_REVOKED | END_USER（本人）或 ADMIN_SECURITY | 主动退出/强制下线 | AuthSessionService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |

**非法转移（FAIL_CLOSED）**：`expired → *`；`revoked → *`；`restricted → mfa_required`（不经 MFA challenge）。

### A.4 KycCase（enum 复制 05 §4 KYC）

> canonical enum（05:738）：`not_started / pending / needs_info / approved / rejected / review`

| 项 | 值 |
|---|---|
| 初态 | `not_started`（未发起 KYC）；`pending`（已提交） |
| TRUE_TERMINAL | `approved`（通过）、`rejected`（驳回） |
| RETRYABLE | `needs_info`（补件后可回 `pending`/`review`） |
| INTERMEDIATE | `review`（人工复核） |

**转移矩阵（候选，待 State Machine gate）**：

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 幂等 | 并发 | 审计 | 账本效果 |
|---|---|---|---|---|---|---|---|---|---|
| KC1 | `not_started` → `pending` | KYC_SUBMITTED | END_USER | 资料完整 | KycCaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| KC2 | `pending` → `review` | KYC_IN_REVIEW | 系统/KYC_REVIEWER | 进入人工复核 | KycCaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| KC3 | `pending` → `needs_info` | KYC_NEEDS_INFO | KYC_REVIEWER | 资料不足 | KycCaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| KC4 | `needs_info` → `pending` | KYC_RESUBMITTED | END_USER | 补充资料后重提 | KycCaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| KC5 | `review` → `approved` | KYC_APPROVED | KYC_REVIEWER | 复核通过；**reviewer ≠ 申请人** | KycCaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| KC6 | `review` → `rejected` | KYC_REJECTED | KYC_REVIEWER | 复核驳回；**reviewer ≠ 申请人** | KycCaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| KC7 | `review` → `needs_info` | KYC_NEEDS_INFO | KYC_REVIEWER | 复核中要求补件 | KycCaseService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |

**非法转移（FAIL_CLOSED）**：`approved → *`；`rejected → *`；`needs_info → approved`（必须回 `pending` 或 `review` 重走）。

### A.5 Ticket（enum 复制 05 §4 Ticket）

> canonical enum（05:795）：`submitted / in_progress / waiting_user / under_review / resolved / closed`

| 项 | 值 |
|---|---|
| 初态 | `submitted`（已创建） |
| TRUE_TERMINAL | `closed`（关闭） |
| STABLE | `resolved`（已解决，可重开或关闭） |
| INTERMEDIATE | `in_progress / waiting_user / under_review` |

**转移矩阵（候选，待 State Machine gate）**：

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 幂等 | 并发 | 审计 | 账本效果 |
|---|---|---|---|---|---|---|---|---|---|
| TK1 | `submitted` → `in_progress` | TICKET_ACCEPTED | SUPPORT_AGENT | 分配处理人 | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| TK2 | `in_progress` → `waiting_user` | TICKET_WAITING_USER | SUPPORT_AGENT | 等待用户回复 | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| TK3 | `waiting_user` → `in_progress` | TICKET_USER_REPLIED | END_USER | 用户回复 | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| TK4 | `in_progress` → `under_review` | TICKET_UNDER_REVIEW | SUPPORT_AGENT | 升级复核 | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| TK5 | `under_review` → `resolved` | TICKET_RESOLVED | SUPPORT_AGENT | 复核通过 | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| TK6 | `in_progress`/`under_review` → `resolved` | TICKET_RESOLVED | SUPPORT_AGENT | 问题解决 | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| TK7 | `resolved` → `closed` | TICKET_CLOSED | 系统/SUPPORT_AGENT | 确认关闭（超时自动或人工） | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |
| TK8 | `resolved` → `in_progress` | TICKET_REOPENED | END_USER/SUPPORT_AGENT | appeal_eligible 且用户要求重开 | TicketService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无 |

**非法转移（FAIL_CLOSED）**：`closed → *`；`resolved → waiting_user`；`submitted → resolved`（必须经处理）。

---

## Part B — 值对象 / 只读聚合（无状态机）

| 对象 | 类型 | 说明 |
|---|---|---|
| ParameterSnapshot | 只读聚合/Projection | 无 `status`；`snapshot_id` 关联 `ParameterRelease`，只追加不可变 |
| Notice | 只读聚合/Projection | 无状态机；`read_state`（read/unread）为字段；通知正文 I18N key 映射 |
| TicketMessage | 值对象 | 无 `status`；`sender_role` 为字段；仅追加不可变 |
| TicketAttachment | 值对象 | 无 `status`；仅追加不可变 |
| SettlementMethod | 值对象/只读聚合 | 无状态机；`verification_status` 为字段；`is_default` 为字段 |

---

## Part C — 3 缺 enum 对象 Owner Decision Matrix

> 依据 Owner 裁决先例（2B1-ENUM-01..06）：缺 enum 对象的 status **冻结前须补进 05 §4**（走 05 变更流程），否则 **FAIL_CLOSED 不建表**。
> 以下 D.1-D.3 生成 Owner Decision Matrix，`RECOMMENDED_OPTION` 待 Owner 裁决；裁决后补入 05 §4（V2.4），再 S01-P05 建 DDL。

### D.1 NotificationDelivery（`notification_deliveries`）

```text
DECISION_ID = 2B2-ENUM-01
DECISION_REQUIRED = NotificationDelivery.delivery_status 的 canonical enum
AFFECTED_PACKAGE = S01-P05（2B-2 DDL）
AFFECTED_OBJECTS = notification_deliveries
CURRENT_AUTHORITY = Owner（05 §4 未定义；05 §3 有 delivery_status 无 enum）
MISSING_DECISION = canonical enum 值集
OPTION_A = pending / delivered / failed / cancelled
OPTION_B = pending / processing / delivered / failed / cancelled（含 in-flight processing）
RECOMMENDED_OPTION = OPTION_A（outbox 投递：pending=待投递；delivered=成功；failed=失败待重试；cancelled=业务对象失效/用户已读不再投递；attempt_count/next_retry_at 已表达重试，无需 processing 态）
RISK_OF_EACH_OPTION = A：无显式 in-flight，需靠 attempt_count 表达；B：processing 与 dedupe_key 幂等重叠，增加状态面
SAFE_WORK_CONTINUING = 其余对象（Approval/Parameter/Auth/KYC/Ticket）不受阻塞
RESUME_CONDITION = Owner 裁决 enum 后补 05 §4，再 S01-P05 建 DDL
```

### D.2 MfaEnrollment（`mfa_enrollments`）

```text
DECISION_ID = 2B2-ENUM-02
DECISION_REQUIRED = MfaEnrollment.status 的 canonical enum
AFFECTED_PACKAGE = S01-P05
AFFECTED_OBJECTS = mfa_enrollments
CURRENT_AUTHORITY = Owner（05 §4 未定义；05 §3 有 status 无 enum）
MISSING_DECISION = canonical enum 值集
OPTION_A = pending / active / revoked
OPTION_B = active / revoked（两态，无 pending 验证态）
RECOMMENDED_OPTION = OPTION_A（MFA 注册需先发起 challenge 验证（pending），验证通过才 active；revoked=用户移除或安全吊销；backup_codes_active 为字段非状态）
RISK_OF_EACH_OPTION = A：pending 语义需明确是否含"已验证未生效"；B：无法表达"注册中未验证"，可能与验证失败混淆
SAFE_WORK_CONTINUING = 不受阻塞
RESUME_CONDITION = Owner 裁决 enum 后补 05 §4，再 S01-P05 建 DDL
```

### D.3 RiskCase（`risk_cases`）

```text
DECISION_ID = 2B2-ENUM-03
DECISION_REQUIRED = RiskCase.status 的 canonical enum
AFFECTED_PACKAGE = S01-P05
AFFECTED_OBJECTS = risk_cases
CURRENT_AUTHORITY = Owner（05 §4 未定义；05 §3 有 status 无 enum）
MISSING_DECISION = canonical enum 值集
OPTION_A = open / investigating / under_review / resolved / closed
OPTION_B = open / pending_review / approved / rejected / closed（复用简化审批流）
RECOMMENDED_OPTION = OPTION_A（风控案件生命周期：open=检测到；investigating=RISK_ANALYST 分析；under_review=RISK_APPROVER 审批处置；resolved=已处置；closed=终态归档；appeal_eligible 表达申诉）
RISK_OF_EACH_OPTION = A：resolved vs closed 边界需定义（appeal 窗口）；B：丢失 investigating 分析阶段，RISK_ANALYST 与 RISK_APPROVER 职责边界模糊
SAFE_WORK_CONTINUING = 不受阻塞
RESUME_CONDITION = Owner 裁决 enum 后补 05 §4，再 S01-P05 建 DDL
```

---

## Part D — 跨对象协同（对齐 05 §4 / §11 已冻结规则）

| 规则 | 依据 |
|---|---|
| `PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR` | 05 §8/§11.3 |
| `RISK_ANALYST != RISK_APPROVER`，申请人不得审批本人申请 | 05 §8/§11.3 |
| Notice 与业务事务解耦；NotificationDelivery 失败不回滚业务 | 05 §4 Notice 设计原则 1 |
| NotificationDelivery 通过 Outbox/异步投递重试；dedupe_key 去重 | 05 §4 Notice 设计原则 2/3 |
| 通知深链关联对象；无权限/对象失效仍可安全查看正文 | 05 §4 Notice 设计原则 4/5 |
| Parameter `approved` ≠ `active`；历史对象使用 snapshot | 05 §4 ParameterRelease 约束 + 07 S01-P04 步骤 6 |
| 高风险详情不泄露内部风控规则 | 05 §4 Notice 安全 reason mapping |
| Approval 回滚不修改旧 Approval 状态，形成新执行对象 + 审计链 | 05 §4 Approval 约束 |

---

## 通用工程约束（S01-P05 建 DDL/骨架时落实）

| 维度 | 约束 |
|---|---|
| 主键 | Snowflake bigint unsigned（`$incrementing=false`，`$keyType='string'`） |
| 并发 | 每表 `object_version int unsigned` CAS 乐观锁 |
| 幂等 | 每表 `idempotency_key varchar(64) UNIQUE` 可空 |
| 审计 | 敏感写表 `audit_event_id` 指针 + append `audit_events` |
| 金额 | `decimal(36,18)`（APT）／`decimal(18,8)`（price/系数）／`decimal(18,4)`（Power） |
| 时间 | `created_time/updated_time` int unsigned（Unix 秒） |
| 状态列 | 领域状态用 ENUM（冻结 enum 后）；05 未定义前 `varchar(32) NULL` + FAIL_CLOSED |
| 失败安全 | 未冻结状态一律 FAIL_CLOSED，不建表、不写业务 |

## 信息来源

- 05 §2.2/§3/§4/§8/§11
- `.project-ai/tasks/TASK-20260816-001/design.md`（2B-1 Owner Decision Matrix 格式 + 职责分离先例）
- `.project-ai/tasks/TASK-20260815-001/design.md`（MC2 角色/协同）
