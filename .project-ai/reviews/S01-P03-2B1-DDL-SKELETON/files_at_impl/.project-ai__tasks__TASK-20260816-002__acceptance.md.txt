# Acceptance: Machine Contract 第二批 2B-1 DDL 与 Model/DAO/Service 骨架（S01-P03）

## 状态

- **前置：S01-P02 enum 已 Owner 裁决（05 §4 V2.3）；转移矩阵 CANDIDATE**
- **本包验收：DDL + 骨架 + fail-closed guard（不验收业务转移逻辑）**

## 验收清单

| # | 验收项 | 状态 |
|---|---|---|
| 1 | DDL forward-only，8 张新表（`results`/`settlements`/`settlement_batches`/`refund_cases`/`correction_cases`/`otc_trades`/`robot_upgrade_orders`/`consent_receipts`）；不改 MC1/MC2 历史 DDL | 待独立审核 |
| 2 | `audit_events` 复用，未重复 CREATE TABLE | 待独立审核 |
| 3 | 主键 Snowflake bigint unsigned，`$incrementing=false`、`$keyType='string'`；decimal 禁 float | 待独立审核 |
| 4 | 状态列 ENUM 与 05 §4 V2.3 严格一致（9 对象） | 待独立审核 |
| 5 | Model 未加入未冻结字段（仅 05 §3 最低字段 + 冻结工程字段） | 待独立审核 |
| 6 | DAO 只读；append-only 表（AuditEvent/OtcTrade）覆写 destructive 方法 fail-closed | 待独立审核 |
| 7 | 9 个 Service 标 `@authoritative_writer`；状态流转 FAIL_CLOSED（未实现转移逻辑） | 待独立审核 |
| 8 | AuditEvent append-only Builder 表名/错误信息改为 `audit_events`，未复用 ledger 字段 | 待独立审核 |

## 机械一致性断言

```text
DDL_TABLE_COUNT = 8
ENUM_DDL_EQ_ENUM_MODEL_EQ_ENUM_FREEZE = YES
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_FIELD = YES
AUDIT_EVENTS_REUSED_NOT_RECREATED = YES
SERVICE_AUTHORITATIVE_WRITER_COUNT = 9
TRANSITION_LOGIC_NOT_IMPLEMENTED = YES
php -l = PASS
git diff --check = PASS
```

## 非目标验证（NOT_RUN，属后续包）

```text
composer test 集成 = 由质量/后续阶段执行（本包仅骨架，无转移逻辑）
DDL 实际建表 = 属 STAGE-05 Sandbox（本包仅 forward-only 脚本）
状态转移业务 = 转移矩阵 FROZEN 后（S01-P06+）实现
```

## 交付物

- `0.5代码/gainode后端/gainode/sql/20260816_machine_contract_batch2b1_8_entities.sql`
- `library/model|dao|service/**` 新增 28 个 PHP 文件（9 对象 + 2 append-only Builder）
