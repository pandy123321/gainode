# Acceptance: S02-P07 · Approval / Parameter / Risk / Support / Notice / Audit（状态机 + SoD + fail-closed + 只读投影）

## 机械验收（可自动验证）

| 项 | 命令 / 证据 | 期望 | 实际 |
|---|---|---|---|
| Contract 测试 | `php tests/Contract/S02P07PolicyContractTest.php` | 34 断言全过 | ✅ 34/34 |
| Integration 测试 | `php tests/Integration/S02P07PolicyStateMachineTest.php` | 61 断言全过 | ✅ 61/61 |
| PHP lint | `php -l`（12 文件） | 无语法错误 | ✅ |
| OpenAPI YAML 解析 | pyyaml safe_load（governance.yaml/policy_parameter.yaml/admin.yaml/gainode-v2.yaml） | 全部可解析 | ✅ |
| OpenAPI schema 注册 | gainode-v2.yaml components.schemas | ApprovalRequest/ParameterRelease/ParameterSnapshot/RiskCase/Ticket/TicketMessage/TicketAttachment/Notice/NotificationDelivery/AuditEvent 10 项 | ✅ |

## 业务验收（人工/复核）

1. **ApprovalRequest AR1-AR8**：八态状态机按 2B-2 §3.2 转移矩阵，非法转移（executed→*、rejected→*、approved→changes_requested、draft→approved）抛 `OBJECT_VERSION_CONFLICT`（409）。
2. **ParameterRelease PR1/PR2/PR5-PR11**：八态状态机；PR3/PR4 因 `changes_requested` 不在 canonical 8 态内（合同缺口）不实现，fail-closed。
3. **RiskCase 五态**：open→investigating→under_review→resolved→closed + 误报 open→closed + 申诉 resolved→investigating（appeal_eligible 守卫）。
4. **Ticket TK1-TK8**：六态状态机 + `resolved→in_progress` 重开（appeal_eligible 守卫）+ `resolved→closed` 终态保护。
5. **NotificationDelivery 四态**：pending→delivered/failed/cancelled + failed→pending 重试（attempt_count 递增）。
6. **SoD（Actor-level）**：ApprovalRequest 自批 / ParameterRelease 操作者=审批人 / RiskCase 审批人=检测人 → `POLICY_DENIED`（403）。
7. **fail-closed**：NotificationDelivery.deliver / TicketAttachment.create / RiskCase.execute 全部抛 `DEPENDENCY_UNAVAILABLE`（503），未用 TBC 依赖补洞。
8. **append-only**：ParameterSnapshot/TicketMessage/TicketAttachment/AuditEvent 无覆盖/删除路径；仅追加与只读投影。
9. **审计脱敏**：AuditEvent listAdmin/detail 对 AUDITOR 脱敏，不暴露内部快照结构。
10. **审计与 CAS**：每个转移写 `audit_events` + actor 回写 + `object_version` 递增，并发冲突 CAS 决胜。
11. **只读投影**：detail/listByUser/listByRelease/listByTicket/listByNotice/getByDedupeKey 只读，不改状态。
12. **不新增 DDL**：13 对象表沿用 S01-P05 已建表。

## 停止条件核验（07 §S02-P06 六域 + §S02-P07 停止条件）

- [x] 通知渠道未签（TBC）→ NotificationDelivery.deliver closed，保留状态机 + 只读投影。
- [x] 附件对象存储/病毒策略未签 → TicketAttachment.create closed，保留只读投影。
- [x] 风险处置策略未签 → RiskCase.execute closed，保留状态机 + 只读投影。
- [x] 紧急操作（MFA/case_id/reason/evidence/48h post-review）未签 → 本包不实现 Emergency 写路径（六域外）。
- [x] 合同缺口 PR3/PR4（`changes_requested`）→ 登记 `NEEDS_OWNER_DECISION`，不中断整体进度。

## 交接物

- 10 个 Service 重写/实现（ApprovalRequest / ParameterRelease / ParameterSnapshot / RiskCase / Ticket / TicketMessage / TicketAttachment / Notice / NotificationDelivery / AuditEvent）
- 1 个 OpenAPI schema 新增（governance.yaml）+ 3 个 OpenAPI 更新（policy_parameter.yaml / admin.yaml / gainode-v2.yaml）
- 2 个测试新增（Contract/Integration，共 95 断言）
- 1 个 TASK 三件套（本目录）
- context.md + manifest.yaml 指针更新
