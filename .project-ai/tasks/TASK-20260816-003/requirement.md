# Requirement: Machine Contract 第二批 2B-2（合同补齐）

## 状态

- **Owner Signoff：未完成（本 task 产出 Owner Decision Matrix，待 Owner 裁决）**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE（未 FROZEN）**

## 背景

MC2（`TASK-20260815-001`）已冻结 8 个核心实体，并将非核心实体拆为 2B-1（P0，`TASK-20260816-001`）与 2B-2（P1/P2，本 task）。

2B-1（S01-P02/S01-P03）已覆盖 9 个 P0 对象。本 task（S01-P04）执行 **2B-2 状态合同补齐**，覆盖 P1/P2 的 13 个对象，为 S01-P05（2B-2 DDL 与骨架）提供冻结前的状态合同依据。

## 范围

固定对象（13 个）：

```text
ApprovalRequest
ParameterRelease
ParameterSnapshot
Notice
NotificationDelivery
AuthSession
MfaEnrollment
KycCase
RiskCase
Ticket
TicketMessage
TicketAttachment
SettlementMethod
```

## 规则（约束）

1. **复用 05 已有 canonical state**（Approval、ParameterRelease、AuthSession、KYC、Ticket），不新增状态值。
2. **只为缺失 canonical enum 的对象生成 Owner Decision Matrix**（NotificationDelivery、MfaEnrollment、RiskCase），不自创状态。
3. **明确职责分离**：`PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR`；`RISK_ANALYST != RISK_APPROVER`；申请人不得审批本人申请。
4. **明确 Notice 与业务事务解耦**：NotificationDelivery 失败不回滚业务。
5. **明确 Parameter `approved` ≠ `active`**；历史对象使用 snapshot。
6. 每个状态定义：初态、合法转移、终态、触发者、Writer、幂等、并发、审计、账本效果。
7. **未批准前保持 FAIL_CLOSED**；触发者/Writer 只用 05 §8 已冻结角色。

## 对象 canonical state 现状

| 对象 | 05 §3 字段 | canonical enum 现状 | 处理方式 |
|---|---|---|---|
| ApprovalRequest | 有 `status` | Approval enum（05 §4：`draft/pending/changes_requested/approved/rejected/executing/executed/failed`） | 复用 + 补齐转移矩阵 |
| ParameterRelease | 有 `status` | Parameter Release enum（05 §4：`draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived`） | 复用 + 补齐转移矩阵 |
| ParameterSnapshot | 无 `status` | 只读聚合/Projection | 无状态机；引用 ParameterRelease snapshot_id |
| Notice | 有 `read_state`（已读/未读，非状态机） | 只读聚合/Projection | 无状态机；read_state 为字段 |
| NotificationDelivery | 有 `delivery_status` | **缺失** | Owner Decision Matrix |
| AuthSession | 有 `status` | Session 状态（05 §2.2：`active/mfa_required/restricted/expired/revoked`） | 复用 + 补齐转移矩阵 |
| MfaEnrollment | 有 `status` | **缺失** | Owner Decision Matrix |
| KycCase | 有 `status` | KYC enum（05 §4：`not_started/pending/needs_info/approved/rejected/review`） | 复用 + 补齐转移矩阵 |
| RiskCase | 有 `status` | **缺失** | Owner Decision Matrix |
| Ticket | 有 `status` | Ticket enum（05 §4：`submitted/in_progress/waiting_user/under_review/resolved/closed`） | 复用 + 补齐转移矩阵 |
| TicketMessage | 无 `status` | 值对象 | 无状态机；仅追加 |
| TicketAttachment | 无 `status` | 值对象 | 无状态机；仅追加 |
| SettlementMethod | 有 `verification_status` | 值对象/只读聚合 | 无状态机；verification_status 为字段 |

## 非目标（NON_GOALS）

- 不生成 2B-2 任何 DDL（属 S01-P05）。
- 不写任何 PHP Model/DAO/Service（属 S01-P05）。
- 不自创 canonical state、不自创角色、不自创 API。
- 不修改 05 契约（3 缺 enum 对象的 enum 补充需 Owner 裁决后走 05 变更流程，见 acceptance）。
- 不涉及后续 S01-P06（非持久投影服务）、S01-P07（Affiliate/Agent）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§2/§3/§4/§8/§11）
- `.project-ai/tasks/TASK-20260816-001/design.md`（2B-1 合同，Owner Decision Matrix 格式 + 职责分离先例）
- `.project-ai/tasks/TASK-20260815-001/design.md`（MC2 角色/协同关系）
- `.project-ai/rules/coding.md`（数据库规则）
