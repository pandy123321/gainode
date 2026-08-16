# REVIEW_REQUEST — S01-P04 · 2B-2 状态合同补齐

## 审核头部

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-2 小批状态合同）
PACKAGE_ID = S01-P04-2B2-STATE-CONTRACT
IMPLEMENTATION_COMMIT = 5d57704deff2f266935d11bbfb4314c167643dd3
BASE_COMMIT = 81d103454fbf046d0cc179fd6ff81485620043f2
REVIEW_RANGE = 81d1034..5d57704（限定 5 文件，排除中间 quality commit feda9a0 的 S01-P03 复审报告）
PACKAGE_SHA256 = 554c1a465e52796996e255bfac806ad171fb0d46417c3b555096ee34c8c23bff
PREVIOUS_PACKAGE = S01-P03-2B1-DDL-SKELETON（2B-1 DDL + 骨架）
```

## 范围（Scope）

本包审核 **2B-2 状态合同补齐**的完整交付（5 文件）：

```text
A .project-ai/tasks/TASK-20260816-003/requirement.md
A .project-ai/tasks/TASK-20260816-003/design.md
A .project-ai/tasks/TASK-20260816-003/acceptance.md
A 0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md（2B-2 Freeze Candidate）
M Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md（§4 补 3 enum，V2.3→V2.4）
```

## 非目标（NON_GOALS）

- 不生成 2B-2 任何 DDL（属 S01-P05）。
- 不写任何 PHP Model/DAO/Service（属 S01-P05）。
- 不实现状态转移业务（本包为合同文档，转移矩阵候选，FAIL_CLOSED）。
- 不自创 canonical state、不自创角色、不自创 API。
- 不涉及 S01-P06（非持久投影）、S01-P07（Affiliate/Agent）。

## 审核对象（固定 13 对象）

| 对象 | 类型 | enum 来源 | 处理方式 |
|---|---|---|---|
| ApprovalRequest | 工作流 | 复制 05 §4 Approval（8 态） | 复用 + 转移矩阵 |
| ParameterRelease | 工作流 | 复制 05 §4 Parameter Release（8 态） | 复用 + 转移矩阵 |
| ParameterSnapshot | 只读聚合 | 无状态机 | 无状态机 |
| Notice | 只读聚合 | 无状态机 | 无状态机 |
| NotificationDelivery | 工作流 | Owner 裁决 2B2-ENUM-01 | 补 05 §4 V2.4 |
| AuthSession | 持久实体 | 复制 05 §2.2（5 态） | 复用 + 转移矩阵 |
| MfaEnrollment | 持久实体 | Owner 裁决 2B2-ENUM-02 | 补 05 §4 V2.4 |
| KycCase | 工作流 | 复制 05 §4 KYC（6 态） | 复用 + 转移矩阵 |
| RiskCase | 工作流 | Owner 裁决 2B2-ENUM-03 | 补 05 §4 V2.4 |
| Ticket | 工作流 | 复制 05 §4 Ticket（6 态） | 复用 + 转移矩阵 |
| TicketMessage | 值对象 | 无状态机 | 无状态机 |
| TicketAttachment | 值对象 | 无状态机 | 无状态机 |
| SettlementMethod | 值对象/只读聚合 | 无状态机 | 无状态机 |

## 关键不变量（必须核对）

```text
APPROVAL_ENUM_MATCHES_05 = YES
PARAMETER_RELEASE_ENUM_MATCHES_05 = YES
AUTH_SESSION_ENUM_MATCHES_05 = YES
KYC_ENUM_MATCHES_05 = YES
TICKET_ENUM_MATCHES_05 = YES
OWNER_DECISION_MATRIX_COUNT = 3（2B2-ENUM-01..03，全部 Owner 裁决 = OPTION_A）
ENUM_OWNER_CONFIRMED_05_V24 = YES（2026-08-16）
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_ROLE = YES
PARAM_ROLE_SEPARATION = YES（PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR）
RISK_ROLE_SEPARATION = YES（RISK_ANALYST != RISK_APPROVER）
NOTICE_DECOUPLED_FROM_BUSINESS = YES
PARAM_APPROVED_NOT_EQUAL_ACTIVE = YES
TRANSITION_MATRICES_NOT_FROZEN = YES
UNFROZEN_STATE_FAIL_CLOSED = YES
```

## 审核绑定（REVIEW_BINDING）

```text
IMPLEMENTATION_COMMIT = 5d57704deff2f266935d11bbfb4314c167643dd3
PACKAGE_SHA256 = 554c1a465e52796996e255bfac806ad171fb0d46417c3b555096ee34c8c23bff
DIFF_UNTUNCATED = YES（DIFF.txt = 39707 字符）
SECRET_SCAN = PASS（0 真实命中，2 处 password 误报已核验）
```

## 请求结论

请按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具完整审核（State Machine gate），最终给出：

```text
VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =
REVIEW_COMPLETENESS =
NEXT_PACKAGE_RECOMMENDATION = S01-P05_AUTHORIZED / NOT_AUTHORIZED
```

说明：

- 本包为合同文档，5 复用对象 enum 复制 05（未新增状态值）；3 缺 enum 对象已 Owner 裁决（2B2-ENUM-01..03 = OPTION_A）并补入 05 §4 V2.4；5 值对象/投影无状态机。
- 转移矩阵均为候选，独立审核（State Machine gate）通过后置 FROZEN。
- S01-P05（2B-2 DDL 与骨架）为下一包，路径不与本包重叠。
