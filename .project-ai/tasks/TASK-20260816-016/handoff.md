# S02-P09 · 后端 Gate — Developer 交接单（Handoff to Quality）

> 来源角色：Developer（`Code-Origin: Developer`）
> 目标角色：独立 Quality Agent（新会话 / 独立 worktree）
> 本文件是 STAGE-02 开发线收口后的交接正文，只登记事实与指针，不含任何 Quality Gate 结论。
> 权威 Gate 规格以 `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P09 为准。

## 0. 交接触发原因（职责分离）

STAGE-02 开发线（S02-P01..P08）已由 Developer 全部实现并本地提交。
下一包 **S02-P09 · 后端 Gate** 属 Quality 角色（07 §S02-P09 步骤 7：`Quality 独立给 STAGE-02 Gate`）。
V3.4 硬约束（`.project-ai/rules/roles.md` §1）：参与实现的 Agent 不得代替独立审核 Agent 给出最终外审结论。
故由独立 Quality Agent 接手，本单为交接输入。

## 1. 当前状态：STAGE-02 开发线完成

分支 `feature/gainode-v3-serial-development`，最近提交：

```text
4e68838  feat(aiops): S02-P08 内部 AI 经济引擎计算管线 + fail-closed   （本包刚提交）
2869b49  chore(governance): 冻结 V3.4 执行计划（整合五角色纪律）
3d23ec5  chore(context): 发布项目上下文 v31（S02-P07 完成）
bc7daf4  feat(governance): S02-P07 治理六域状态机 + SoD + fail-closed + 只读投影
273513a  feat(otc): S02-P06 OTC/Power 状态机骨架 + 只读投影 + fail-closed
```

S02-P01..P06 已各有独立审核提交；**S02-P07、S02-P08 尚未内审，S02-P09 Gate 尚未执行**。

## 2. 待办：Quality 需执行的三件事

### 2.1 内审 S02-P07

```text
PACKAGE_ID     = S02-P07
TASK_DIR       = .project-ai/tasks/TASK-20260816-014
DEVELOPER_SHA  = bc7daf4a8eb5660fc7a596c64f489b493baed29e
BASE_SHA       = 4222d45934469b0e7aa35baed03d030b3795afa6
REVIEW_RANGE   = 4222d45..bc7daf4
CHANGED        = 21 files / +2665 / -105
范围            = ApprovalRequest / ParameterRelease / ParameterSnapshot / RiskCase / Ticket /
                 TicketMessage / TicketAttachment / Notice / NotificationDelivery / AuditEvent
                 六域状态机 + SoD（approver≠submitter、operator≠approver、approver≠detector）+
                 fail-closed（deliver/create/execute）+ 只读投影 + append-only
测试            = php tests/Contract/S02P07PolicyContractTest.php（34 断言，Developer 复跑 PASS）
                 php tests/Integration/S02P07PolicyStateMachineTest.php（61 断言，PASS）
已知合同缺口    = PR3/PR4（changes_requested 不在 canonical 8 态）→ 登记 NEEDS_OWNER_DECISION
```

### 2.2 内审 S02-P08

```text
PACKAGE_ID     = S02-P08
TASK_DIR       = .project-ai/tasks/TASK-20260816-015
DEVELOPER_SHA  = 4e6883808ee3774712a41eaad25d163eedda0335
BASE_SHA       = 2869b49f1d3f2987e0d1e3c363537c646387a37b
REVIEW_RANGE   = 2869b49..4e68838
CHANGED        = 11 files / +1418 / -0
范围            = 内部 AI 经济引擎纯计算管线：ConfirmedProfitAdapter（去重）/
                 ReferenceProfitService（<=0 短路 / smoothing fail-closed）/
                 AptBudgetMappingService（price/multiplier fail-closed + bcmath）/
                 DailyAIBudgetService（五 cap 取最小）/ AiBudgetParameterReader /
                 BudgetDecision / AiBudgetEngine（编排 + 持久化/Outbox/Audit fail-closed）
测试            = php tests/Contract/S02P08AiOpsContractTest.php（56 断言，PASS）
                 php tests/Integration/S02P08AiOpsEngineTest.php（24 断言，PASS）
已知合同缺口    = S02-P08-BUDGET-PERSISTENCE（预算持久对象未冻结，persist fail-closed）
                 S02-P08-PARAMS（smoothing/price/multiplier/cap 全 TBC → fail-closed）
```

### 2.3 执行 S02-P09 · 后端 Gate

以 07 §S02-P09 固定步骤 1–7 为准，目标文件：

```text
.project-ai/reviews/STAGE-02-BACKEND-COVERAGE.md
OpenAPI lint evidence
test/coverage/invariant evidence
STAGE-02-QUALITY-GATE.md
```

步骤摘要（详见 07 §S02-P09）：
1. 05 §6 每个 P0 API → OpenAPI operation / route / controller / validator / service / test 映射，缺一返责任包。
2. 所有状态转移 → writer / guard / idempotency / audit / outbox / test，检查非法出边与终态保护。
3. OpenAPI parse/ref/operationId/required/auth/idempotency/closed-schema + 全部 PHP syntax / unit / integration / feature / contract。
4. RBAC/ABAC/SoD/field masking、Secret、依赖、SQL、append-only/immutable 审查。
5. Ledger/APT/Power/Reward/OTC/Prediction 守恒、reversal、Outbox replay、process restart + ≥15 场景。
6. 核对 S02-P01..P08 独立 Snapshot/Quality/Finding closure，汇总未运行项与 Owner 条件。
7. Quality 独立给 STAGE-02 Gate；不通过则缺陷回责任包最小修复。

验收矩阵（07 §S02-P09）：P0 API 覆盖=100%；非法状态出边=0；直接账本经济列更新=0；SoD bypass=0；
核心测试通过；独立 Gate APPROVED 后才进入 STAGE-03；Production 仍 NO-GO。

## 3. Quality 执行约束（V3.4）

```text
- 独立 worktree（固定 SHA，物理隔离，见 rules/git-review-worktree.md）：
  STAGE-02 全链在 feature/gainode-v3-serial-development（领先 master 62 提交，merge-base=fd7968b）。
  审核基线 = 该分支 HEAD（等价于 base=master + cherry-pick master..HEAD 全链，直接以 feature 分支
  HEAD 建基线更简单、不会漏 commit）：
      git worktree add -b gainode/review/S02-P09 <dir> feature/gainode-v3-serial-development
- 只读 Developer worktree；独立复跑测试，不把 Developer 自检当 Quality Verdict
- 修复提交用 Code-Origin: Quality + Git-Operator: Quality + Review-Findings: <ids>
- push / 提审 / merge 需 Owner 明确授权；本单不含任何授权
- 后端运行目录：0.5代码/gainode后端/gainode
```

## 4. 未决 Owner 事项（交接时不阻塞，由 Quality 在 Gate 汇总上报）

```text
S02-P07: PR3/PR4 changes_requested 合同缺口（NEEDS_OWNER_DECISION）
S02-P08: BUDGET-PERSISTENCE + PARAMS 未冻结（NEEDS_OWNER_DECISION）
S01-P07: 11 项已签（2026-08-16 全 OPTION_A）；S01-P08: 9 项已签 + D10 LOCKED（状态机待 IR 冻结）
```

## 5. Developer 最终自检记录

```text
S02-P08 PHP lint      = PASS（7 新文件）
S02-P08 Contract      = PASS（56 断言）
S02-P08 Integration   = PASS（24 断言）
S02-P08 OpenAPI 解析  = PASS（aiops.yaml + gainode-v2.yaml）
trailer               = Code-Origin: Developer + Git-Operator: Developer ✅
FINAL_SELF_CHECK      = 范围无越界；未建 DDL；未触碰 apt_accounts；fail-closed 边界完整
```
