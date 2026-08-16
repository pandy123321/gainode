# Acceptance: Machine Contract 第二批 2B-2（合同补齐）

## 状态

- **Owner Signoff：完成（3 缺 enum 对象已逐项裁决，2026-08-16，全部采纳 RECOMMENDED_OPTION = OPTION_A，已补入 05 §4 V2.4）**
- **Independent Review：未开始（5 复用对象转移矩阵 + 3 缺 enum 对象 enum，待 State Machine gate）**
- **冻结状态：CANDIDATE**

## 验收范围

本 task 只验收「状态合同补齐」产出（文档），不验收 DDL/代码（属 S01-P05）。

## 验收清单

| # | 验收项 | 状态 |
|---|---|---|
| 1 | ApprovalRequest 复用 05 §4 Approval enum（8 态），未新增状态值 | 待独立审核 |
| 2 | ParameterRelease 复用 05 §4 Parameter Release enum（8 态），未新增状态值 | 待独立审核 |
| 3 | AuthSession 复用 05 §2.2 Session 状态（5 态），未新增状态值 | 待独立审核 |
| 4 | KycCase 复用 05 §4 KYC enum（6 态），未新增状态值 | 待独立审核 |
| 5 | Ticket 复用 05 §4 Ticket enum（6 态），未新增状态值 | 待独立审核 |
| 6 | 3 缺 enum 对象（NotificationDelivery/MfaEnrollment/RiskCase）经 Owner 裁决补入 05 §4（V2.4）为 canonical enum，未自创状态 | 待独立审核 |
| 7 | 每个复用对象转移定义：初态、合法转移、终态、触发者、Writer、幂等、并发、审计、账本效果 | 待独立审核 |
| 8 | 职责分离明确：`PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR`；`RISK_ANALYST != RISK_APPROVER`；申请人不得审批本人申请 | 待独立审核 |
| 9 | Notice 与业务事务解耦；NotificationDelivery 失败不回滚业务；dedupe_key 去重 | 待独立审核 |
| 10 | Parameter `approved` ≠ `active`；历史对象使用 snapshot | 待独立审核 |
| 11 | 值对象/只读聚合（ParameterSnapshot/Notice/TicketMessage/TicketAttachment/SettlementMethod）无状态机，未新增 status | 待独立审核 |
| 12 | 触发者/Writer 仅使用 05 §8 已冻结角色，未自创角色 | 待独立审核 |
| 13 | 转移矩阵未经独立审核前不建 ENUM 表、不写业务；3 缺 enum 未 Owner 裁决前 FAIL_CLOSED 不建表 | 待独立审核 |

## 机械一致性断言（冻结前 gate）

```text
APPROVAL_ENUM_MATCHES_05 = YES
PARAMETER_RELEASE_ENUM_MATCHES_05 = YES
AUTH_SESSION_ENUM_MATCHES_05 = YES
KYC_ENUM_MATCHES_05 = YES
TICKET_ENUM_MATCHES_05 = YES
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_ROLE = YES
OWNER_DECISION_MATRIX_COUNT = 3
ENUM_OWNER_CONFIRMED_05_V24 = YES（Owner 裁决 2026-08-16）
TRANSITION_MATRICES_NOT_FROZEN = YES
UNFROZEN_STATE_FAIL_CLOSED = YES
PARAM_ROLE_SEPARATION = YES
RISK_ROLE_SEPARATION = YES
NOTICE_DECOUPLED_FROM_BUSINESS = YES
PARAM_APPROVED_NOT_EQUAL_ACTIVE = YES
```

## 阻塞项与恢复条件

| 阻塞对象 | 状态 | 恢复条件 |
|---|---|---|
| （已解除）NotificationDelivery/MfaEnrollment/RiskCase enum | RESOLVED | Owner 已裁决并补入 05 §4（V2.4，2026-08-16） |
| 复用对象转移矩阵 | 待独立审核 | Independent Review（State Machine gate）通过后置 FROZEN |

## 交付物

- `.project-ai/tasks/TASK-20260816-003/requirement.md`
- `.project-ai/tasks/TASK-20260816-003/design.md`
- `.project-ai/tasks/TASK-20260816-003/acceptance.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md`（2B-2 Freeze Candidate，Owner 裁决后落盘，随 `.sql` 同目录，S01-P04 步骤 7）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§4 补入 3 enum，V2.4，Owner 裁决后）

## 非目标验证（明确 NOT_RUN，属后续包）

```text
DDL_CREATED = NOT_RUN（属 S01-P05）
MODEL_DAO_SERVICE = NOT_RUN（属 S01-P05）
php -l = NOT_RUN（本包无 PHP 代码）
composer test = NOT_RUN（本包无代码变更）
```
