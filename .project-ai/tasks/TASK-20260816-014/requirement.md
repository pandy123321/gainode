# Requirement: S02-P07 · Approval / Parameter / Risk / Support / Notice / Audit（状态机 + SoD + fail-closed + 只读投影）

## 状态

- **Owner Signoff：N/A（本 task 不产生 Owner Decision Matrix，全部按已冻结/候选合同 best-effort）**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE（2B-2 状态转移矩阵未 FROZEN；涉外部依赖/经济写路径 FAIL_CLOSED）**

## 背景

STAGE-02 第 7 包。治理六域（Approval / Parameter / Risk / Support / Notice / Audit）的对象 DDL 已在 2B-2（13 对象）落盘，Model/DAO/Service 骨架已建（S01-P05）。其中 AuthSession / MfaEnrollment / KycCase 三对象状态机已在 S02-P02 落地，本包补齐其余治理六域的状态机骨架、SoD 守卫、fail-closed 写路径与只读投影。

## 范围（10 对象，六域）

```text
Approval             approval_requests       ApprovalRequestService   AR1-AR8 八态
Parameter            parameter_releases      ParameterReleaseService  PR1/PR2/PR5-PR11 八态
                     parameter_snapshots     ParameterSnapshotService append-only 只读
Risk                 risk_cases              RiskCaseService          五态（2B2-ENUM-03）
Support              tickets                 TicketService            TK1-TK8 六态
                     ticket_messages         TicketMessageService     append-only 只读
                     ticket_attachments      TicketAttachmentService  append-only 只读 + create fail-closed
Notice               notices                 NoticeService            read_state 幂等
                     notification_deliveries NotificationDeliveryService 四态（2B2-ENUM-01）
Audit                audit_events            AuditEventService        append-only + Admin 脱敏查询
```

> `SettlementMethod`（值对象/只读聚合，无状态机）与 AuthSession/MfaEnrollment/KycCase（S02-P02 已落地）不在本包六域实现范围。

## 规则（约束）

1. 领域状态全部取自 05 §4 canonical + 2B-2 Freeze（候选），禁止自创状态值。
2. 状态转移矩阵取自 `MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md`（AR1-AR8 / PR1-PR11 / TK1-TK8 / NotificationDelivery 四态 / RiskCase 五态）。
3. **纯状态转移**（只改状态 + decided_by/reviewed_by/assigned_to 等 actor 回写 + 审计 + `object_version` CAS，不写账本、不依赖外部数据源）完整实现。
4. **外部依赖写**（通知渠道、对象存储/病毒扫描、风险处置策略）一律 FAIL_CLOSED（`DEPENDENCY_UNAVAILABLE` 503）。
5. 每个转移：初态、合法转移、终态、Writer、幂等、并发（CAS）、审计（append `audit_events`）。
6. **SoD（Actor-level Invariant）**：
   - ApprovalRequest：审批人 ≠ 申请人（`guardNotSelf`）。
   - ParameterRelease：PARAM_APPROVER ≠ PARAM_EDITOR，RELEASE_OPERATOR ≠ PARAM_APPROVER（`guardNotApprover`）。
   - RiskCase：RISK_APPROVER ≠ RISK_ANALYST（detector）（`guardNotDetector`）。
   - SoD 违反抛 `POLICY_DENIED`（403）。
7. 触发者/Writer 仅用 05 §8 已冻结角色（END_USER / OPS_OPERATOR / ADMIN_SECURITY / PARAM_EDITOR / PARAM_APPROVER / RELEASE_OPERATOR / KYC_REVIEWER / RISK_ANALYST / RISK_APPROVER / SUPPORT_AGENT / 系统），不自创角色。
8. 金额 decimal string，禁 float；非法转移一律 `OBJECT_VERSION_CONFLICT`（409）。
9. append-only 对象（ParameterSnapshot / TicketMessage / TicketAttachment / AuditEvent）Model/Builder/DAO 三层防护已在 STAGE-01 落地，本包只补只读投影与 fail-closed 入口，不放开覆盖/删除。

## 状态分类

- **ApprovalRequest**：TRUE_TERMINAL = `executed` / `rejected`；RETRYABLE_TERMINAL = `failed`（形成新执行对象）；INTERMEDIATE = `pending / changes_requested / approved / executing`。
- **ParameterRelease**：TRUE_TERMINAL = `archived`；STABLE = `active`（可 paused/rolled_back）；INTERMEDIATE = `pending_approval / approved / scheduled / paused`。
- **RiskCase**：TRUE_TERMINAL = `closed`；`open → investigating → under_review → resolved → closed` + `open → closed`（误报）+ `resolved → investigating`（申诉重开，appeal_eligible 守卫）。
- **Ticket**：TRUE_TERMINAL = `closed`；STABLE = `resolved`（可重开/关闭）；INTERMEDIATE = `in_progress / waiting_user / under_review`。
- **NotificationDelivery**：终态 = `delivered / cancelled`；`failed → pending`（重试，attempt_count 递增）。
- **Notice**：`read_state` 字段 unread→read 幂等（无状态机）。

## fail-closed 边界（依赖未冻结，写操作 closed）

| 依赖 | 冻结状态 | 受影响写操作 |
|---|---|---|
| 通知渠道服务（PUSH/EMAIL/SMS/IN_APP） | TBC | NotificationDelivery.deliver |
| 对象存储 + 病毒/类型/大小策略 | TBC | TicketAttachment.create |
| 风险处置策略（restrictions 冻结/放行） | TBC | RiskCase.execute |

## 合同缺口（登记，不阻塞）

- **PR3/PR4**：`ParameterRelease` 候选合同引用 `changes_requested` 状态，但该状态不在 2B-2 冻结的 8 态 canonical enum 内（`draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived`）。按「目标态不存在 = fail-closed」原则，PR3/PR4 不实现，仅实现 PR1/PR2/PR5-PR11。需 Owner 决策（合同缺口 `NEEDS_OWNER_DECISION`）。

## 非目标（NON_GOALS）

- 不新增 DDL（13 对象表已在 S01-P05 建）。
- 不实现通知渠道/对象存储/风险处置的真实外部调用。
- 不实现 Controller 层（OpenAPI 路径骨架沿用 S02-P01，业务 request/response 本包补 schema）。
- 不重写 AuthSession/MfaEnrollment/KycCase（S02-P02 已落地）。
- 不重写 SettlementMethod（值对象，无状态机）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3/§4/§8/§11）
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（§S02-P07）
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md`（§3/§4/§7/§8）
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2B2_13_ENTITIES.sql`
- `.project-ai/rules/coding.md`
- `.project-ai/tasks/TASK-20260816-013/design.md`（S02-P06 状态机先例）
