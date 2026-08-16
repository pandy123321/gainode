# Acceptance: Machine Contract 第二批 2B-2（DDL 与骨架）

## 状态

- **Owner Signoff：完成（3 缺 enum 对象已裁决，见 S01-P04）**
- **Independent Review：未开始**
- **冻结状态：2B-2 状态合同 CANDIDATE；本包骨架 + fail-closed**

## 验收清单

| # | 验收项 | 状态 |
|---|---|---|
| 1 | DDL 创建 13 张新表，forward-only，无 DROP/IF NOT EXISTS | 待独立审核 |
| 2 | 主键 Snowflake bigint unsigned，无 AUTO_INCREMENT | 待独立审核 |
| 3 | 5 复用对象 enum 严格对齐 05 §4/§2.2（8/8/5/6/6 态） | 待独立审核 |
| 4 | 3 裁决对象 enum 对齐 Owner 裁决（4/3/5 态，2B2-ENUM-01..03） | 待独立审核 |
| 5 | append-only 对象（ParameterSnapshot/TicketMessage/TicketAttachment）三层防护，无 object_version/updated_time | 待独立审核 |
| 6 | 可变对象（10 个）含 object_version + idempotency_key + audit_event_id + created_time + updated_time | 待独立审核 |
| 7 | NotificationDelivery 用 dedupe_key 幂等，无额外 idempotency_key | 待独立审核 |
| 8 | Model 映射冻结表名/主键/时间列/enum，未加未冻结字段 | 待独立审核 |
| 9 | DAO 只读查询；append-only 表覆写 destructive | 待独立审核 |
| 10 | Service 唯一写入者标 @authoritative_writer，未实现状态转移 | 待独立审核 |
| 11 | php -l 全部 PASS | 待执行 |
| 12 | git diff --check 无空白错误 | 待执行 |
| 13 | enum(DDL) == enum(Model) == enum(Freeze) | 待独立审核 |

## 机械一致性断言（冻结前 gate）

```text
DDL_TABLE_COUNT = 13
ENUM_DDL_EQ_ENUM_MODEL_EQ_ENUM_FREEZE = YES
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_FIELD = YES
SERVICE_AUTHORITATIVE_WRITER_COUNT = 13
TRANSITION_LOGIC_NOT_IMPLEMENTED = YES（状态流转一律 FAIL_CLOSED）
APPEND_ONLY_COUNT = 3（ParameterSnapshot/TicketMessage/TicketAttachment）
APPEND_ONLY_BUILDER_DENY_SET = 12 方法（与 OtcTradeAppendOnlyBuilder 一致）
SNOWFLAKE_PRIMARY_KEY = YES
DECIMAL_NO_FLOAT = YES
```

## 非目标验证（明确 NOT_RUN，属后续包）

```text
DDL 实际建表 = NOT_RUN（属 STAGE-05 Sandbox）
运行时/数据库验证 = NOT_RUN
状态转移业务验证 = NOT_RUN（转移矩阵 FROZEN 后）
OpenAPI/路由/控制器 = NOT_RUN
composer test = 需在 DDL 骨架落盘后评估（本包为骨架，无业务逻辑）
```

## 交付物

- `.project-ai/tasks/TASK-20260816-004/requirement.md`
- `.project-ai/tasks/TASK-20260816-004/design.md`
- `.project-ai/tasks/TASK-20260816-004/acceptance.md`
- `0.5代码/gainode后端/gainode/sql/20260816_machine_contract_batch2b2_13_entities.sql`
- 13 Model + 3 Builder + 13 DAO + 13 Service（见 design.md 目标文件）
