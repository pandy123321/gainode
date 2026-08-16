# REVIEW_REQUEST — S01-P02 · 2B-1 状态合同补齐（含 Owner enum 裁决）

## 审核头部

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-1 小批状态合同）
PACKAGE_ID = S01-P02-2B1-STATE-CONTRACT
IMPLEMENTATION_COMMIT = a32918c
BASE_COMMIT = 4bcf80fee4cb9603688465261a0ab5091eab7e07
REVIEW_RANGE = 4bcf80f..a32918c
PACKAGE_SHA256 = 4cb17ee80e5cd47d802181fafe71b8ee4f046d0e4caaa24a86f92765cd64d20f
PREVIOUS_PACKAGE = S01-P01-MC2-REVIEW-LOCK（Round 7 APPROVED，MC2 已 FROZEN）
```

## 范围（Scope）

本包审核 **2B-1 状态合同补齐**的完整交付（5 文件）：

```text
A .project-ai/tasks/TASK-20260816-001/requirement.md
A .project-ai/tasks/TASK-20260816-001/design.md
A .project-ai/tasks/TASK-20260816-001/acceptance.md
M Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md（§4 补入 6 enum，V2.3）
A 0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2B1_STATE_FREEZE.md（2B-1 Freeze Candidate）
```

> `manifest.yaml` 的 `machine_contract_batch2b1_state_contract` decisionSource 与 `contextVersion` 为治理/进度记录，不属合同正文，不在本包审核范围（另行发布上下文）。

## 非目标（NON_GOALS）

- 不生成 2B-1 DDL（属 S01-P03）。
- 不写 PHP Model/DAO/Service（属 S01-P03）。
- 不自创 canonical state、不自创角色、不自创 API。
- 不涉及 2B-2 对象（属 S01-P04）。

## 审核对象（固定 9 对象）

1. **Result**：复制 05 §4 enum `provisional/official/disputed/corrected` + 转移矩阵 RS1-RS5（候选）。
2. **Settlement**：复制 05 §4 enum `queued/calculating/review/payable/paid/failed` + 转移矩阵 ST1-ST7（候选）。
3. **SettlementBatch / RefundCase / CorrectionCase / OtcTrade / RobotUpgradeOrder / ConsentReceipt**（6 缺 enum 实体）：经 Owner 逐项裁决（2026-08-16）补入 05 §4，转移矩阵以 Freeze Candidate §5 摘要承载（候选）。
4. **AuditEvent**：复用 MC2 `audit_events` DDL，不重复创建。

## Owner 裁决（2026-08-16，已记录）

```text
2B1-ENUM-01 SettlementBatch   = created / processing / completed / partially_failed / failed
2B1-ENUM-02 RefundCase        = pending / approved / executing / completed / rejected / failed
2B1-ENUM-03 CorrectionCase    = pending / approved / executing / completed / rejected / failed
2B1-ENUM-04 OtcTrade          = completed（append-only 单态）
2B1-ENUM-05 RobotUpgradeOrder = pending / processing / completed / failed / cancelled
2B1-ENUM-06 ConsentReceipt    = active / expired（两态）
```

## 关键不变量（必须核对）

```text
Result enum == 05 §4 canonical（未新增状态值）
Settlement enum == 05 §4 canonical（未新增状态值）
6 实体 enum == 05 §4 V2.3 补入值（一致）
Result official != Settlement paid
Result confirmer != Settlement approver（05 §8 SoD）
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_ROLE = YES（触发者/Writer 仅用 05 §8 已有角色）
AUDIT_EVENTS_REUSED_NOT_RECREATED = YES
OWNER_DECISION_MATRIX_COUNT = 6
TRANSITION_MATRICES_NOT_FROZEN = YES（转移矩阵候选，未 FROZEN）
```

## 审核绑定（REVIEW_BINDING）

```text
IMPLEMENTATION_COMMIT = a32918c
PACKAGE_SHA256 = 4cb17ee80e5cd47d802181fafe71b8ee4f046d0e4caaa24a86f92765cd64d20f
DIFF_UNTUNCATED = YES（DIFF.txt = 42282 字符）
SECRET_SCAN = PASS（0 hits）
```

## 请求结论

请按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具完整审核，最终给出：

```text
VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =
REVIEW_COMPLETENESS =
NEXT_PACKAGE_RECOMMENDATION = S01-P03_AUTHORIZED / NOT_AUTHORIZED
```

说明：

- enum（Result/Settlement 复制 + 6 实体 Owner 裁决）已确定，但**转移矩阵均为候选**，需 State Machine gate 审核通过后才视为 FROZEN。
- S01-P03（2B-1 DDL 与骨架）前置 = 本包对象合同 FROZEN；enum 已确定可进入 DDL 设计，但转移矩阵未 FROZEN 前，S01-P03 仍须以 fail-closed guard 骨架落地，不实现结算/退款/撮合业务。
