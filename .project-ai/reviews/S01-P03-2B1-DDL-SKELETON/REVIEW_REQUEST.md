# REVIEW_REQUEST — S01-P03 · 2B-1 DDL 与 Model/DAO/Service 骨架

## 审核头部

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-1 小批 DDL 与骨架）
PACKAGE_ID = S01-P03-2B1-DDL-SKELETON
IMPLEMENTATION_COMMIT = eedf313319f811c6eb46a6a3ea7b383d98e9a5cc
BASE_COMMIT = eba19c6ac8892844759bb166b8baf0541713a03a
REVIEW_RANGE = eba19c6..eedf313
PACKAGE_SHA256 = 303d5642eca4942ecf6ddc05c2556a65eeb46d0a13dabe904f4126c76c7dd215
PREVIOUS_PACKAGE = S01-P02-2B1-STATE-CONTRACT（enum 已 Owner 裁决并补入 05 §4 V2.3；转移矩阵 CANDIDATE，待 State Machine gate）
```

## 范围（Scope）

本包审核 **2B-1 DDL 与 Model/DAO/Service 骨架**的完整交付（33 文件）：

```text
A .project-ai/tasks/TASK-20260816-002/requirement.md
A .project-ai/tasks/TASK-20260816-002/design.md
A .project-ai/tasks/TASK-20260816-002/acceptance.md
A 0.5代码/gainode后端/gainode/sql/20260816_machine_contract_batch2b1_8_entities.sql（8 张新表）
A 0.5代码/gainode后端/gainode/library/model/**（11 文件，含 2 个 append-only Builder）
A 0.5代码/gainode后端/gainode/library/dao/**（9 文件）
A 0.5代码/gainode后端/gainode/library/service/**（9 文件）
```

## 非目标（NON_GOALS）

- 不实现任何状态转移方法（结算/退款/撮合/审批业务全部 FAIL_CLOSED）。
- 不新增未冻结字段（仅 05 §3 最低字段 + 冻结工程字段）。
- 不修改 MC1/MC2 冻结文件；不重复 CREATE `audit_events`。
- 不触碰 S01-P01/P02 锁定文件。
- 不实现 OpenAPI / 路由 / 控制器（属后续包）。

## 审核对象（固定 9 对象）

| 对象 | 表名 | 状态 enum（05 §4 V2.3） | 类型 |
|---|---|---|---|
| Result | `results` | provisional/official/disputed/corrected | 工作流 |
| Settlement | `settlements` | queued/calculating/review/payable/paid/failed | 工作流 |
| SettlementBatch | `settlement_batches` | created/processing/completed/partially_failed/failed | 工作流 |
| RefundCase | `refund_cases` | pending/approved/executing/completed/rejected/failed | 工作流 |
| CorrectionCase | `correction_cases` | pending/approved/executing/completed/rejected/failed | 工作流 |
| OtcTrade | `otc_trades` | completed（单态） | append-only |
| RobotUpgradeOrder | `robot_upgrade_orders` | pending/processing/completed/failed/cancelled | 工作流 |
| ConsentReceipt | `consent_receipts` | active/expired（两态） | 工作流 |
| AuditEvent | `audit_events` | （无状态机） | append-only（复用 MC2） |

## 关键不变量（必须核对）

```text
DDL_TABLE_COUNT = 8（排除 audit_events）
ENUM_DDL_EQ_ENUM_MODEL_EQ_ENUM_FREEZE = YES
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_FIELD = YES
AUDIT_EVENTS_REUSED_NOT_RECREATED = YES
SERVICE_AUTHORITATIVE_WRITER_COUNT = 9
TRANSITION_LOGIC_NOT_IMPLEMENTED = YES（状态流转一律 FAIL_CLOSED）
APPEND_ONLY_BUILDER_DENY_SET = 与 AptLedgerEntryAppendOnlyBuilder 一致（12 方法）
SNOWFLAKE_PRIMARY_KEY = YES（$incrementing=false, $keyType='string'）
DECIMAL_NO_FLOAT = YES
```

## 审核绑定（REVIEW_BINDING）

```text
IMPLEMENTATION_COMMIT = eedf313319f811c6eb46a6a3ea7b383d98e9a5cc
PACKAGE_SHA256 = 303d5642eca4942ecf6ddc05c2556a65eeb46d0a13dabe904f4126c76c7dd215
DIFF_UNTUNCATED = YES（DIFF.txt = 99699 字符）
SECRET_SCAN = PASS（0 hits）
```

## 请求结论

请按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具完整审核，最终给出：

```text
VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =
REVIEW_COMPLETENESS =
NEXT_PACKAGE_RECOMMENDATION = S01-P04_AUTHORIZED / NOT_AUTHORIZED
```

说明：

- 本包为骨架 + fail-closed guard，不消费未冻结转移矩阵；转移矩阵仍 CANDIDATE，由质量 agent 并行做 State Machine gate。
- S01-P04（2B-2 合同补齐）为下一包，路径不与本包重叠。
