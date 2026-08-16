# QUALITY_REVIEW_PROMPT — S01-P04 · 2B-2 状态合同补齐

你是 Gainode 项目的 Independent Review Agent（默认只读，不修改任何代码/DDL/合同）。

## 1. 审核输入（先验证）

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-2 小批状态合同）
PACKAGE_ID = S01-P04-2B2-STATE-CONTRACT
IMPLEMENTATION_COMMIT = 5d57704deff2f266935d11bbfb4314c167643dd3
BASE_COMMIT = 81d103454fbf046d0cc179fd6ff81485620043f2
REVIEW_RANGE = 81d1034..5d57704（限定 5 文件）
PACKAGE_SHA256 = 554c1a465e52796996e255bfac806ad171fb0d46417c3b555096ee34c8c23bff
```

权威输入文件（本复审包）：

```text
REVIEW_REQUEST.md        审核范围与绑定
DIFF.txt                 完整未截断 diff（39707 字符）
CHANGED_FILES.txt        变更文件清单（5 文件）
PAYLOAD_MANIFEST.csv     逐文件 SHA-256
PACKAGE_SHA256.txt       总包 SHA-256
files_at_impl/*.txt      5d57704 全文快照（5 文件）
SELF_REVIEW.md           执行者自检
VALIDATION_RESULTS.md    已执行验证
KNOWN_LIMITATIONS.md     前置状态与工具限制
SECRET_SCAN.md           秘钥扫描（PASS）
```

## 2. 审核对象（13 对象：5 复用 + 3 裁决 + 5 值对象/投影）

### 2.1 复用对象 enum 一致性（重点）

核对以下 5 对象 enum 与 05 完全一致，未新增/删改状态值：

- ApprovalRequest = `draft/pending/changes_requested/approved/rejected/executing/executed/failed`（05 §4 Approval）
- ParameterRelease = `draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived`（05 §4 Parameter Release）
- AuthSession = `active/mfa_required/restricted/expired/revoked`（05 §2.2）
- KycCase = `not_started/pending/needs_info/approved/rejected/review`（05 §4 KYC）
- Ticket = `submitted/in_progress/waiting_user/under_review/resolved/closed`（05 §4 Ticket）

### 2.2 Owner 裁决对象（3 个，已补 05 §4 V2.4）

核对以下 3 对象 enum 与 Owner 裁决（2B2-ENUM-01..03 = OPTION_A）一致：

- NotificationDelivery = `pending/delivered/failed/cancelled`
- MfaEnrollment = `pending/active/revoked`
- RiskCase = `open/investigating/under_review/resolved/closed`

### 2.3 转移矩阵（候选，State Machine gate 重点）

核对每个状态定义是否包含：初态、合法转移、终态、触发者、Writer、幂等、并发、审计、账本效果。核对非法转移 FAIL_CLOSED。

### 2.4 职责分离（重点）

- `PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR`
- `RISK_ANALYST != RISK_APPROVER`
- 申请人不得审批本人申请

### 2.5 值对象/只读聚合

核对 ParameterSnapshot/Notice/TicketMessage/TicketAttachment/SettlementMethod 未新增 `status`（无状态机）。

### 2.6 业务解耦规则

- Notice 与业务事务解耦；NotificationDelivery 失败不回滚业务
- Parameter `approved` ≠ `active`；历史对象使用 snapshot

## 3. 审核方法（Evidence First）

- 每条 Finding 必须基于 `DIFF.txt` / `files_at_impl/*.txt` 的**实际文本**。
- 重点核对：enum 是否与 05 §4/§2.2 完全一致；是否引入自创状态/角色；职责分离是否在状态机层面体现；值对象是否误加状态机。
- 触发者/Writer 仅限 05 §8 已冻结 13 角色。

## 4. 输出要求

按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具，每条 Finding 必填全字段。

最终标准头：

```text
REVIEW_ID =
PROJECT =
STAGE =
COMMIT = 5d57704deff2f266935d11bbfb4314c167643dd3
PACKAGE_SHA256 = 554c1a465e52796996e255bfac806ad171fb0d46417c3b555096ee34c8c23bff
REVIEW_BINDING = VALID
REVIEW_COMPLETENESS =

VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =

NEXT_PACKAGE_RECOMMENDATION = S01-P05_AUTHORIZED / NOT_AUTHORIZED
```

说明：

- 本包为合同文档，转移矩阵候选；独立审核（State Machine gate）通过后置 FROZEN。
- S01-P05（2B-2 DDL 与骨架）为下一包，路径不与本包重叠。
