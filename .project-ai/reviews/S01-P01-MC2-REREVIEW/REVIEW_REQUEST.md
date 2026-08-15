# REVIEW_REQUEST — MC2 State Transition Freeze 复审（IR 686 修复后重提）

## 审核头部

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 State Transition Freeze）
PACKAGE_ID = S01-P01-MC2-REREVIEW
IMPLEMENTATION_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
MC2_BASE_COMMIT = 7e6f828a9566b7382dae6aa7c918a63d0747b79a
REVIEW_RANGE = 7e6f828..2795e38
PACKAGE_SHA256 = 7789e3933113e7c29e89d85e608885b99cf8704b667127540ef54ec0b88b25a2
PREVIOUS_REVIEW = IR 686（六审）= CHANGES_REQUIRED（P0=0 / P1=1 / P2=3）
```

## 范围（Scope）

本次复审只针对 **IR 686 的 1 P1 + 3 P2 修复**，即 review range `7e6f828..2795e38`（单 commit `2795e38`）。

5 个变更文件：

```text
M .project-ai/manifest.yaml
M .project-ai/tasks/TASK-20260815-001/acceptance.md
M .project-ai/tasks/TASK-20260815-001/design.md
M .project-ai/tasks/TASK-20260815-001/requirement.md
M 0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md
```

## 非目标（NON_GOALS）

- 不重审已通过 IR 679 / IR 682 的既有合同内容（除非发现 CONCRETE_REGRESSION_EVIDENCE）。
- 不涉及非核心实体 DDL（2B-1/2B-2）、OpenAPI 3.1、Environment Freeze。
- 不修改任何产品业务代码。
- 本复审不包含 V3 策划基线提交 `fd7968b`（该提交为纯文档策划，已隔离）。

## 复核对象（IR 686 四项）

1. **P1-1**：`apt_accounts.object_version` CAS 从「dispute hold 锁」升级为 **统一 Economic Mutation Lock**（`APT_ACCOUNT_ECONOMIC_MUTATION_LOCK`），覆盖所有改 `balance_apt_i/balance_apt_c/frozen_apt_i/frozen_apt_c/aggregate_dispute_hold` 的操作，11 步同事务原子。
2. **P2-1**：`PRE_L5` → 通用 **`PRE_HOLD_MUTATION_GUARD`**，显式适用 L4（pending DEBIT→disputed）+ L5（posted CREDIT→disputed）。
3. **P2-2**：并发错误码统一 **`OBJECT_VERSION_CONFLICT`(HTTP 409)**，`ACCOUNT_LOCK_CONFLICT` 仅 `INTERNAL_ONLY`。
4. **P2-3**：Review 证据完整性 → **`REVIEW_PACKAGE_TRUNCATED = NO`**（本轮已生成完整未截断 diff，见 `DIFF.txt`，41930 字符）。

## 审核绑定（REVIEW_BINDING）

```text
IMPLEMENTATION_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
PACKAGE_SHA256 = 7789e3933113e7c29e89d85e608885b99cf8704b667127540ef54ec0b88b25a2
DIFF_UNTUNCATED = YES
SECRET_SCAN = PASS (0 hits)
```

## 请求结论

请按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具完整审核，最终给出：

```text
VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
REVIEW_COMPLETENESS =
NEXT_STAGE_RECOMMENDATION = AUTHORIZED / NOT_AUTHORIZED
```

只有 `APPROVED` 才允许把 MC2 置 **FROZEN**。
