# REVIEW_REQUEST — S01-P02 · 2B-1 状态合同补齐

## 审核头部

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-1 小批状态合同）
PACKAGE_ID = S01-P02-2B1-STATE-CONTRACT
IMPLEMENTATION_COMMIT = c2d57ce1eec2c30ff076d04aac6256f1ad4b18e0
BASE_COMMIT = 4bcf80fee4cb9603688465261a0ab5091eab7e07
REVIEW_RANGE = 4bcf80f..c2d57ce
PACKAGE_SHA256 = eba2536266f9950605ba4aa599cd3ecd286e17a5998ebd215c3781623cf2a2df
PREVIOUS_PACKAGE = S01-P01-MC2-REVIEW-LOCK（Round 7 APPROVED，MC2 已 FROZEN）
```

## 范围（Scope）

本包审核 **2B-1 状态合同补齐**的 3 个新增任务文档（`TASK-20260816-001/`）：

```text
A .project-ai/tasks/TASK-20260816-001/requirement.md
A .project-ai/tasks/TASK-20260816-001/design.md
A .project-ai/tasks/TASK-20260816-001/acceptance.md
```

> 进度账本（bootstrap/context/manifest/07 的 S01-P02 状态更新）属 Developer 进度记录，不在本包合同审核范围；manifest.yaml 的 `machine_contract_batch2b1_state_contract` decisionSource 仅为对上述 3 文件的指针摘要。

## 非目标（NON_GOALS）

- 不生成 2B-1 DDL（属 S01-P03）。
- 不写 PHP Model/DAO/Service（属 S01-P03）。
- 不自创 canonical state、不自创角色、不自创 API。
- 不修改 05 契约（6 缺 enum 实体的 enum 补充需 Owner 裁决后走 05 变更流程）。
- 不涉及 2B-2 对象（属 S01-P04）。

## 审核对象（固定 9 对象）

1. **Result**：复制 05 §4 enum `provisional/official/disputed/corrected` + 转移矩阵 RS1-RS5（候选）。
2. **Settlement**：复制 05 §4 enum `queued/calculating/review/payable/paid/failed` + 转移矩阵 ST1-ST7（候选）。
3. **SettlementBatch / RefundCase / CorrectionCase / OtcTrade / RobotUpgradeOrder / ConsentReceipt**（6 缺 enum 实体）：仅生成 Owner Decision Matrix（2B1-ENUM-01..06）+ 候选状态合同摘要（design.md D.7，非冻结），未自创状态。
4. **AuditEvent**：复用 MC2 `audit_events` DDL，不重复创建。

## 关键不变量（必须核对）

```text
Result enum == 05 §4 canonical（provisional/official/disputed/corrected），未新增状态值
Settlement enum == 05 §4 canonical（queued/calculating/review/payable/paid/failed），未新增状态值
Result official != Settlement paid
Result confirmer != Settlement approver（05 §8 SoD）
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_ROLE = YES（触发者/Writer 仅用 05 §8 已有角色）
AUDIT_EVENTS_REUSED_NOT_RECREATED = YES
OWNER_DECISION_MATRIX_COUNT = 6
CANDIDATE_STATE_SUMMARY_NOT_FROZEN = YES（design.md D.7 标注「候选/非冻结」）
UNFROZEN_STATE_FAIL_CLOSED = YES
```

## 审核绑定（REVIEW_BINDING）

```text
IMPLEMENTATION_COMMIT = c2d57ce1eec2c30ff076d04aac6256f1ad4b18e0
PACKAGE_SHA256 = eba2536266f9950605ba4aa599cd3ecd286e17a5998ebd215c3781623cf2a2df
DIFF_UNTUNCATED = YES（DIFF.txt = 25522 字符）
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

- Result/Settlement 的 enum 已 05 冻结，但转移矩阵为**候选**，需 State Machine gate 审核通过后才视为该两对象合同 FROZEN。
- 6 缺 enum 实体在 Owner 裁决 enum 并补 05 §4 前保持 `CONTRACT_GAP / FAIL_CLOSED`，本审核**不**将其置 FROZEN。
