# QUALITY_REVIEW_PROMPT — S01-P05 · 2B-2 DDL + Model/DAO/Service 骨架

你是 Gainode 的 Independent Review Agent。请对以下包出具完整审核（DDL + Model/DAO/Service 骨架 gate）。

## 审计输入（先核验）

```text
PACKAGE_ID = S01-P05-2B2-DDL-SKELETON
IMPLEMENTATION_COMMIT = 971513061cbe7accab3539cfe38679eecaf69f65
BASE_COMMIT = 69a899829e4c926f740a9bead5f45afbe4f4d9c7
REVIEW_RANGE = 69a8998..9715130
PACKAGE_SHA256 = 0b432de67210e6b3e5842bc9fa74bd6456d0e14ef707937f700931420f998f8f
FILES = 46
DIFF_UNTUNCATED = YES（126457 字符）
SECRET_SCAN = PASS
```

先核对 PACKAGE_SHA256 与 manifest 一致，确认 DIFF.txt 未被截断，再开始审核。

## 审核对象（13 对象）

ApprovalRequest / ParameterRelease / ParameterSnapshot / Notice / NotificationDelivery /
AuthSession / MfaEnrollment / KycCase / RiskCase / Ticket / TicketMessage / TicketAttachment /
SettlementMethod（各含 DDL + Model + DAO + Service；3 个 append-only 对象含 Builder）

## DDL 检查点（13 表）

1. 表数 = 13，forward-only，无 DROP / IF NOT EXISTS。
2. 主键 Snowflake bigint unsigned，无 AUTO_INCREMENT。
3. 8 状态机 enum 严格对齐 05 §4 V2.4 / Owner 裁决（2B2-ENUM-01..03），无自创状态值。
4. 金额无 float（本包无 APT 金额）。
5. append-only 表（parameter_snapshots/ticket_messages/ticket_attachments）无 updated_time、无 object_version。
6. NotificationDelivery 用 dedupe_key 幂等（UNIQUE），无额外 idempotency_key。

## Model 检查点（13）

1. table/PK/时间列映射冻结；Snowflake `$incrementing=false`、`$keyType='string'`。
2. 状态常量 + STATUSES 与 DDL enum 逐值一致。
3. append-only Model：`$timestamps=false` + `UPDATED_AT=null` + `newEloquentBuilder()` + `save()`/`delete()` 兜底。
4. FK 关系仅同域明确引用；user_id 不建 FK。

## Builder 检查点（3）

1. deny set = 12 方法（与 OtcTradeAppendOnlyBuilder 一致）。
2. 错误信息指向正确表名。

## DAO 检查点（13）

1. 只读查询封装。
2. append-only DAO 覆写 delete/deleteAll/update/updateAll/updateOrCreate 全部 fail-closed。

## Service 检查点（13）

1. 全部标 `@authoritative_writer <table>`。
2. 未实现状态转移（FAIL_CLOSED）。

## 关键不变量

```text
ENUM_DDL_EQ_ENUM_MODEL_EQ_ENUM_FREEZE = YES
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_FIELD = YES
SERVICE_AUTHORITATIVE_WRITER_COUNT = 13
TRANSITION_LOGIC_NOT_IMPLEMENTED = YES
APPEND_ONLY_COUNT = 3
```

## 方法论

Evidence First：每条结论须引用具体文件行号 / DDL 列 / Model 常量，禁止凭印象判定。

## 输出要求

```text
VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =
REVIEW_COMPLETENESS =
NEXT_PACKAGE_RECOMMENDATION = S01-P06_AUTHORIZED / NOT_AUTHORIZED
```

每项发现标注：严重级（P0 阻断/P1 主要/P2 次要/P3 建议）、文件、行号、证据、修复建议。
