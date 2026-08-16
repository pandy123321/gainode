# Gainode 执行细节与阶段闸门（Workflow）

> 本文件源自创世DOG `tools/git-review-worktree/WORKFLOW.md` 通用框架，已适配 Gainode。
> 角色定义见 [`roles.md`](./roles.md)。所有状态词、提交格式、问题分级、Verdict 为项目固定约定。
> 与 07 V3.4 一致：Developer 一开到底，门禁统一由 Quality 在「审核—提审—合并」环节把关。

---

## 1. 双流水线模型

开发线与质量线是两条流水线，不得混成一个提交，也不得让外审进度无条件阻塞普通开发。

```text
开发线：
OWNER_APPROVED_ROADMAP
→ IMPLEMENTING_STAGE_N
→ LOCAL_VALIDATED_STAGE_N
→ DEVELOPER_COMMITTED_STAGE_N
→ IMPLEMENTING_STAGE_N+1

质量线：
DEVELOPER_COMMITTED_STAGE_N
→ QUALITY_REVIEW_STAGE_N
→ FIXED_REVIEW_HEAD_STAGE_N
→ EXTERNAL_REVIEW_STAGE_N
→ OWNER_APPROVED_STAGE_EXIT_N
→ MERGED / STAGE_ACCEPTED_N
→ QUALITY_REVIEW_STAGE_N+1
```

> Gainode 对应：`STAGE_N` = 07 的工作包 ID（如 `S02-P03`、`S02-P04`）。
> 开发线 = Developer 串行推进所有已定义 Package；质量线 = Quality 逐 Package 收口。

---

## 2. 路线批准即授权连续本地 commit

Owner 批准一段路线时，即同时授权 Developer 在 `<developer-branch>` 上为每个 Stage
创建独立本地来源 commit，无需逐 Stage 停等授权。该授权**只覆盖本地 commit**，明确禁止：

```text
Forbidden: push / merge / rebase / cherry-pick / tag / release / deploy
Forbidden: 把多个 Stage 混入一个 commit
```

push、提审、merge、发布始终需要各自 Owner 明确授权。此设计让 Developer 一路开发到底，
不扩大任何 Git 权限。

---

## 3. 状态词（只能按证据使用）

| 状态 | 必须具备的证据 |
| --- | --- |
| `IMPLEMENTING` | Owner 已批准实现，Developer 正在单 Stage 范围内工作 |
| `CODE_COMPLETE` | 实现完成，不代表测试、审核或提交 |
| `LOCAL_VALIDATED` | 指定自动/人工测试有实际退出码和记录 |
| `DEVELOPER_COMMITTED` | 已形成仅含当前 Stage 的本地来源提交，可进入 Quality 队列 |
| `QUALITY_REVIEWED` | Quality 已检查实际 Diff 并给出结论 |
| `FIXED_REVIEW_HEAD` | 已获权形成完整固定 SHA，可供 Reviewer 读取 |
| `EXTERNAL_REVIEW_APPROVED` | Reviewer 对当前完整 SHA 给 APPROVED/获 Owner 接受的有条件结论 |
| `STAGE_ACCEPTED` | Owner 已批准 Stage Exit，Quality 已完成合并后核对 |
| `RELEASE_READY` | 全部发布前闸门通过；不等于部署授权 |
| `DEPLOY_AUTHORIZED` | Owner 对明确环境、版本和操作单独授权 |

禁止把「页面能打开」「有 commit」「测试过一部分」写成 `STAGE_ACCEPTED`。

---

## 4. 提交来源与 trailer 格式

Developer 实现提交：

```text
feat(<scope>): <STAGE_ID> <summary>

Stage: <STAGE_ID>
Code-Origin: Developer
Git-Operator: Developer
```

Quality 修复提交：

```text
fix(review): <STAGE_ID> close <finding-ids>

Stage: <STAGE_ID>
Code-Origin: Quality
Git-Operator: Quality
Review-Findings: <P1-001,P2-002>
```

硬规则：

- Developer 与 Quality 的代码**禁止混入同一 commit**；
- 禁止 amend、squash、rebase 或重新复制文件来抹掉来源边界；
- 重新提审时审核范围覆盖当前 Stage 从基线到最新固定 SHA 的完整提交链，而非只审最后一个修复 commit。

---

## 5. 问题分级

| 级别 | 定义 | 默认处理 |
| --- | --- | --- |
| P0 | 安全绕过、密钥/资金风险、不可逆数据破坏、错误系统写入、核心协议失效 | 必须修复，立即阻断 |
| P1 | 主流程错误、权限/状态机/事务/并发缺陷、假成功、不兼容迁移 | 必须修复，阻断 Stage |
| P2 | 当前验收合同明确要求但未满足，或重要覆盖/一致性缺口 | 由验收合同决定是否阻断；不得静默忽略 |
| P3 | 不影响当前正确性的体验、命名、维护性或补充覆盖 | 可登记后续，不自动阻断 |

执行 Agent 收到审核意见后逐项标记：`ACCEPTED` / `REJECTED`（附证据） / `STALE` /
`NEEDS_OWNER_DECISION`。审核 Agent 的修复提示词不构成自动执行授权。

---

## 6. Verdict 集合

外部审核 Verdict 只允许：`APPROVED` / `APPROVED_WITH_CONDITIONS` / `CHANGES_REQUIRED` / `NO-GO`。

Quality 结论只允许：`QUALITY_PASS` / `QUALITY_CHANGES_REQUIRED` / `QUALITY_BLOCKED`。

每个问题必须包含：问题编号、P0/P1/P2/P3、文件、行号、触发方法、影响、违反的合同、
固定修复结果、必须运行的测试、修复归属。

> Gainode 特定：Finding 的完整字段结构以 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` §6 为准
> （含 EVIDENCE / ROOT_CAUSE / REACHABLE_SCENARIO / ACCEPTANCE_CRITERIA / REGRESSION_CHECKS 等）。

---

## 7. 修复归属规则

- 业务行为、页面行为、Field/Action/State、API、Database/Schema/migration、权限、
  owner/productLine、状态机、事务/审计、幂等/并发、recovery、签名、Batch、测试业务覆盖
  → 默认退回 **Developer**；
- Quality 只能处理**合同不变、范围明确、Owner 明确授权**的机械修复；
- Quality 修改必须在 Developer 提交之后，形成独立 Quality commit，不得 amend Developer；
- 同一轮 Developer 与 Quality 都有修改时，必须形成至少两个不同提交。

---

## 8. Quality 审核提审门禁清单

Developer 一路开发到底后，门禁统一由 Quality 在「审核—提审—合并」环节把关。
每次审核、提审、合并前逐项核对；任一不满足即阻断对应 Stage，但不要求 Developer
停止后续无依赖 Stage：

1. **基线一致性**：基线 SHA、分支、工作区与任务包一致；冻结计划清单 SHA256 校验通过；
2. **Stage 边界**：审核分支只含一个 Stage；一个 commit 不混入两个 Stage；无后续 Stage、未知文件、构建产物混入；
3. **来源归属**：Developer 提交 trailer 为 `Code-Origin: Developer`、`Git-Operator: Developer`；Quality 修复为 `Code-Origin: Quality`；二者不混入同一 commit；
4. **合同一致性**：Field/Action/State/API/DB 可追溯到冻结合同；未改变冻结的 Database/API/Event-State/Environment 合同；
5. **安全红线**：无私钥/助记词/凭证/生产密码泄露；无未授权资产/账本/部署操作；`.env` 与 vendor 未进入提交；
6. **测试真实性**：测试调用真实生产路径，无非真实 exit code；未运行项未写成 PASS；
7. **覆盖完整性**：正常/失败/边界/重复/并发/恢复路径已覆盖；
8. **待决项上报**：Developer 登记的 `NEEDS_OWNER_DECISION` 汇总上报 Owner 裁决；
9. **P0/P1 阻断**：共享 P0/P1 或合同失效时，阻断受影响 Stage 的提审与合并，并通知 Developer 影响范围；
10. **外审对象一致**：外审比较范围覆盖 base 到最新 head 的完整 Stage 链；外审 SHA 与待合并 SHA 完全一致；
11. **合并顺序**：按 Stage 顺序审核与合并；合并后验证完成。

以下仍为**硬停止**（任何角色不得绕过，需 Owner 单独授权）：

- push / merge / rebase / cherry-pick / tag / release / deploy；
- 资产 / 账本 / 真实资金 / 生产数据变更；
- 修改已完成只读基线（除非 Owner 建立基线例外 Stage）。

---

## 9. Reviewer 审核输入与输出

输入必须包含：

- `STAGE_ID`；
- 完整基线 SHA、完整最终 SHA；
- `base..head` 比较范围；
- Developer/Quality 提交归属表；
- 任务包、权威合同和验收标准；
- Quality 实际复跑的测试与未运行项；
- 明确的只读限制。

Reviewer 只输出四种 Verdict（见 §6）。审核意见不构成修复、合并或发布授权。

---

## 10. 独立 worktree 拓扑

| 用途 | 固定值 | 写入者 |
| --- | --- | --- |
| 合并目标 | `master` | Quality 获权后 |
| Developer 连续开发 | `feature/gainode-v3-serial-development` | 唯一 Developer |
| Quality 审核 | `gainode/review/<stage-id>` | 唯一 Quality |
| Reviewer | 指定审核分支的完整 SHA | 只读 Reviewer |

关键约束：

- Developer worktree 与 Quality worktree 必须是两个目录，禁止两个 Agent 写同一工作树；
- Developer 已产生的后续 Stage commit 不得进入当前审核分支；
- Quality 按 Stage 顺序提审和合并，可落后但不得打包或乱序；
- branch/worktree 创建、local commit、push、merge 都按各自授权执行。

具体建立独立审核 worktree 的命令见 [`git-review-worktree.md`](./git-review-worktree.md)。

---

## 11. Stage 交接记录固定格式

Developer 完成每个 Stage 后必须逐项填写：

```text
Stage: <ID>
Baseline SHA: <40位>
Developer final SHA: <40位>
Changed files: <逐个文件>
Contract slice: <Page/Field/Action/State 或 Freeze 输入版本与SHA>
Implementation: <按文件说明实际实现>
Automated verification: <命令 | 退出码 | PASS/FAIL/BLOCKED/NOT_RUN>
Manual verification: <步骤 | 预期 | 实际 | 证据>
FINAL_SELF_CHECK: <范围、合同、风险与证据检查发现列表>
FINAL_SELF_CHECK_FIXED: <逐项修复与复验>
Open issues: <严重度、影响、独立Stage建议；无则 NONE>
Out-of-scope untouched: <已完成代码、其他Stage、配置、依赖等>
Git actions: <实际执行；未授权时必须写 no add/commit/push/merge/rebase>
External actions: <资产/账本/部署；均应为 NONE，除非获权人工操作>
Next technically ready Stage: <ID 或 BLOCKED + 原因>
```

Quality 接手时使用该记录定位证据，但必须独立读取完整差异和复跑测试，
不能把 Developer 的最终自检当成 Quality Verdict。

> Gainode 现有对应物：Developer 快照（`.project-ai/reviews/<STAGE>-DEVELOPER-SNAPSHOT-*.md`）
> 与 Quality Review（`<STAGE>-QUALITY-REVIEW-*.md`）已承载部分字段，本格式为补充标准，
> 不要求废弃既有产物；新 Stage 应优先采用本格式作为交接正文。

---

## 12. 与独立审核 worktree 的衔接

1. Developer 在 `<developer-branch>` 形成 Stage 的独立 commit，把完整 SHA 放入 Quality 队列；
2. Quality 用 `git worktree add -b gainode/review/<stage-id> <worktree> master` 建立固定 SHA 的独立审核分支；
3. Quality 在该 worktree 内审、独立复跑测试、必要时追加 Quality commit；
4. 获 Owner 授权后 push/提审，Reviewer 只读审 `base..head`；
5. 外审通过 + Owner merge 授权后，Quality 只合并当前 Stage，运行 post-merge 验证；
6. 循环下一 Stage，Developer 全程不被阻塞。

---

## 信息来源

- 创世DOG `tools/git-review-worktree/WORKFLOW.md`（通用框架来源）
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（V3.4 §3/§4/§5）
- `通过agent开发前规则/INDEPENDENT_REVIEW_AGENT_PROTOCOL.md`（Finding 结构）
- `.project-ai/rules/review.md`（Gainode 审核清单）
