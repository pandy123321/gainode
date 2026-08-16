# Design: S02-P07 · Approval / Parameter / Risk / Support / Notice / Audit（状态机 + SoD + fail-closed + 只读投影）

## Part A — 状态转移矩阵（2B-2 Freeze 候选）

### A1. ApprovalRequest（AR1-AR8，八态）

| # | 从 → 到 | 触发者 | Guard | Service 方法 | Event |
|---|---|---|---|---|---|
| AR1 | draft → pending | 申请人 | 对象存在、字段完整 | `submit` | APPROVAL_SUBMITTED |
| AR2 | pending → changes_requested | 审批人 | 审批人 ≠ 申请人 | `requestChanges` | APPROVAL_CHANGES_REQUESTED |
| AR3 | changes_requested → pending | 申请人 | 重提不篡改原记录 | `resubmit` | APPROVAL_RESUBMITTED |
| AR4 | pending → approved | 审批人 | 审批人 ≠ 申请人 | `approve` | APPROVAL_APPROVED |
| AR5 | pending → rejected | 审批人 | 审批人 ≠ 申请人 | `reject` | APPROVAL_REJECTED |
| AR6 | approved → executing | 系统/审批人 | 批准完成 | `startExecution` | APPROVAL_EXECUTION_STARTED |
| AR7 | executing → executed | 系统 | 执行成功 | `completeExecution` | APPROVAL_EXECUTION_COMPLETED |
| AR8 | executing → failed | 系统 | 执行异常 | `failExecution` | APPROVAL_EXECUTION_FAILED |

### A2. ParameterRelease（PR1/PR2/PR5-PR11，八态）

| # | 从 → 到 | 触发者 | Guard | Service 方法 | Event |
|---|---|---|---|---|---|
| PR1 | draft → pending_approval | PARAM_EDITOR | 值完整、diff 有效 | `submit` | PARAMETER_RELEASE_SUBMITTED |
| PR2 | pending_approval → approved | PARAM_APPROVER | 审批人 ≠ 编辑人 | `approve` | PARAMETER_RELEASE_APPROVED |
| PR5 | approved → scheduled | RELEASE_OPERATOR | 操作者 ≠ 审批人 | `schedule` | — |
| PR6 | approved → active | RELEASE_OPERATOR | 操作者 ≠ 审批人 | `activateFromApproved` | PARAMETER_RELEASE_ACTIVATED |
| PR7 | scheduled → active | 系统/RELEASE_OPERATOR | 到期或提前 | `activateFromScheduled` | PARAMETER_RELEASE_ACTIVATED |
| PR8 | active → paused | RELEASE_OPERATOR | 临时停用 | `pause` | — |
| PR9 | paused → active | RELEASE_OPERATOR | 恢复 | `resume` | — |
| PR10 | active/paused → rolled_back | RELEASE_OPERATOR | 回滚保留审计链 | `rollback` | — |
| PR11 | rolled_back/scheduled → archived | RELEASE_OPERATOR | 不可再激活 | `archive` | — |

> PR3/PR4 因 `changes_requested` 不在 2B-2 canonical 8 态内（合同缺口）不实现，fail-closed。

### A3. RiskCase（五态，2B2-ENUM-03）

| # | 从 → 到 | 触发者 | Guard | Service 方法 | Event |
|---|---|---|---|---|---|
| R1 | open → investigating | RISK_ANALYST | — | `startInvestigate` | RISK_CASE_INVESTIGATING |
| R2 | investigating → under_review | RISK_ANALYST | — | `submitDecision` | — |
| R3 | under_review → resolved | RISK_APPROVER | 审批人 ≠ 分析人 | `resolve` | RISK_CASE_RESOLVED |
| R4 | resolved → closed | RISK_APPROVER | — | `closeResolved` | — |
| R5 | open → closed | RISK_APPROVER | 误报 | `closeFalsePositive` | — |
| R6 | resolved → investigating | RISK_APPROVER | appeal_eligible=1 | `reopenAppeal` | — |

### A4. Ticket（TK1-TK8，六态）

| # | 从 → 到 | 触发者 | Guard | Service 方法 | Event |
|---|---|---|---|---|---|
| TK1 | submitted → in_progress | SUPPORT_AGENT | 分配处理人 | `accept` | TICKET_ACCEPTED |
| TK2 | in_progress → waiting_user | SUPPORT_AGENT | — | `waitUser` | — |
| TK3 | waiting_user → in_progress | END_USER | — | `userReplied` | — |
| TK4 | in_progress → under_review | SUPPORT_AGENT | — | `escalate` | — |
| TK5 | under_review → resolved | SUPPORT_AGENT | — | `resolve` | TICKET_RESOLVED |
| TK6 | in_progress → resolved | SUPPORT_AGENT | — | `resolve` | TICKET_RESOLVED |
| TK7 | resolved → closed | 系统/SUPPORT_AGENT | — | `close` | — |
| TK8 | resolved → in_progress | END_USER/SUPPORT_AGENT | appeal_eligible=1 | `reopen` | — |

### A5. NotificationDelivery（四态，2B2-ENUM-01）

| # | 从 → 到 | 触发者 | Service 方法 | Event |
|---|---|---|---|---|
| N1 | pending → delivered | 系统 | `markDelivered` | NOTIFICATION_DELIVERED |
| N2 | pending → failed | 系统 | `markFailed` | NOTIFICATION_DELIVERY_FAILED |
| N3 | failed → pending | 系统 | `retry`（attempt_count+1） | — |
| N4 | pending → cancelled | 系统 | `cancel` | — |

## Part B — 实现策略（与 S02-P05/P06 一致）

1. **纯状态转移完整实现**：每个方法走 `TransactionBoundary` + `get` 状态守卫 + `appendAudit` + `object_version` CAS 原子 UPDATE，回写 actor 字段（`decided_by`/`approved_by`/`reviewed_by`/`assigned_to`）与 `audit_event_id`。
   - 非法转移（当前状态不在 from 集合）抛 `OBJECT_VERSION_CONFLICT`（409）。
   - CAS 失败（object_version 不匹配）抛 `OBJECT_VERSION_CONFLICT`（409）。
2. **SoD 守卫**：
   - `ApprovalRequestService::guardNotSelf`：审批人 == 申请人 → `POLICY_DENIED`（403）。
   - `ParameterReleaseService::guardNotApprover`：RELEASE_OPERATOR == approved_by → `POLICY_DENIED`。
   - `RiskCaseService::guardNotDetector`：RISK_APPROVER == detected_by → `POLICY_DENIED`。
   - Ticket `reopen` / RiskCase `reopenAppeal`：`appeal_eligible != 1` → `POLICY_DENIED`。
3. **外部依赖写 FAIL_CLOSED**：
   - `NotificationDeliveryService::deliver` 依赖通知渠道（TBC）→ `DEPENDENCY_UNAVAILABLE`（503）。
   - `TicketAttachmentService::create` 依赖对象存储/病毒扫描（TBC）→ `DEPENDENCY_UNAVAILABLE`（503）。
   - `RiskCaseService::execute` 依赖风险处置策略（TBC）→ `DEPENDENCY_UNAVAILABLE`（503）。
4. **append-only 对象**：ParameterSnapshot / TicketMessage / TicketAttachment / AuditEvent 只读投影（detail / listByX / getByX）透传 DAO；create 入口仅 AuditEvent（内部）与 fail-closed 的 TicketAttachment。参数快照/消息/附件不覆盖、不删除。
5. **审计脱敏**：`AuditEventService::listAdmin` / `detail` 对 AUDITOR 角色做字段脱敏（不暴露 `before_snapshot_type` 等内部结构），数据范围双重脱敏。

## Part C — 文件清单

| 文件 | 动作 | 说明 |
|---|---|---|
| `library/service/approval/ApprovalRequestService.php` | 重写 | AR1-AR8 + EVENT_ 常量 + guardNotSelf + detail + appendAudit |
| `library/service/parameter/ParameterReleaseService.php` | 重写 | PR1/PR2/PR5-PR11 + guardNotApprover + detail/getActive/getByIdempotencyKey |
| `library/service/parameter/ParameterSnapshotService.php` | 实现 | getByRelease/listByRelease/detail 只读投影 |
| `library/service/risk/RiskCaseService.php` | 重写 | 五态 + guardNotDetector + execute fail-closed + detail/listByUser |
| `library/service/support/TicketService.php` | 重写 | TK1-TK8 + appeal 守卫 + detail/listByUser |
| `library/service/support/TicketMessageService.php` | 实现 | getByTicket/listByTicket/detail 只读投影 |
| `library/service/support/TicketAttachmentService.php` | 实现 | create fail-closed + getByTicket/listByTicket/detail 只读 |
| `library/service/notice/NoticeService.php` | 重写 | markRead 幂等 + detail/listByUser |
| `library/service/notice/NotificationDeliveryService.php` | 重写 | 四态 + deliver fail-closed + detail/listByNotice/getByDedupeKey |
| `library/service/audit/AuditEventService.php` | 扩展 | listAdmin/detail 脱敏查询 + create/getByTarget/getByActor/getByEventCode |
| `openapi/components/schemas/governance.yaml` | 新建 | 10 schema（ApprovalRequest/ParameterRelease/ParameterSnapshot/RiskCase/Ticket/TicketMessage/TicketAttachment/Notice/NotificationDelivery/AuditEvent） |
| `openapi/paths/policy_parameter.yaml` | 更新 | ParameterRelease/ParameterSnapshot summary 补 fail-closed 说明 |
| `openapi/paths/admin.yaml` | 更新 | ApprovalRequest/RiskCase/AuditEvent summary 补 fail-closed 说明 |
| `openapi/gainode-v2.yaml` | 更新 | 注册 10 个 governance schema |
| `tests/Contract/S02P07PolicyContractTest.php` | 新建 | 状态常量/Event Catalog/fail-closed/错误码 HTTP 映射（34 断言） |
| `tests/Integration/S02P07PolicyStateMachineTest.php` | 新建 | SQLite 内存库状态机/SoD/append-only/脱敏/fail-closed/只读投影（61 断言） |

## Part D — 验证矩阵（07 §S02-P07 验证项映射）

| 07 验证项 | 本包落地方式 |
|---|---|
| self-approval | ApprovalRequest guardNotSelf → POLICY_DENIED |
| role switching bypass | ParameterRelease guardNotApprover / RiskCase guardNotDetector |
| double decision/consume | CAS object_version + 终态 OBJECT_VERSION_CONFLICT |
| parameter active mutation | active 值不可直接改（纯状态转移只改 status + actor 回写） |
| rollback snapshot | rollback 生成回滚 ParameterSnapshot（副作用参数冻结后附加，本包状态转移） |
| risk restriction | execute fail-closed DEPENDENCY_UNAVAILABLE |
| attachment abuse | TicketAttachment.create fail-closed（对象存储/病毒 TBC） |
| notice duplicate/failure | NotificationDelivery dedupe_key + deliver fail-closed + failed→pending 重试 |
| audit tamper/field leakage | AuditEvent append-only + listAdmin/detail 脱敏 |
| super-admin bypass | SoD 为 actor-level invariant，不因角色缺省绕行 |

## 信息来源

- `MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md` §3/§4/§7/§8
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md` §3/§4/§8/§11
- `07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P07
- `.project-ai/tasks/TASK-20260816-013/design.md`（S02-P06 先例）
