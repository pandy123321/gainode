# Design: Machine Contract 第二批 2B-1 DDL 与 Model/DAO/Service 骨架（S01-P03）

## 状态

- **前置：S01-P02 enum 已 Owner 裁决（05 §4 V2.3）；转移矩阵 CANDIDATE（质量 agent State Machine gate 中）**
- **本包：仅骨架 + fail-closed guard，不实现业务转移逻辑**

## 1. 表清单与 enum 映射（权威源：05 §4 V2.3）

| 对象 | 表名 | 状态 enum | 备注 |
|---|---|---|---|
| Result | `results` | `provisional/official/disputed/corrected` | 复制 05 §4；`outcome` 用 1X2 `HOME/DRAW/AWAY`（MC1 PredictionOrder 已冻结） |
| Settlement | `settlements` | `queued/calculating/review/payable/paid/failed` | 复制 05 §4 |
| SettlementBatch | `settlement_batches` | `created/processing/completed/partially_failed/failed` | Owner 2B1-ENUM-01 |
| RefundCase | `refund_cases` | `pending/approved/executing/completed/rejected/failed` | Owner 2B1-ENUM-02 |
| CorrectionCase | `correction_cases` | `pending/approved/executing/completed/rejected/failed` | Owner 2B1-ENUM-03 |
| OtcTrade | `otc_trades` | `completed`（单态） | Owner 2B1-ENUM-04，append-only |
| RobotUpgradeOrder | `robot_upgrade_orders` | `pending/processing/completed/failed/cancelled` | Owner 2B1-ENUM-05 |
| ConsentReceipt | `consent_receipts` | `active/expired` | Owner 2B1-ENUM-06 |
| AuditEvent | `audit_events` | （无状态机，append-only） | 复用 MC2 DDL |

## 2. 字段设计（05 §3 最低字段 + 冻结工程字段）

通用工程字段（design S01-P02 §通用工程约束）：

```text
object_version int unsigned NOT NULL DEFAULT 0        — CAS 乐观锁
idempotency_key varchar(64) DEFAULT NULL UNIQUE       — 幂等（可空）
audit_event_id bigint unsigned NOT NULL DEFAULT 0      — 审计指针（敏感写表）
created_time int unsigned NOT NULL DEFAULT 0           — Unix 秒
updated_time int unsigned NOT NULL DEFAULT 0           — Unix 秒（有状态流转的对象）
```

append-only 对象（OtcTrade / audit_events）**无 updated_time**，`$timestamps=false`，`UPDATED_AT=null`。

### 2.1 results

```text
result_id bigint PK
market_id bigint
event_id bigint
scores json NULL（比分，如 {"home":2,"away":1}）
outcome enum('HOME','DRAW','AWAY') NOT NULL DEFAULT 'HOME'
status enum('provisional','official','disputed','corrected') NOT NULL DEFAULT 'provisional'
confirmed_by bigint DEFAULT 0
confirmed_at int DEFAULT 0
evidence_ids json NULL
dispute_reason_code varchar(64) DEFAULT ''
correction_version int unsigned DEFAULT 0
rule_version varchar(64) DEFAULT ''
snapshot_id bigint DEFAULT 0
object_version / idempotency_key / audit_event_id / created_time / updated_time
```

### 2.2 settlements

```text
settlement_id bigint PK
market_id bigint
batch_id bigint
status enum('queued','calculating','review','payable','paid','failed') DEFAULT 'queued'
principal_total_apt decimal(36,18) DEFAULT 0
reward_total_apt decimal(36,18) DEFAULT 0
service_fee_total_apt decimal(36,18) DEFAULT 0
ledger_batch_id bigint DEFAULT 0
approved_by bigint DEFAULT 0
executed_at int DEFAULT 0
rule_version / parameter_release_id bigint / snapshot_id
object_version / idempotency_key / audit_event_id / created_time / updated_time
```

### 2.3 settlement_batches

```text
batch_id bigint PK
status enum('created','processing','completed','partially_failed','failed') DEFAULT 'created'
market_count int DEFAULT 0
order_count int DEFAULT 0
settlement_ids json NULL
total_principal_apt / total_reward_apt / total_service_fee_apt decimal(36,18)
executed_at int DEFAULT 0
rule_version / object_version / idempotency_key / audit_event_id / created_time / updated_time
```

### 2.4 refund_cases

```text
refund_id bigint PK
market_id bigint
batch_size int DEFAULT 0
principal_total_apt / service_fee_total_apt decimal(36,18)
status enum('pending','approved','executing','completed','rejected','failed') DEFAULT 'pending'
approved_by bigint DEFAULT 0
executed_at int DEFAULT 0
ledger_batch_ids json NULL
reason_code varchar(64) DEFAULT ''
case_id bigint DEFAULT 0
approval_id bigint DEFAULT 0
rule_version / snapshot_id / object_version / idempotency_key / audit_event_id / created_time / updated_time
```

### 2.5 correction_cases

```text
correction_id bigint PK
market_id bigint
result_id_old bigint / result_id_new bigint
settlement_ids_old json NULL / settlement_ids_new json NULL
status enum('pending','approved','executing','completed','rejected','failed') DEFAULT 'pending'
approved_by bigint / executed_at int
ledger_reversal_ids json NULL / ledger_new_ids json NULL
case_id bigint / approval_id bigint
evidence_ids json NULL
rule_version / snapshot_id / object_version / idempotency_key / audit_event_id / created_time / updated_time
```

### 2.6 otc_trades（append-only）

```text
trade_id bigint PK
otc_order_id bigint
buyer_user_id bigint / seller_user_id bigint
quantity_apt decimal(36,18)
price_apt decimal(18,8)
fee_apt decimal(36,18)
power_consumed decimal(18,4)
status enum('completed') NOT NULL DEFAULT 'completed'
ledger_entry_ids json NULL
ledger_batch_id bigint DEFAULT 0
idempotency_key / audit_event_id / created_time（无 updated_time，append-only）
```

### 2.7 robot_upgrade_orders

```text
upgrade_order_id bigint PK
robot_id bigint / user_id bigint
from_level int / to_level int
apt_cost decimal(36,18)
status enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending'
power_cap_after decimal(18,4)
capacities_after json NULL
cooling_end_at int DEFAULT 0
review_case_id bigint DEFAULT 0
approval_id bigint DEFAULT 0
ledger_entry_id bigint DEFAULT 0
rule_version / parameter_release_id bigint / object_version / idempotency_key / audit_event_id / created_time / updated_time
```

### 2.8 consent_receipts

```text
receipt_id bigint PK
user_id bigint
consent_type varchar(32) DEFAULT ''
consent_version varchar(32) DEFAULT ''
content_hash varchar(64) DEFAULT ''
status enum('active','expired') DEFAULT 'active'
agreed_at int DEFAULT 0
expires_at int DEFAULT 0
policy_version varchar(64) DEFAULT ''
object_version / idempotency_key / audit_event_id / created_time / updated_time
```

### 2.9 audit_events（复用 MC2 DDL，不重建）

字段对齐 MC2 `audit_events` DDL（typed reference snapshot）。AuditEvent Model 仅映射该表，不新增字段。

## 3. Model/DAO/Service 骨架规则

- Model：映射冻结表名/主键/时间列/enum；`$incrementing=false`、`$keyType='string'`；append-only 表 `$timestamps=false` + `UPDATED_AT=null`。
- DAO：只读查询封装；append-only 表（AuditEvent/OtcTrade）覆写 `delete/deleteAll/update/updateAll/updateOrCreate` 全部 fail-closed。
- Service：标 `@authoritative_writer <table>`；只透传查询，不实现任何状态流转方法（状态流转 FAIL_CLOSED，等待转移矩阵 FROZEN）。

## 4. append-only Builder 复用

- `AuditEventAppendOnlyBuilder` / `OtcTradeAppendOnlyBuilder`：复制 `AptLedgerEntryAppendOnlyBuilder` 的 DESTRUCTIVE_METHODS deny set 与全部覆写，仅改表名与错误信息。
- Model 的 `newEloquentBuilder()` 注入对应 Builder；`save()` 对已落盘实例抛 `RunException`；`delete()` 抛 `RunException`。

## 信息来源

- 05 §3（对象字段）/§4 V2.3（enum）/§8（RBAC）
- MC1 DDL `20260813_..._8_core_entities.sql`、MC2 DDL `20260815_..._audit_events.sql`
- MC1 骨架（`library/model|dao|service/**`，commit `5fb3d01`）
- 07 §S01-P03
