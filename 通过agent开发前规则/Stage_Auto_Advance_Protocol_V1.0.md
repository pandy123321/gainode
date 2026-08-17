# Stage Auto-Advance Protocol — 阶段审核通过自动推进规则

**状态：MANDATORY**

---

# 1. 核心原则

项目一旦已经完成：

```text
Development Freeze
Stage Plan Freeze
Scope Freeze
External Review Protocol Freeze
```

则执行 Agent 在已批准 Stage Plan 内不应反复等待人工确认。

标准模型：

```text
Stage Implementation
        ↓
Self Check
        ↓
Evidence Package
        ↓
Independent Review
        ↓
Executor Adjudication
        ↓
Final Review Acceptance
        ↓
Stage Gate Passed
        ↓
AUTO ADVANCE
        ↓
Next Approved Stage
```

因此：

> **人工负责批准计划和边界，Agent 负责在批准边界内部自动完成闭环。**

---

# 2. 自动推进不是跳过审核

禁止错误理解：

```text
AUTO_ADVANCE
=
Skip Review
```

正确含义：

```text
AUTO_ADVANCE
=
Review Passed
+
Adjudication Completed
+
Gate Passed
+
Next Stage Already Approved
```

之后不再额外询问：

```text
“审核已经通过，可以开始下一阶段吗？”
```

---

# 3. 自动推进最低条件

只有以下条件全部成立：

```text
IMPLEMENTATION_COMPLETE = YES
STATIC_SELF_CHECK = PASS
REQUIRED_VALIDATIONS = PASS_OR_FORMALLY_DEFERRED
PACKAGE_MANIFEST = VALID
PACKAGE_HASH_MATCH = YES
REVIEW_BINDING = VALID
EXTERNAL_REVIEW_VERDICT = APPROVED / APPROVED_FOR_NEXT_STAGE
REVIEW_COMPLETENESS = COMPLETE
EXTERNAL_REVIEW_ADJUDICATION = ACCEPTED
ALL_FINDINGS_ASSESSED = YES
UNVERIFIABLE_BLOCKING_FINDING = 0
DISPUTED_BLOCKING_FINDING = 0
P0_OPEN = 0
P1_OPEN = 0
P2 = CLOSED_OR_DISPOSITIONED
OUT_OF_SCOPE_TASK = 0
ACCEPTANCE_CRITERIA = PASS
NEXT_STAGE_DEFINED = YES
NEXT_STAGE_WITHIN_FROZEN_PLAN = YES
```

才能：

```text
AUTO_ADVANCE_DECISION = APPROVED
STAGE_GATE = PASSED
NEXT_STAGE_AUTHORIZATION = YES
HUMAN_CONFIRMATION = NOT_REQUIRED
```

---

# 4. 自动推进必须执行的动作

当 Stage Gate 满足后，执行 Agent 必须自动：

```text
1. 标记当前 Stage 完成；
2. 保存 Implementation Commit、Package SHA-256、Review ID、Review Verdict、Adjudication、Finding 状态、Validation 结果；
3. 更新 STAGE_GATE_STATUS；
4. 将当前阶段信息归档；
5. 读取冻结 Stage Plan；
6. 确认下一 Stage ID；
7. 自动继承上一 Stage 上下文；
8. 自动切换 CURRENT_STAGE；
9. 自动执行下一 Stage 范围内任务；
10. 下一 Stage 完成后重新执行完整 Review Gate。
```

---

# 5. 下一阶段上下文自动继承

自动进入下一阶段时必须带入：

```text
PREVIOUS_STAGE
PREVIOUS_IMPLEMENTATION_COMMIT
PREVIOUS_PACKAGE_SHA256
PREVIOUS_REVIEW_ID
PREVIOUS_REVIEW_VERDICT
PREVIOUS_ADJUDICATION_STATUS
CLOSED_FINDINGS
OPEN_FINDINGS
ACCEPTED_DEVIATIONS
DEFERRED_VALIDATIONS
CURRENT_BASELINE
CURRENT_FREEZE_VERSION
```

禁止新 Stage Agent 重新猜历史。

---

# 6. 哪些情况必须停止自动推进

任一条件成立：

```text
Review package hash mismatch
Commit mismatch
Review scope mismatch
Review completeness incomplete
Adjudication disputed
Finding unverifiable
P0 open
P1 open
Blocking P2 unresolved
Acceptance criteria failed
Required validation failed
New out-of-scope task discovered
Frozen architecture must change
API must change
Database schema must change
State/Event must change
Permission model must change
Business logic must change
New dependency not approved
Closed Finding must be reopened
Next Stage undefined
Next Stage outside frozen plan
```

则：

```text
AUTO_ADVANCE_DECISION = PAUSED
NEXT_STAGE_AUTHORIZATION = NO
HUMAN_CONFIRMATION = REQUIRED
```

---

# 7. 人工确认必须是“例外”，不是正常流程

项目一旦进入已冻结开发计划，正常范围内实施、Build、Test、Evidence、Review、正确 Finding 修复、重新提审、审核通过、进入下一 Stage，都不得不断询问 Owner。

人工只处理：

```text
Scope Change
Architecture Change
Risk Acceptance
Business Decision
New Dependency
New External System
Freeze Change
High-risk Runtime Operation
Deployment
Production
Mainnet
Signer
Unresolved Review Dispute
```

---

# 8. 外部审核结果不是唯一自动推进条件

即使：

```text
EXTERNAL_REVIEW_VERDICT = APPROVED
```

如果：

```text
EXTERNAL_REVIEW_ADJUDICATION != ACCEPTED
```

则不得推进。

例如 Reviewer 返回 APPROVED，但执行 Agent 复核后发现 Reviewer 审错 Commit，则：

```text
REVIEW_BINDING = INVALID
AUTO_ADVANCE = NO
```

---

# 9. CHANGES_REQUIRED 的自动修复闭环

若 Reviewer 返回：

```text
CHANGES_REQUIRED
```

执行 Agent 完成 Adjudication。

对于：

```text
CORRECT_ACTIONABLE
```

如果修复仍属于当前 Stage 范围：

```text
HUMAN_CONFIRMATION = NOT_REQUIRED
```

直接执行：

```text
Fix
↓
Self Check
↓
New Package
↓
Independent Re-review
```

直到 APPROVED，无需 Owner 每轮批准修复。

---

# 10. 错误 Finding 时禁止自动推进

如果：

```text
Reviewer Finding = P1
Executor Adjudication = INCORRECT_DO_NOT_EXECUTE
```

执行 Agent 不能 Ignore Finding 后 Auto Advance。

必须：

```text
Submit Counter-Evidence
↓
Independent Re-review
↓
Finding = REJECTED_WITH_EVIDENCE
↓
Final Verdict
↓
Auto Advance
```

---

# 11. PARTIAL Finding 的推进规则

若：

```text
PARTIALLY_CORRECT_LIMITED_ACTION
```

必须先执行 Valid Part、记录 Invalid Part、重新提审。只有独立审核最终确认阻断部分已关闭，才能推进。

---

# 12. UNVERIFIABLE 一律停止

如果：

```text
UNVERIFIABLE_PAUSE
```

则：

```text
AUTO_ADVANCE = FORBIDDEN
```

无论 Finding 严重级别。必须先补齐 Missing File、Runtime Evidence、Decision、Config、Logs、Commit、Package 等必要证据。

---

# 13. NEXT_STAGE_DEFINED 强制要求

自动推进之前必须明确：

```text
CURRENT_STAGE = Gx
NEXT_STAGE = Gy
```

且 Gy 必须已经存在于冻结计划中。

禁止 Agent 自行创造 G3.5、临时插入新阶段或修改阶段顺序。

---

# 14. 下一 Stage Scope 必须预冻结

自动进入下一阶段前必须读取：

```text
NEXT_STAGE_GOAL
ALLOWED_PATHS
FORBIDDEN_PATHS
INPUTS
NON_GOALS
EXIT_CRITERIA
REVIEW_REQUIREMENTS
```

缺任何影响执行边界的关键定义：

```text
AUTO_ADVANCE_DECISION = PAUSED
```

---

# 15. 自动推进不授权跨 Stage 修改

虽然下一阶段可以自动开始，但 CURRENT_STAGE 切换后，只允许修改当前 Stage Scope。不得因为自动推进机制而一次执行多个 Stage。每个 Stage 仍是独立 Review Unit。

---

# 16. 单次执行循环规则

逻辑上始终保持：

```text
One Stage
→ One Review Gate
→ One Advancement Decision
```

不能把多个 Stage 打成一个 Review。

---

# 17. 高风险动作永远不属于 Auto Advance

即使属于 Stage 计划，下列动作也不能仅因为 Stage Review 通过而自动执行：

```text
Production Migration
Production Deployment
Mainnet
Private Key Access
Signer Activation
On-chain Broadcast
Contract Redeployment
Production Data Mutation
Frozen Security Model Change
```

这些必须拥有单独：

```text
HIGH_RISK_AUTHORIZATION = YES
```

---

# 18. Auto Advance 与 Deployment 分离

必须永远成立：

```text
NEXT_STAGE_AUTHORIZATION != DEPLOYMENT_APPROVAL
```

例如 G8 Review = APPROVED 可以允许进入 G9，但不表示允许部署生产。

---

# 19. Auto Advance 与 Freeze Change 分离

如果下一 Stage 发现必须修改冻结设计：

```text
AUTO ADVANCE STOP
↓
CHANGE_REQUEST
↓
DESIGN_REVIEW
↓
OWNER_SIGNOFF
↓
UPDATED_FREEZE
↓
RESUME
```

---

# 20. Stage Gate 状态新增字段

`STAGE_GATE_STATUS` 建议增加：

```text
AUTO_ADVANCE_DECISION
AUTO_ADVANCE_REASON
PREVIOUS_STAGE
NEXT_STAGE
NEXT_STAGE_DEFINED
NEXT_STAGE_WITHIN_FROZEN_PLAN
NEXT_STAGE_AUTHORIZATION
HUMAN_CONFIRMATION_REQUIRED
EXTERNAL_REVIEW_ADJUDICATION
REVIEW_COMPLETENESS
```

---

# 21. 标准自动推进输出

```text
CURRENT_STAGE = G3
IMPLEMENTATION_COMPLETE = YES
SELF_CHECK = PASS
EXTERNAL_REVIEW_VERDICT = APPROVED
REVIEW_COMPLETENESS = COMPLETE
EXTERNAL_REVIEW_ADJUDICATION = ACCEPTED
P0_OPEN = 0
P1_OPEN = 0
P2_BLOCKING = 0
STAGE_GATE = PASSED
NEXT_STAGE = G4
NEXT_STAGE_DEFINED = YES
NEXT_STAGE_WITHIN_FROZEN_PLAN = YES
AUTO_ADVANCE_DECISION = APPROVED
NEXT_STAGE_AUTHORIZATION = YES
HUMAN_CONFIRMATION = NOT_REQUIRED
```

然后直接进入 G4。

---

# 22. 标准暂停输出

```text
CURRENT_STAGE = G3
STAGE_GATE = OPEN
AUTO_ADVANCE_DECISION = PAUSED
AUTO_ADVANCE_REASON = OUT_OF_SCOPE_DATABASE_CHANGE_REQUIRED
NEXT_STAGE_AUTHORIZATION = NO
HUMAN_CONFIRMATION = REQUIRED
REQUIRED_DECISION = DATABASE_FREEZE_CHANGE
```

---

# 23. 审核 Agent 的自动推进责任

独立审核 Agent 不负责启动下一 Stage，但必须明确输出：

```text
NEXT_STAGE_RECOMMENDATION = AUTHORIZED / NOT_AUTHORIZED
```

依据 Findings、Acceptance、Regression、Scope、Evidence。

---

# 24. 执行 Agent 的自动推进责任

执行 Agent 负责：

```text
读取 Reviewer Verdict
↓
完成 Adjudication
↓
验证 Stage Gate
↓
验证 Next Stage Scope
↓
自动推进
```

Reviewer approves 与 Executor advances 仍是两个角色。

---

# 25. Owner 的角色

Owner 无需批准每次正常 Stage 切换。

Owner 主要处理：

```text
Initial Freeze
Scope Changes
Risk Acceptance
Architecture Decisions
High-risk Operations
Production Deployment
Final Release
```

---

# 26. 自动开发主循环

```text
while NEXT_STAGE exists:

    load freeze
    load stage
    determine scope

    if out_of_scope:
        pause_for_human

    implement
    validate
    build evidence package
    submit independent review
    wait verdict
    adjudicate findings

    if correct_findings:
        fix_and_resubmit

    if disputed:
        submit_counter_evidence
        wait_re_review

    if blocked:
        stop

    if gate_passed:
        advance_to_next_stage
```

---

# 27. 防止“假自动化”

以下不算自动 Stage 系统：

```text
每做完一个任务
→ 问用户“下一步？”
```

真正的自动化是：

```text
范围已批准
+
下一阶段已定义
+
Gate 已通过
→ 自动继续
```

人工应该只处理异常和授权边界。

---

# 28. 防止“失控自动化”

自动推进不表示 Agent has unlimited authority。

必须始终受：

```text
Freeze
Stage Scope
Review
Adjudication
Runtime Gate
High-risk Gate
```

约束。

---

# 29. 通用基线新增核心原则

```text
31. 已批准 Stage 内正常开发不得反复请求人工确认
32. Stage Gate 通过后默认自动进入已定义下一 Stage
33. 自动推进必须发生在独立审核和二次判定之后
34. 自动推进不能跳过任何 Stage Review
35. 自动推进不能跨越冻结 Scope
36. 下一 Stage 未定义时不得自行创造 Stage
37. 审核通过不等于部署授权
38. 正确 Finding 自动修复，错误 Finding 自动反证，不得盲从
39. 只有异常、越界和高风险行为才需要人工介入
40. 自动化目标是减少重复确认，而不是扩大 Agent 权力
```

---

# 30. Stage Gate 完整统一条件

旧的简化条件：

```text
EXTERNAL_REVIEW_VERDICT = APPROVED
P0_OPEN = 0
P1_OPEN = 0
```

不得再单独作为进入下一阶段的充分条件。

以后统一使用：

```text
FINAL_REVIEW_VERDICT = APPROVED
AND REVIEW_COMPLETENESS = COMPLETE
AND EXTERNAL_REVIEW_ADJUDICATION = ACCEPTED
AND REVIEW_BINDING = VALID
AND P0_OPEN = 0
AND P1_OPEN = 0
AND BLOCKING_P2 = 0
AND ACCEPTANCE_CRITERIA = PASS
AND NEXT_STAGE_DEFINED = YES
AND NEXT_STAGE_WITHIN_FROZEN_PLAN = YES
```

满足后：

```text
STAGE_GATE = PASSED
AUTO_ADVANCE_DECISION = APPROVED
NEXT_STAGE_AUTHORIZATION = YES
HUMAN_CONFIRMATION = NOT_REQUIRED
```

---

# 31. 与 External Review Finding Adjudication 的关系

自动推进必须建立在完整的审核结论二次判定之后。

执行 Agent 收到独立审核结果后必须逐条标记：

```text
CORRECT_ACTIONABLE
INCORRECT_DO_NOT_EXECUTE
PARTIALLY_CORRECT_LIMITED_ACTION
UNVERIFIABLE_PAUSE
OUT_OF_SCOPE_HUMAN_CONFIRMATION_REQUIRED
```

其中 CORRECT_ACTIONABLE 在当前 Stage Scope 内可以自动修复并重新提审；INCORRECT_DO_NOT_EXECUTE 必须提供反证并重新提交独立审核确认，不得自行关闭 Finding；PARTIALLY_CORRECT_LIMITED_ACTION 仅执行证据支持部分；UNVERIFIABLE_PAUSE 与 OUT_OF_SCOPE_HUMAN_CONFIRMATION_REQUIRED 都禁止自动推进。

---

# 32. 与 Review Completeness 的关系

独立审核报告必须达到：

```text
REVIEW_COMPLETENESS = COMPLETE
```

每条确认成立的 Finding 至少必须包含：

```text
FINDING_ID
SEVERITY
FILE_PATH
LINE_RANGE_OR_FUNCTION
CURRENT_BEHAVIOR
EXPECTED_BEHAVIOR
EVIDENCE
ROOT_CAUSE
IMPACT
REMEDIATION_REQUIRED
REMEDIATION_SCOPE
REMEDIATION_STEPS
CONSTRAINTS_AND_NON_GOALS
ACCEPTANCE_CRITERIA
REGRESSION_CHECKS
```

若正式 Finding 缺少足以让执行 Agent 实施的修复方案：

```text
REVIEW_COMPLETENESS = INCOMPLETE
AUTO_ADVANCE_DECISION = PAUSED
NEXT_STAGE_AUTHORIZATION = NO
```

---

# 33. 最终治理不变量

必须长期保持：

```text
Reviewer Cannot Force Incorrect Changes.
Executor Cannot Ignore Valid Findings.
Executor Cannot Self-Approve Disputes.
Reviewer Must Re-evaluate Counter-Evidence.
Evidence Decides.
Stage Review Cannot Be Skipped.
Passed Stage Automatically Advances Only Within Frozen Scope.
Deployment Approval Is Always Separate.
```

中文：

> 审核者不能强迫执行者修改正确代码；执行者不能忽略正确 Finding；执行者不能自行裁决审核争议；审核者必须重新审核反证；最终由证据决定；每个 Stage 都必须经过审核；审核通过后的自动推进只能发生在冻结范围内；部署授权永远与阶段开发授权分离。
