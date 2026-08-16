# Requirement: Machine Contract 第二批 2B-2（DDL 与骨架）

## 状态

- **Owner Signoff：完成（3 缺 enum 对象已裁决，2026-08-16，见 S01-P04）**
- **Independent Review：未开始**
- **冻结状态：2B-2 状态合同 CANDIDATE（转移矩阵未 FROZEN）**

## 背景

S01-P04（`TASK-20260816-003`）已完成 2B-2 状态合同补齐：5 复用对象 enum 复制 05；3 缺 enum 对象（NotificationDelivery/MfaEnrollment/RiskCase）已 Owner 裁决并补入 05 §4（V2.4）；5 值对象/只读聚合无状态机。

本 task（S01-P05）执行 **2B-2 DDL 与 Model/DAO/Service 骨架**。转移矩阵未 FROZEN，故**本包仅骨架 + fail-closed guard，不实现状态转移业务**（同 S01-P03 先例）。

## 范围

固定对象（13 个，全部建表）：

```text
ApprovalRequest
ParameterRelease
ParameterSnapshot
Notice
NotificationDelivery
AuthSession
MfaEnrollment
KycCase
RiskCase
Ticket
TicketMessage
TicketAttachment
SettlementMethod
```

## 目标文件

```text
sql/20260816_machine_contract_batch2b2_13_entities.sql
library/{model,dao,service}/approval/ApprovalRequest*
library/{model,dao,service}/parameter/{ParameterRelease,ParameterSnapshot}*
library/{model,dao,service}/notice/{Notice,NotificationDelivery}*
library/{model,dao,service}/auth/{AuthSession,MfaEnrollment}*
library/{model,dao,service}/kyc/KycCase*
library/{model,dao,service}/risk/RiskCase*
library/{model,dao,service}/support/{Ticket,TicketMessage,TicketAttachment}*
library/{model,dao,service}/settlement/SettlementMethod*
```

## 规则（约束）

1. **DDL forward-only**，不改 MC1/MC2/2B-1 历史文件；全新表，禁止 DROP/CREATE IF NOT EXISTS。
2. **Snowflake 主键** bigint unsigned，`$incrementing=false`，`$keyType='string'`。
3. **enum 严格对齐 05 §4**（复用对象）+ Owner 裁决（3 对象），禁止自创状态值。
4. **金额 decimal 禁 float**；本包无 APT 金额（P1/P2 对象），decimal 仅用于字段约定占位（如有）。
5. **时间** `created_time/updated_time` int unsigned（Unix 秒）。
6. **append-only 对象**（ParameterSnapshot/TicketMessage/TicketAttachment）无 `object_version`、无 `updated_time`，用三层防护（Builder + Model + DAO），同 OtcTrade/AuditEvent 先例。
7. **可变对象**（其余 10 个）标准字段：object_version + idempotency_key + audit_event_id + created_time + updated_time。
8. **NotificationDelivery 幂等用 `dedupe_key`**（05 §4 Notice 原则 3），不额外加 idempotency_key。
9. **DAO 只提供查询**；Service 唯一写入者标 `@authoritative_writer`。
10. **转移矩阵未 FROZEN**：任何状态流转 MUST FAIL_CLOSED，不实现结算/审批/投递业务。

## 非目标（NON_GOALS）

- 不实现任何状态转移方法（审批/参数激活/MFA 验证/风控处置/工单流转/通知投递全部 FAIL_CLOSED）。
- 不新增未冻结字段（仅 05 §3 最低字段 + 冻结工程字段）。
- 不修改 MC1/MC2/2B-1 冻结文件。
- 不触碰 S01-P04 锁定文件（05 §4 V2.4 已冻结，本包不修改）。
- 不实现 OpenAPI / 路由 / 控制器（属后续包）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§2.2/§3/§4 V2.4/§8/§11）
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md`（2B-2 Freeze Candidate）
- `.project-ai/tasks/TASK-20260816-003/design.md`（Part A/B/C/D）
- `0.5代码/gainode后端/gainode/sql/20260816_machine_contract_batch2b1_8_entities.sql`（2B-1 DDL 先例）
- `.project-ai/rules/coding.md`（数据库规则）
