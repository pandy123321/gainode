# REVIEW_REQUEST — S01-P05 · 2B-2 DDL + Model/DAO/Service 骨架

## 审核头部

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-2 DDL 与骨架）
PACKAGE_ID = S01-P05-2B2-DDL-SKELETON
IMPLEMENTATION_COMMIT = 971513061cbe7accab3539cfe38679eecaf69f65
BASE_COMMIT = 69a899829e4c926f740a9bead5f45afbe4f4d9c7
REVIEW_RANGE = 69a8998..9715130（限定 46 文件）
PACKAGE_SHA256 = 0b432de67210e6b3e5842bc9fa74bd6456d0e14ef707937f700931420f998f8f
PREVIOUS_PACKAGE = S01-P04-2B2-STATE-CONTRACT（2B-2 状态合同，已 Owner 裁决 + 独立审核）
```

## 范围（Scope）

本包审核 **2B-2 DDL + Model/DAO/Service 骨架**的完整交付（46 文件）：

```text
A .project-ai/tasks/TASK-20260816-004/requirement.md
A .project-ai/tasks/TASK-20260816-004/design.md
A .project-ai/tasks/TASK-20260816-004/acceptance.md
A 0.5代码/gainode后端/gainode/sql/20260816_machine_contract_batch2b2_13_entities.sql（13 表 DDL）
A library/{model,dao,service}/approval/ApprovalRequest*（3）
A library/{model,dao,service}/parameter/ParameterRelease* + ParameterSnapshot*（4，含 1 Builder）
A library/{model,dao,service}/notice/Notice* + NotificationDelivery*（6）
A library/{model,dao,service}/auth/AuthSession* + MfaEnrollment*（6）
A library/{model,dao,service}/kyc/KycCase*（3）
A library/{model,dao,service}/risk/RiskCase*（3）
A library/{model,dao,service}/support/Ticket* + TicketMessage* + TicketAttachment*（9，含 2 Builder）
A library/{model,dao,service}/settlement/SettlementMethod*（3）
```

## 非目标（NON_GOALS）

- 不实现任何状态转移方法（审批/参数激活/MFA 验证/风控处置/工单流转/通知投递全部 FAIL_CLOSED）。
- 不新增未冻结字段（仅 05 §3 最低字段 + 冻结工程字段）。
- 不修改 MC1/MC2/2B-1 冻结文件、不修改 S01-P04 锁定的 05 §4 V2.4。
- 不实现 OpenAPI / 路由 / 控制器（属后续包）。
- 不涉及 S01-P06（非持久投影）、S01-P07（Affiliate/Agent）。

## 审核对象（固定 13 对象）

| # | 对象 | 表 | append-only | 状态机 | 交付 |
|---|---|---|---|---|---|
| 1 | ApprovalRequest | approval_requests | 否 | 8 态 | Model+DAO+Service |
| 2 | ParameterRelease | parameter_releases | 否 | 8 态 | Model+DAO+Service |
| 3 | ParameterSnapshot | parameter_snapshots | **是** | 无 | Model+Builder+DAO+Service |
| 4 | Notice | notices | 否 | read_state | Model+DAO+Service |
| 5 | NotificationDelivery | notification_deliveries | 否 | 4 态 | Model+DAO+Service |
| 6 | AuthSession | auth_sessions | 否 | 5 态 | Model+DAO+Service |
| 7 | MfaEnrollment | mfa_enrollments | 否 | 3 态 | Model+DAO+Service |
| 8 | KycCase | kyc_cases | 否 | 6 态 | Model+DAO+Service |
| 9 | RiskCase | risk_cases | 否 | 5 态 | Model+DAO+Service |
| 10 | Ticket | tickets | 否 | 6 态 | Model+DAO+Service |
| 11 | TicketMessage | ticket_messages | **是** | 无 | Model+Builder+DAO+Service |
| 12 | TicketAttachment | ticket_attachments | **是** | 无 | Model+Builder+DAO+Service |
| 13 | SettlementMethod | settlement_methods | 否 | verification_status | Model+DAO+Service |

## 关键不变量（必须核对）

```text
DDL_TABLE_COUNT = 13
ENUM_DDL_EQ_ENUM_MODEL_EQ_ENUM_FREEZE = YES（8 状态机 enum 严格对齐 05 §4 V2.4 / Owner 裁决）
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_FIELD = YES（仅 05 §3 最低字段 + 冻结工程字段）
SNOWFLAKE_PRIMARY_KEY = YES（bigint unsigned，$incrementing=false，$keyType='string'）
DECIMAL_NO_FLOAT = YES（本包无 APT 金额）
SERVICE_AUTHORITATIVE_WRITER_COUNT = 13
TRANSITION_LOGIC_NOT_IMPLEMENTED = YES（状态流转一律 FAIL_CLOSED）
APPEND_ONLY_COUNT = 3（ParameterSnapshot/TicketMessage/TicketAttachment）
APPEND_ONLY_ENGINEERING = 无 object_version、无 updated_time，三层防护（Builder+Model+DAO）
APPEND_ONLY_BUILDER_DENY_SET = 12 方法（与 OtcTradeAppendOnlyBuilder 一致）
NOTIFICATION_DELIVERY_DEDUPE_KEY = YES（dedupe_key 幂等，无额外 idempotency_key）
```

## 审核绑定（REVIEW_BINDING）

```text
IMPLEMENTATION_COMMIT = 971513061cbe7accab3539cfe38679eecaf69f65
PACKAGE_SHA256 = 0b432de67210e6b3e5842bc9fa74bd6456d0e14ef707937f700931420f998f8f
DIFF_UNTUNCATED = YES（DIFF.txt = 126457 字符，46 文件全量）
SECRET_SCAN = PASS（0 真实命中）
PHP_LINT = PASS（42/42 无语法错误）
GIT_DIFF_CHECK = PASS（无空白错误）
```

## 请求结论

请按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具完整审核（DDL + Model/DAO/Service 骨架 gate），最终给出：

```text
VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =
REVIEW_COMPLETENESS =
NEXT_PACKAGE_RECOMMENDATION = S01-P06_AUTHORIZED / NOT_AUTHORIZED
```

说明：

- 本包为骨架 + fail-closed guard，不实现状态转移业务。转移矩阵仍 CANDIDATE（S01-P04 未 FROZEN）。
- 3 个 append-only 对象（ParameterSnapshot/TicketMessage/TicketAttachment）复刻 2B-1 已审核的 OtcTrade/AuditEvent 三层防护。
- NotificationDelivery 幂等用 dedupe_key（05 §4 Notice 原则 3），不额外设 idempotency_key。
- S01-P06（非持久投影，禁止建表）为下一包，路径不与本包重叠。
