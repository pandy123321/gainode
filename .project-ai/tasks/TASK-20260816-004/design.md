# Design: Machine Contract 第二批 2B-2（DDL 与骨架）

## 状态

- **Owner Signoff：完成（3 缺 enum 对象已裁决，见 S01-P04）**
- **Independent Review：未开始**
- **冻结状态：2B-2 状态合同 CANDIDATE；本包骨架 + fail-closed**

## 权威依据

- 角色（05 §8）：`END_USER/SUPPORT_AGENT/OPS_OPERATOR/KYC_REVIEWER/RISK_ANALYST/RISK_APPROVER/LEDGER_OPERATOR/FINANCE_REVIEWER/PARAM_EDITOR/PARAM_APPROVER/RELEASE_OPERATOR/AUDITOR/ADMIN_SECURITY`。
- 职责分离（05 §8/§11）：`PARAM_EDITOR != PARAM_APPROVER != RELEASE_OPERATOR`；`RISK_ANALYST != RISK_APPROVER`；申请人不得审批本人申请。

## 表设计（13 表）

> 主键 Snowflake bigint unsigned；时间 int unsigned Unix 秒；enum 严格对齐 05 §4 V2.4 / Owner 裁决。
> 分类：**标准（可变）** = object_version + idempotency_key + audit_event_id + created_time + updated_time；
> **append-only（不可变）** = idempotency_key + audit_event_id + created_time（无 object_version / updated_time）。

### 1. approval_requests（ApprovalRequest，工作流，标准）

主键 `approval_id`。字段：`approval_id, request_type, request_object_type, request_object_id, status(enum 8态), submitted_by, submitter_role, assigned_to, decided_by, decided_at, reason_key, changes_requested_reason, execution_id, case_id` + 标准工程字段。

### 2. parameter_releases（ParameterRelease，工作流，标准）

主键 `release_id`。字段：`release_id, parameter_keys(json), status(enum 8态), draft_version, approved_by, scheduled_at, activated_at, paused_at, rolled_back_at, archived_at, monitoring_job_id, snapshot_id, case_id, audit_event_ids(json)` + 标准工程字段。

### 3. parameter_snapshots（ParameterSnapshot，只读聚合，append-only）

主键 `snapshot_id`。字段：`snapshot_id, release_id, parameter_keys(json), parameter_values(json), version, created_by` + append-only 工程字段（idempotency_key + audit_event_id + created_time）。

### 4. notices（Notice，只读聚合，标准——read_state 可变）

主键 `notice_id`。字段：`notice_id, user_id, notice_type, title_key, body_key, priority(enum INFO/WARNING/CRITICAL), related_object_type, related_object_id, read_state(enum unread/read), content_version, locale, expires_at` + 标准工程字段。

### 5. notification_deliveries（NotificationDelivery，工作流，标准）

主键 `delivery_id`。字段：`delivery_id, notice_id, channel(enum PUSH/EMAIL/SMS/IN_APP), delivery_status(enum 4态), dedupe_key(UNIQUE), attempt_count, last_attempt_at, next_retry_at, delivered_at, failure_reason_code` + 标准工程字段（**幂等用 dedupe_key，不加 idempotency_key**）。

### 6. auth_sessions（AuthSession，实体，标准）

主键 `session_id`。字段：`session_id, user_id, token_hash, status(enum 5态), device_info(json), ip_address, mfa_verified(tinyint), expires_at` + 标准工程字段。

### 7. mfa_enrollments（MfaEnrollment，实体，标准）

主键 `enrollment_id`。字段：`enrollment_id, user_id, method_type, status(enum 3态), enrolled_at, last_verified_at, backup_codes_active(tinyint), device_info(json)` + 标准工程字段。

### 8. kyc_cases（KycCase，工作流，标准）

主键 `case_id`。字段：`case_id, user_id, kyc_level, status(enum 6态), submitted_at, reviewed_at, reviewed_by, reason_code, reason_text_key, next_action, policy_version, rule_version` + 标准工程字段。

### 9. risk_cases（RiskCase，工作流，标准）

主键 `case_id`。字段：`case_id, user_id, risk_type, severity, status(enum 5态), detected_at, detected_by, reviewed_by, disposition, disposition_reason_key, restrictions(json), appeal_eligible(tinyint)` + 标准工程字段。

### 10. tickets（Ticket，工作流，标准）

主键 `ticket_id`。字段：`ticket_id, user_id, category, status(enum 6态), assigned_to, last_activity_at, resolution_type, resolution_summary_key, appeal_eligible(tinyint), ticket_message_ids(json), case_id` + 标准工程字段。

### 11. ticket_messages（TicketMessage，值对象，append-only）

主键 `message_id`。字段：`message_id, ticket_id, sender_role, body_key, attachments(json)` + append-only 工程字段。

### 12. ticket_attachments（TicketAttachment，值对象，append-only）

主键 `attachment_id`。字段：`attachment_id, ticket_id, ticket_message_id, file_type, file_url, file_hash, uploaded_by` + append-only 工程字段。

### 13. settlement_methods（SettlementMethod，值对象/只读聚合，标准——verification_status 可变）

主键 `method_id`。字段：`method_id, user_id, currency, method_type, is_default(tinyint), verification_status` + 标准工程字段。

## enum 全集（05 §4 V2.4 / §2.2 / Owner 裁决）

```text
ApprovalRequest = draft/pending/changes_requested/approved/rejected/executing/executed/failed
ParameterRelease = draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived
AuthSession = active/mfa_required/restricted/expired/revoked
KycCase = not_started/pending/needs_info/approved/rejected/review
Ticket = submitted/in_progress/waiting_user/under_review/resolved/closed
NotificationDelivery = pending/delivered/failed/cancelled（Owner 2B2-ENUM-01）
MfaEnrollment = pending/active/revoked（Owner 2B2-ENUM-02）
RiskCase = open/investigating/under_review/resolved/closed（Owner 2B2-ENUM-03）
```

## 工程约束

| 维度 | 约束 |
|---|---|
| 主键 | Snowflake bigint unsigned（`$incrementing=false`，`$keyType='string'`） |
| 并发 | 可变表 `object_version int unsigned` CAS 乐观锁（append-only 表无） |
| 幂等 | 可变表 `idempotency_key varchar(64) UNIQUE` 可空；NotificationDelivery 用 `dedupe_key` |
| 审计 | 每表 `audit_event_id` 指针 + append `audit_events` |
| 时间 | `created_time/updated_time` int unsigned（Unix 秒）；append-only 无 updated_time |
| 状态列 | 领域状态 ENUM（冻结 enum） |
| 失败安全 | 转移矩阵未冻结，状态流转 FAIL_CLOSED，不写业务 |

## append-only 三层防护（同 OtcTrade/AuditEvent）

- ParameterSnapshot、TicketMessage、TicketAttachment 三个值对象/只读聚合创建后不可变。
- **Model 层**：`$timestamps=false` + `UPDATED_AT=null` + `save()`（已落盘抛异常）+ `delete()`（抛异常）+ `newEloquentBuilder()` 注入 Builder。
- **Builder 层**：`*AppendOnlyBuilder` 覆盖 12 个 destructive 方法（同 OtcTradeAppendOnlyBuilder deny set），仅改表名/错误信息。
- **DAO 层**：覆写 `delete/deleteAll/update/updateAll/updateOrCreate` 全部 fail-closed。

## 信息来源

- 05 §2.2/§3/§4 V2.4/§8/§11
- `MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md`
- `.project-ai/tasks/TASK-20260816-003/design.md`
- `20260816_machine_contract_batch2b1_8_entities.sql`
- `OtcTradeAppendOnlyBuilder.php` / `OtcTradeModel.php` / `OtcTradeDao.php`（append-only 模板）
