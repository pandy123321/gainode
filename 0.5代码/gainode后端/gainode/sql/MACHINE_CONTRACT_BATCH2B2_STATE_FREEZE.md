# Machine Contract 第二批 2B-2 — State Contract Freeze（候选）

> 状态：**CANDIDATE（未 FROZEN）** — Owner Signoff ⏳（3 缺 enum 对象 NotificationDelivery/MfaEnrollment/RiskCase 待 Owner 裁决）；Independent Review 未开始。
> 说明：本文件为 Machine Contract 第二批 **2B-2 小批**（13 对象）的状态合同冻结候选。5 个复用对象（ApprovalRequest/ParameterRelease/AuthSession/KycCase/Ticket）enum 复制 05 §4/§2.2（已冻结）；3 缺 enum 对象待 Owner 裁决后补入 05 §4；5 个值对象/只读聚合无状态机。转移矩阵均为**候选**，正式 FROZEN 前须重提 Independent Review（State Machine gate）并通过。
> 起草日期：2026-08-16
> 关联 DDL：无（本批不生成 DDL，属 S01-P05）
> 权威契约：`Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§2.2 Session 状态 / §3 对象字段 / §4 统一状态机 / §8 RBAC / §11 SoD）
> 前置冻结：`MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`（MC1）、`MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（MC2，协同关系权威源）、`MACHINE_CONTRACT_BATCH2B1_STATE_FREEZE.md`（2B-1）
> 任务：`.project-ai/tasks/TASK-20260816-003/`

## 1. 冻结范围

本批冻结 **2B-2 小批 13 个对象的状态合同**（enum + 状态转移矩阵候选）。冻结后，这 13 个对象的状态流转由本文件授权，非法流转 FAIL_CLOSED。

| 对象 | 类型 | enum 来源 | 状态 |
|---|---|---|---|
| ApprovalRequest | 工作流对象 | 复制 05 §4 Approval `draft/pending/changes_requested/approved/rejected/executing/executed/failed` | 候选（转移矩阵待 gate） |
| ParameterRelease | 工作流对象 | 复制 05 §4 Parameter Release `draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived` | 候选（转移矩阵待 gate） |
| ParameterSnapshot | 只读聚合/Projection | 无状态机 | 无状态机 |
| Notice | 只读聚合/Projection | 无状态机（read_state 为字段） | 无状态机 |
| NotificationDelivery | 工作流对象 | **待 Owner 裁决（2B2-ENUM-01）** | FAIL_CLOSED（未裁决前不建表） |
| AuthSession | 持久领域实体 | 复制 05 §2.2 `active/mfa_required/restricted/expired/revoked` | 候选（转移矩阵待 gate） |
| MfaEnrollment | 持久领域实体 | **待 Owner 裁决（2B2-ENUM-02）** | FAIL_CLOSED（未裁决前不建表） |
| KycCase | 工作流对象 | 复制 05 §4 KYC `not_started/pending/needs_info/approved/rejected/review` | 候选（转移矩阵待 gate） |
| RiskCase | 工作流对象 | **待 Owner 裁决（2B2-ENUM-03）** | FAIL_CLOSED（未裁决前不建表） |
| Ticket | 工作流对象 | 复制 05 §4 Ticket `submitted/in_progress/waiting_user/under_review/resolved/closed` | 候选（转移矩阵待 gate） |
| TicketMessage | 值对象 | 无状态机 | 无状态机 |
| TicketAttachment | 值对象 | 无状态机 | 无状态机 |
| SettlementMethod | 值对象/只读聚合 | 无状态机（verification_status 为字段） | 无状态机 |

**不包含**（拆出本批，另行交付）：
- 2B-2 的 DDL（属 S01-P05）。
- 非持久投影服务（S01-P06）。
- Affiliate/Agent（S01-P07）。

## 2. 角色映射（05 §8 canonical，不自创角色）

- ApprovalRequest 申请人 → **END_USER / OPS_OPERATOR / ADMIN_SECURITY**（依 request_type）；审批人 → **PARAM_APPROVER / RISK_APPROVER**（依 request_type）；**审批人 ≠ 申请人**。
- ParameterRelease 编辑 → **PARAM_EDITOR**；批准 → **PARAM_APPROVER**；激活/暂停/回滚/归档 → **RELEASE_OPERATOR**。**三者互斥（SoD）**。
- AuthSession 撤销 → **END_USER（本人）/ ADMIN_SECURITY（强制）**；restricted → **ADMIN_SECURITY**。
- KycCase 复核 → **KYC_REVIEWER**；**reviewer ≠ 申请人**。
- RiskCase 分析 → **RISK_ANALYST**；处置批准 → **RISK_APPROVER**。**两者互斥（SoD）**。
- Ticket 处理 → **SUPPORT_AGENT**；用户回复 → **END_USER**。
- 对账只读 → **FINANCE_REVIEWER**（不可写）。
- 审计只读 → **AUDITOR**。

> ⚠️ 职责分离提醒：本项目 11 角色由 OWNER 单人兼任（manifest `p1_004_owner_freeze`）。系统层面角色分离仍成立；若同一自然人同时持有互斥角色并自审自批，须满足 `p1_010_override_contract`。

## 3. ApprovalRequest 状态合同

> canonical enum（05 §4，已冻结）：`draft / pending / changes_requested / approved / rejected / executing / executed / failed`
> 关键不变量：**Approval 回滚不修改旧 Approval 状态**，形成新执行对象 + 审计链；`executing` ≠ `executed`。

### 3.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `draft` |
| TRUE_TERMINAL | `executed`（执行完成）、`rejected`（驳回） |
| RETRYABLE_TERMINAL | `failed`（可升级/重试，形成新执行对象） |
| INTERMEDIATE | `pending / changes_requested / approved / executing` |

### 3.2 转移矩阵（候选）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 账本效果 |
|---|---|---|---|---|---|---|
| AR1 | `draft` → `pending` | APPROVAL_SUBMITTED | 申请人 | 申请对象存在、字段完整 | ApprovalService | 无 |
| AR2 | `pending` → `changes_requested` | CHANGES_REQUESTED | 审批人 | 审批人 ≠ 申请人 | ApprovalService | 无 |
| AR3 | `changes_requested` → `pending` | RESUBMITTED | 申请人 | 修改后重提，不篡改原审批记录 | ApprovalService | 无 |
| AR4 | `pending` → `approved` | APPROVED | 审批人 | 审批人 ≠ 申请人 | ApprovalService | 无 |
| AR5 | `pending` → `rejected` | REJECTED | 审批人 | 审批人 ≠ 申请人 | ApprovalService | 无 |
| AR6 | `approved` → `executing` | EXECUTION_STARTED | 系统/审批人 | 批准完成 | ApprovalService | 无 |
| AR7 | `executing` → `executed` | EXECUTION_COMPLETED | 系统 | 执行成功 | ApprovalService | 依 request_type |
| AR8 | `executing` → `failed` | EXECUTION_FAILED | 系统 | 执行异常 | ApprovalService | 无（冻结，不半执行） |

**非法转移（FAIL_CLOSED）**：`executed → *`、`rejected → *`（重提需新 Approval）、`approved → changes_requested`、`draft → approved`（越级）。

## 4. ParameterRelease 状态合同

> canonical enum（05 §4，已冻结）：`draft / pending_approval / approved / scheduled / active / paused / rolled_back / archived`
> 关键不变量：**`approved` ≠ `active`**（批准后可排期延迟生效）；历史对象使用 `ParameterSnapshot`。

### 4.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `draft` |
| TRUE_TERMINAL | `archived`（不可再激活，仅审计查询） |
| STABLE | `active`（可 `paused`/`rolled_back`） |
| INTERMEDIATE | `pending_approval / approved / scheduled / paused` |

### 4.2 转移矩阵（候选）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 账本效果 |
|---|---|---|---|---|---|---|
| PR1 | `draft` → `pending_approval` | RELEASE_SUBMITTED | PARAM_EDITOR | 参数值完整、diff 有效 | ParameterReleaseService | 无 |
| PR2 | `pending_approval` → `approved` | RELEASE_APPROVED | PARAM_APPROVER | PARAM_APPROVER ≠ PARAM_EDITOR | ParameterReleaseService | 无 |
| PR3 | `pending_approval` → `changes_requested` | CHANGES_REQUESTED | PARAM_APPROVER | 同 AR2 | ParameterReleaseService | 无 |
| PR4 | `changes_requested` → `pending_approval` | RESUBMITTED | PARAM_EDITOR | 修改后重提 | ParameterReleaseService | 无 |
| PR5 | `approved` → `scheduled` | RELEASE_SCHEDULED | RELEASE_OPERATOR | RELEASE_OPERATOR ≠ PARAM_APPROVER | ParameterReleaseService | 无 |
| PR6 | `approved` → `active` | RELEASE_ACTIVATED | RELEASE_OPERATOR | RELEASE_OPERATOR ≠ PARAM_APPROVER | ParameterReleaseService | 生成 active `ParameterSnapshot` |
| PR7 | `scheduled` → `active` | RELEASE_ACTIVATED | 系统（定时）/RELEASE_OPERATOR 提前 | scheduled_at 到期或提前激活 | ParameterReleaseService | 生成 `ParameterSnapshot` |
| PR8 | `active` → `paused` | RELEASE_PAUSED | RELEASE_OPERATOR | 临时停用，不删历史 | ParameterReleaseService | 无 |
| PR9 | `paused` → `active` | RELEASE_RESUMED | RELEASE_OPERATOR | 恢复 | ParameterReleaseService | 无 |
| PR10 | `active`/`paused` → `rolled_back` | RELEASE_ROLLED_BACK | RELEASE_OPERATOR | 回滚到上一版本，保留审计链 | ParameterReleaseService | 生成回滚 `ParameterSnapshot` |
| PR11 | `rolled_back`/`scheduled` → `archived` | RELEASE_ARCHIVED | RELEASE_OPERATOR | 不可再激活 | ParameterReleaseService | 无 |

**非法转移（FAIL_CLOSED）**：`archived → *`、`rolled_back → active`、`active → approved`、`pending_approval → scheduled`（必须经 `approved`）。

## 5. AuthSession 状态合同

> canonical enum（05 §2.2，已冻结）：`active / mfa_required / restricted / expired / revoked`

### 5.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `active`（登录成功）；MFA 未验证则 `mfa_required` |
| TRUE_TERMINAL | `expired`（自然到期）、`revoked`（主动撤销） |
| INTERMEDIATE | `mfa_required / restricted` |

### 5.2 转移矩阵（候选）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 账本效果 |
|---|---|---|---|---|---|---|
| AS1 | `active` → `mfa_required` | MFA_CHALLENGE_REQUIRED | 系统 | 敏感操作触发 MFA | AuthSessionService | 无 |
| AS2 | `mfa_required` → `active` | MFA_VERIFIED | END_USER | MFA challenge 通过 | AuthSessionService | 无 |
| AS3 | `active` → `restricted` | SESSION_RESTRICTED | ADMIN_SECURITY/系统 | 安全策略触发 | AuthSessionService | 无 |
| AS4 | `restricted` → `active` | SESSION_UNRESTRICTED | ADMIN_SECURITY | 解除限制 | AuthSessionService | 无 |
| AS5 | `active/mfa_required/restricted` → `expired` | SESSION_EXPIRED | 系统 | expires_at 到期 | AuthSessionService | 无 |
| AS6 | `active/mfa_required/restricted` → `revoked` | SESSION_REVOKED | END_USER（本人）/ADMIN_SECURITY | 主动退出/强制下线 | AuthSessionService | 无 |

**非法转移（FAIL_CLOSED）**：`expired → *`、`revoked → *`、`restricted → mfa_required`。

## 6. KycCase 状态合同

> canonical enum（05 §4，已冻结）：`not_started / pending / needs_info / approved / rejected / review`

### 6.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `not_started`（未发起）、`pending`（已提交） |
| TRUE_TERMINAL | `approved`（通过）、`rejected`（驳回） |
| RETRYABLE | `needs_info`（补件后回 `pending`/`review`） |
| INTERMEDIATE | `review`（人工复核） |

### 6.2 转移矩阵（候选）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 账本效果 |
|---|---|---|---|---|---|---|
| KC1 | `not_started` → `pending` | KYC_SUBMITTED | END_USER | 资料完整 | KycCaseService | 无 |
| KC2 | `pending` → `review` | KYC_IN_REVIEW | 系统/KYC_REVIEWER | 进入人工复核 | KycCaseService | 无 |
| KC3 | `pending` → `needs_info` | KYC_NEEDS_INFO | KYC_REVIEWER | 资料不足 | KycCaseService | 无 |
| KC4 | `needs_info` → `pending` | KYC_RESUBMITTED | END_USER | 补资料后重提 | KycCaseService | 无 |
| KC5 | `review` → `approved` | KYC_APPROVED | KYC_REVIEWER | reviewer ≠ 申请人 | KycCaseService | 无 |
| KC6 | `review` → `rejected` | KYC_REJECTED | KYC_REVIEWER | reviewer ≠ 申请人 | KycCaseService | 无 |
| KC7 | `review` → `needs_info` | KYC_NEEDS_INFO | KYC_REVIEWER | 复核中要求补件 | KycCaseService | 无 |

**非法转移（FAIL_CLOSED）**：`approved → *`、`rejected → *`、`needs_info → approved`（必须回 `pending` 或 `review`）。

## 7. Ticket 状态合同

> canonical enum（05 §4，已冻结）：`submitted / in_progress / waiting_user / under_review / resolved / closed`

### 7.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `submitted` |
| TRUE_TERMINAL | `closed` |
| STABLE | `resolved`（可重开或关闭） |
| INTERMEDIATE | `in_progress / waiting_user / under_review` |

### 7.2 转移矩阵（候选）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 账本效果 |
|---|---|---|---|---|---|---|
| TK1 | `submitted` → `in_progress` | TICKET_ACCEPTED | SUPPORT_AGENT | 分配处理人 | TicketService | 无 |
| TK2 | `in_progress` → `waiting_user` | TICKET_WAITING_USER | SUPPORT_AGENT | 等待用户回复 | TicketService | 无 |
| TK3 | `waiting_user` → `in_progress` | TICKET_USER_REPLIED | END_USER | 用户回复 | TicketService | 无 |
| TK4 | `in_progress` → `under_review` | TICKET_UNDER_REVIEW | SUPPORT_AGENT | 升级复核 | TicketService | 无 |
| TK5 | `under_review` → `resolved` | TICKET_RESOLVED | SUPPORT_AGENT | 复核通过 | TicketService | 无 |
| TK6 | `in_progress`/`under_review` → `resolved` | TICKET_RESOLVED | SUPPORT_AGENT | 问题解决 | TicketService | 无 |
| TK7 | `resolved` → `closed` | TICKET_CLOSED | 系统/SUPPORT_AGENT | 确认关闭 | TicketService | 无 |
| TK8 | `resolved` → `in_progress` | TICKET_REOPENED | END_USER/SUPPORT_AGENT | appeal_eligible 且要求重开 | TicketService | 无 |

**非法转移（FAIL_CLOSED）**：`closed → *`、`resolved → waiting_user`、`submitted → resolved`（必须经处理）。

## 8. 3 缺 enum 对象状态合同（待 Owner 裁决，FAIL_CLOSED）

> 依据 S01-P04 步骤 2，只为缺失状态生成 Owner Decision Matrix，不自创。以下 3 对象 enum **待 Owner 裁决**，未裁决前 FAIL_CLOSED 不建表。RECOMMENDED_OPTION 见 design.md Part C。

### 8.1 NotificationDelivery — 待裁决（2B2-ENUM-01）

```text
状态 = FAIL_CLOSED（未 Owner 裁决，不建表）
RECOMMENDED（候选）= pending / delivered / failed / cancelled（OPTION_A）
初态（候选）= pending
终态（候选）= delivered / failed / cancelled
触发者（候选）= 系统（Outbox/异步投递）
Writer = NotificationDeliveryService
幂等 = dedupe_key
审计 = append audit_events
账本副作用 = 无（通知投递，失败不回滚业务，05 §4 Notice 设计原则 1）
```

### 8.2 MfaEnrollment — 待裁决（2B2-ENUM-02）

```text
状态 = FAIL_CLOSED（未 Owner 裁决，不建表）
RECOMMENDED（候选）= pending / active / revoked（OPTION_A）
初态（候选）= pending（注册未验证）
终态（候选）= revoked
触发者（候选）= END_USER（注册/验证/移除）、ADMIN_SECURITY（吊销）
Writer = MfaEnrollmentService
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = 无
```

### 8.3 RiskCase — 待裁决（2B2-ENUM-03）

```text
状态 = FAIL_CLOSED（未 Owner 裁决，不建表）
RECOMMENDED（候选）= open / investigating / under_review / resolved / closed（OPTION_A）
初态（候选）= open（检测到）
终态（候选）= closed
触发者（候选）= RISK_ANALYST（分析 investigating）、RISK_APPROVER（处置审批 under_review）
Writer = RiskCaseService
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = 依 disposition（restrictions 冻结/放行，不直接写账）
```

## 9. 值对象 / 只读聚合（无状态机）

| 对象 | 说明 |
|---|---|
| ParameterSnapshot | 无 `status`；`snapshot_id` 关联 `ParameterRelease`，只追加不可变 |
| Notice | 无状态机；`read_state`（read/unread）为字段；正文 I18N key 映射，不暴露 raw reason_code |
| TicketMessage | 无 `status`；`sender_role` 为字段；仅追加不可变 |
| TicketAttachment | 无 `status`；仅追加不可变 |
| SettlementMethod | 无状态机；`verification_status`、`is_default` 为字段 |

## 10. 跨对象协同（对齐 05 §4 / §11 已冻结规则）

| 规则 | 依据 |
|---|---|
| `PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR` | 05 §8/§11.3 |
| `RISK_ANALYST != RISK_APPROVER`，申请人不得审批本人申请 | 05 §8/§11.3 |
| Notice 与业务事务解耦；NotificationDelivery 失败不回滚业务 | 05 §4 Notice 设计原则 1 |
| NotificationDelivery 通过 Outbox/异步投递重试；dedupe_key 去重 | 05 §4 Notice 设计原则 2/3 |
| 通知深链关联对象；无权限/对象失效仍可安全查看正文 | 05 §4 Notice 设计原则 4/5 |
| Parameter `approved` ≠ `active`；历史对象使用 snapshot | 05 §4 ParameterRelease 约束 |
| 高风险详情不泄露内部风控规则 | 05 §4 Notice 安全 reason mapping |
| Approval 回滚不修改旧 Approval 状态，形成新执行对象 + 审计链 | 05 §4 Approval 约束 |

## 11. 通用工程约束（S01-P05 建 DDL/骨架时落实）

| 维度 | 约束 |
|---|---|
| 主键 | Snowflake bigint unsigned（`$incrementing=false`，`$keyType='string'`） |
| 并发 | 每表 `object_version int unsigned` CAS 乐观锁（对应 05 If-Match / `OBJECT_VERSION_CONFLICT 409`） |
| 幂等 | 每表 `idempotency_key varchar(64) UNIQUE` 可空 |
| 审计 | 敏感写表 `audit_event_id` 指针 + append `audit_events` |
| 金额 | `decimal(36,18)`（APT 数量）／`decimal(18,8)`（price/系数）／`decimal(18,4)`（Power） |
| 时间 | `created_time/updated_time` int unsigned（Unix 秒） |
| 状态列 | 领域状态用 ENUM（冻结 enum 后）；05 未定义前 `varchar(32) NULL` + FAIL_CLOSED |
| 失败安全 | 转移矩阵未冻结一律 FAIL_CLOSED，不建表、不写业务 |

## 12. 冻结状态与 gate

```text
OWNER_SIGNOFF = PENDING（3 缺 enum 对象待 Owner 裁决）
05_SECTION4_SUPPLEMENT = NOT_DONE（待 Owner 裁决后补 V2.4）
INDEPENDENT_REVIEW = PENDING（State Machine gate）
FROZEN_STATUS = CANDIDATE
APPROVAL_ENUM = draft/pending/changes_requested/approved/rejected/executing/executed/failed（复制 05 §4，未新增）
PARAMETER_RELEASE_ENUM = draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived（复制 05 §4，未新增）
AUTH_SESSION_ENUM = active/mfa_required/restricted/expired/revoked（复制 05 §2.2，未新增）
KYC_ENUM = not_started/pending/needs_info/approved/rejected/review（复制 05 §4，未新增）
TICKET_ENUM = submitted/in_progress/waiting_user/under_review/resolved/closed（复制 05 §4，未新增）
OWNER_DECISION_MATRIX_COUNT = 3（NotificationDelivery/MfaEnrollment/RiskCase）
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_ROLE = YES
PARAM_ROLE_SEPARATION = YES
RISK_ROLE_SEPARATION = YES
NOTICE_DECOUPLED_FROM_BUSINESS = YES
PARAM_APPROVED_NOT_EQUAL_ACTIVE = YES
```

正式 FROZEN 前须：① 3 缺 enum 对象 Owner 裁决并补入 05 §4（V2.4）；② 重提 Independent Review（State Machine gate）并通过。

## 信息来源

- 05 §2.2（Session 状态）/§3（对象字段）/§4（统一状态机 + Notice 规则）/§8（RBAC）/§11（SoD）
- MC1 Freeze §3.6/§3.7/§3.9
- MC2 Freeze §3.4/§3.5/§3.6/§3.7/§5/§6
- `.project-ai/tasks/TASK-20260816-001/design.md`（2B-1 Owner Decision Matrix 格式 + 职责分离先例）
- `.project-ai/tasks/TASK-20260816-003/design.md`（Part A/B/C/D）
