# Requirement: Machine Contract 第二批 2B-1 DDL 与 Model/DAO/Service 骨架（S01-P03）

## 状态

- **前置：S01-P02 对应对象合同 enum 已 Owner 裁决并补入 05 §4（V2.3）；转移矩阵 CANDIDATE（待质量 agent State Machine gate）**
- **本包边界：仅 DDL + 骨架 + fail-closed guard；不实现结算/退款/撮合业务；不消费未冻结转移矩阵**

## 目标

按 07 §S01-P03 固定范围，为 2B-1 小批 9 对象建立 DDL + Model/DAO/Service 骨架：

```text
Result
Settlement
SettlementBatch
RefundCase
CorrectionCase
OtcTrade
RobotUpgradeOrder
ConsentReceipt
AuditEvent
```

## 固定范围（非目标 = 明确不做）

- 8 张新表 DDL（`results`/`settlements`/`settlement_batches`/`refund_cases`/`correction_cases`/`otc_trades`/`robot_upgrade_orders`/`consent_receipts`）。
- `audit_events` 复用 MC2 `20260815_machine_contract_batch2_audit_events.sql`，不重复 CREATE TABLE。
- Model/DAO/Service 骨架一一对应；Service 是唯一 Authoritative Writer。
- **非目标**：不实现任何状态转移方法、不实现结算/退款/撮合/审批业务、不新增字段（超出 05 §3 + 冻结工程字段）、不修改 MC1/MC2 冻结文件、不触碰 S01-P01/P02 锁定文件。

## 需求条目

| ID | 需求 | 权威依据 |
|---|---|---|
| R1 | 8 张新表 DDL forward-only，Snowflake bigint unsigned 主键，decimal 禁 float | 07 §S01-P03 / MC1 DDL 约定 |
| R2 | 领域状态列用 ENUM（冻结 enum），与 05 §4 V2.3 严格一致 | 05 §4 V2.3 |
| R3 | 每表 `object_version` / `idempotency_key` / `audit_event_id` / 时间列按冻结工程约束 | design S01-P02 通用工程约束 |
| R4 | 每个 Model 映射冻结表名/主键/时间列/enum；`$incrementing=false`、`$keyType='string'` | 07 §S01-P03 |
| R5 | DAO 只提供查询（+ append-only 表覆写 destructive）；Service 标 `@authoritative_writer` 且状态流转 FAIL_CLOSED | 07 §S01-P03 |
| R6 | AuditEvent 复用已验证 append-only Builder/DAO 防护模式，表名/测试矩阵改为 `audit_events` | 07 §S01-P03 |
| R7 | OtcTrade 为 append-only 单态事实，采用 append-only 防护 | design S01-P02 D.7.4 |

## 验收（机械一致性断言）

```text
DDL_TABLE_COUNT = 8（排除 audit_events）
ENUM_DDL_EQ_ENUM_MODEL_EQ_ENUM_FREEZE = YES
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_FIELD = YES
AUDIT_EVENTS_REUSED_NOT_RECREATED = YES
SERVICE_AUTHORITATIVE_WRITER_MARKED = YES（9 个 Service）
TRANSITION_LOGIC_NOT_IMPLEMENTED = YES（状态流转一律 FAIL_CLOSED）
php -l = PASS（全部变更 PHP 文件）
git diff --check = PASS
```
