# Acceptance: Machine Contract 第二批 2B-1（状态合同补齐）

## 状态

- **Owner Signoff：未完成**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE**

## 验收范围

本 task 只验收「状态合同补齐」产出（文档），不验收 DDL/代码（属 S01-P03）。

## 验收清单

| # | 验收项 | 状态 |
|---|---|---|
| 1 | Result 复制 05 §4 canonical enum `provisional/official/disputed/corrected`，未新增状态值 | 待独立审核 |
| 2 | Settlement 复制 05 §4 canonical enum `queued/calculating/review/payable/paid/failed`，未新增状态值 | 待独立审核 |
| 3 | Result 每个转移定义：初态、合法转移、终态、触发者、Writer、幂等、并发、审计、账本效果 | 待独立审核 |
| 4 | Settlement 每个转移定义：同上 | 待独立审核 |
| 5 | AuditEvent 复用 MC2 `audit_events` DDL，未重复创建、未改 append-only 约束 | 待独立审核 |
| 6 | 6 缺 enum 实体（SettlementBatch/RefundCase/CorrectionCase/OtcTrade/RobotUpgradeOrder/ConsentReceipt）仅生成 Owner Decision Matrix + 候选状态合同摘要（design.md D.7，标注「候选/非冻结」），未自创/冻结状态 | 待独立审核 |
| 7 | 触发者/Writer 仅使用 05 §8 已冻结角色，未自创角色 | 待独立审核 |
| 8 | 未批准状态一律 FAIL_CLOSED（未建表、未写业务） | 待独立审核 |
| 9 | Result/Settlement 转移矩阵与 MC2 已冻结协同关系（M6/M7/M9/M10/M12、P3/P5/P6/P7/P10/P11/P12、结算会计矩阵）一致 | 待独立审核 |

## 机械一致性断言（冻结前 gate）

```text
RESULT_ENUM_MATCHES_05 = YES
SETTLEMENT_ENUM_MATCHES_05 = YES
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_ROLE = YES
AUDIT_EVENTS_REUSED_NOT_RECREATED = YES
OWNER_DECISION_MATRIX_COUNT = 6
CANDIDATE_STATE_SUMMARY_NOT_FROZEN = YES
UNFROZEN_STATE_FAIL_CLOSED = YES
```

## 阻塞项与恢复条件

| 阻塞对象 | 状态 | 恢复条件 |
|---|---|---|
| SettlementBatch/RefundCase/CorrectionCase/OtcTrade/RobotUpgradeOrder/ConsentReceipt 的 enum | CONTRACT_GAP / FAIL_CLOSED | Owner 裁决 enum → 补 05 §4 → 冻结 |

## 交付物

- `.project-ai/tasks/TASK-20260816-001/requirement.md`
- `.project-ai/tasks/TASK-20260816-001/design.md`
- `.project-ai/tasks/TASK-20260816-001/acceptance.md`

> 说明：2B-1 冻结正文（`sql/MACHINE_CONTRACT_BATCH2B1_STATE_FREEZE.md`）不在此包生成——本包 design.md 已完整承载 Result/Settlement 转移矩阵 + 6 实体 Owner Decision Matrix + AuditEvent 复用。冻结正文将在 **S01-P03（2B-1 DDL 与骨架）** 生成 DDL 时一并落盘（与 MC1/MC2 freeze 文档同目录、同流程），届时随 `.sql` 一起进入版本控制。

## 非目标验证（明确 NOT_RUN，属后续包）

```text
DDL_CREATED = NOT_RUN（属 S01-P03）
MODEL_DAO_SERVICE = NOT_RUN（属 S01-P03）
php -l = NOT_RUN（本包无 PHP 代码）
composer test = NOT_RUN（本包无代码变更）
```
