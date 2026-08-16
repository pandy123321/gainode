# QUALITY_REVIEW_PROMPT — S01-P03 · 2B-1 DDL 与 Model/DAO/Service 骨架

你是 Gainode 项目的 Independent Review Agent（默认只读，不修改任何代码/DDL/合同）。

## 1. 审核输入（先验证）

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-1 小批 DDL 与骨架）
PACKAGE_ID = S01-P03-2B1-DDL-SKELETON
IMPLEMENTATION_COMMIT = eedf313319f811c6eb46a6a3ea7b383d98e9a5cc
BASE_COMMIT = eba19c6ac8892844759bb166b8baf0541713a03a
REVIEW_RANGE = eba19c6..eedf313
PACKAGE_SHA256 = 303d5642eca4942ecf6ddc05c2556a65eeb46d0a13dabe904f4126c76c7dd215
```

权威输入文件（本复审包）：

```text
REVIEW_REQUEST.md        审核范围与绑定
DIFF.txt                 完整未截断 diff（99699 字符）
CHANGED_FILES.txt        变更文件清单（33 文件）
PAYLOAD_MANIFEST.csv     逐文件 SHA-256
PACKAGE_SHA256.txt       总包 SHA-256
files_at_impl/*.txt      eedf313 全文快照（33 文件）
SELF_REVIEW.md           执行者自检
VALIDATION_RESULTS.md    已执行验证
KNOWN_LIMITATIONS.md     前置状态与工具限制
SECRET_SCAN.md           秘钥扫描（PASS）
```

## 2. 审核对象（固定 9 对象，8 新表 + audit_events 复用）

### 2.1 DDL（`20260816_machine_contract_batch2b1_8_entities.sql`）

- 核对 **8 张新表**，无重复 CREATE `audit_events`（应复用 MC2）。
- 核对主键 Snowflake bigint unsigned；金额 `decimal(...)` 禁 float；时间 Unix 秒 int unsigned。
- 核对每个 `status` ENUM 与 05 §4 V2.3 严格一致（见 REVIEW_REQUEST 表）。
- 核对每表 `object_version` / `idempotency_key`（可空 UNIQUE）/ `audit_event_id`（敏感写表）/ 时间列。
- 核对 `otc_trades` 无 `updated_time`（append-only）。

### 2.2 Model（11 文件）

- 核对 `$table` / `$primaryKey` 与 DDL 一致。
- 核对 `$incrementing=false`、`$keyType='string'`（Snowflake）。
- 核对状态常量（`STATUSES` 数组）与 DDL enum 一致，未自创状态。
- 核对 `$fields` 未加入未冻结字段。
- append-only 表（OtcTrade/AuditEvent）：核对 `$timestamps=false`、`UPDATED_AT=null`、`save()`（落盘）/`delete()` 抛 RunException、`newEloquentBuilder()` 注入 Builder。

### 2.3 append-only Builder（2 文件）

- 核对 `OtcTradeAppendOnlyBuilder` / `AuditEventAppendOnlyBuilder` 的 `DESTRUCTIVE_METHODS` deny set 与 `AptLedgerEntryAppendOnlyBuilder` **完全一致**（12 方法）。
- 核对错误信息仅改表名，未复用 ledger 字段。

### 2.4 DAO（9 文件）

- 核对只读查询封装；append-only 表（OtcTrade/AuditEvent）覆写 `delete/deleteAll/update/updateAll/updateOrCreate` 全部 fail-closed。

### 2.5 Service（9 文件）

- 核对每个 Service 标 `@authoritative_writer <table>`。
- 核对**未实现任何状态转移方法**（结算/退款/撮合/审批业务一律 FAIL_CLOSED）。
- 核对仅透传只读查询。

## 3. 审核方法（Evidence First）

- 每条 Finding 必须基于 `DIFF.txt` / `files_at_impl/*.txt` 的**实际文本**。
- 重点核对：enum 是否与 05 §4 V2.3 完全一致；是否引入未冻结字段；是否实现转移逻辑；append-only 防护是否机械强制（非仅注释）。
- 与 MC2 已冻结协同关系（结算会计矩阵、M6/M7/M9/M10/M12、P3/P5/P6/P7/P10/P11/P12）核对字段引用正确性（仅结构引用，不实现逻辑）。

## 4. 输出要求

按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具，每条 Finding 必填全字段。

最终标准头：

```text
REVIEW_ID =
PROJECT =
STAGE =
COMMIT = eedf313319f811c6eb46a6a3ea7b383d98e9a5cc
PACKAGE_SHA256 = 303d5642eca4942ecf6ddc05c2556a65eeb46d0a13dabe904f4126c76c7dd215
REVIEW_BINDING = VALID
REVIEW_COMPLETENESS =

VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =

NEXT_PACKAGE_RECOMMENDATION = S01-P04_AUTHORIZED / NOT_AUTHORIZED
```

说明：

- 本包为骨架 + fail-closed guard，不消费未冻结转移矩阵；转移矩阵仍 CANDIDATE，可并行做 State Machine gate。
- S01-P04（2B-2 合同补齐）为下一包，路径不与本包重叠。
