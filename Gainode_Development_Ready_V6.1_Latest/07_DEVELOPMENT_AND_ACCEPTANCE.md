# 07 · Gainode 开发执行计划、Agent 派发规则与功能验收

> 版本：V3.4 · Fully Detailed 40-Packages + Single Developer Serial + Independent Quality Review（Dev 一开到底，门禁移交 Quality；整合五角色纪律）
> 文档状态：`FROZEN_FOR_EXECUTION`
> 冻结 ID：`GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.4-20260816`
> 冻结日期：2026-08-16（Asia/Shanghai）
> 生效项目：Gainode 体育预测、竞猜与内部套利经济引擎
> 唯一工作区：`E:\github\sports`
> 后端基线：PHP 8.2 + Webman 2.1 + Workerman；不迁移 Go
> 目标：让执行 Agent 不需要自行设计项目路线，只需按本文件规定的工作包、文件范围、实现顺序、验证命令和停止条件执行

---

## 0. 项目身份锁与绝对禁止项

### 0.1 冻结执行基线

本文件 V3.4 是 Gainode 当前唯一、最新且已冻结的开发步骤基线。Development Agent、Quality Agent 和后续复审 Agent 必须按本文件定义的 Formal Stage、Package ID、包顺序、范围、停止条件、提审节奏和 Gate 条件执行。自 V3.3 起 Development Agent 一开到底，进度门禁由 Quality Agent 在审核时验证（见 §3.2 与 §5 门禁语义统一覆盖条款）；V3.4 在此基础上升级 §3 角色纪律（五角色、双流水线、提交来源 trailer、独立审核 worktree 与 11 项门禁清单）。

```text
LATEST_EXECUTION_PLAN = Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md
LATEST_EXECUTION_PLAN_VERSION = V3.4
EXECUTION_PLAN_STATUS = FROZEN_FOR_EXECUTION
EXECUTION_PLAN_FREEZE_ID = GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.4-20260816
DEVELOPMENT_AGENT_MUST_FOLLOW_FROZEN_PLAN = YES
QUALITY_AGENT_MUST_REVIEW_AGAINST_FROZEN_PLAN = YES
OLDER_EXECUTION_PLAN_STATUS = SUPERSEDED_DO_NOT_EXECUTE
PLAN_CHANGE_CONTROL = OWNER_APPROVAL_REQUIRED
DEV_GATE_MODEL = NO_PROGRESS_GATES_QUALITY_ENFORCES
```

冻结含义：

- 不得自行新增、删除、合并、拆分、跳过或重排本文件定义的工作包。
- 不得自行改变 Package 的输入、允许路径、锁定路径、非目标、验收条件或 Stage Gate。
- Quality Agent 必须逐 Package 锁定快照和审核；每个 Formal Stage 必须单独提交 Stage Gate 审核。
- Development Agent 串行推进所有已定义 Package，**一开到底**，不因任何进度门禁（合同未冻结、审核未返回、Owner 决策未决、Stage Gate 未关闭）而停止；这些门禁由 Quality Agent 在审核/提审时验证（见 §3.2 与 §15）。
- 修正文案、补充证据或关闭已确认 Finding，可以按当前 Package 的最小范围执行；改变业务、经济、状态、API、DDL、权限、依赖、正式参数或执行路线时，必须先生成 Change Request 并获得 Owner 明确批准。
- 任何批准后的计划修改必须升级版本、重新生成 Freeze ID、更新冻结凭证并同步 `.project-ai/bootstrap.md`、`.project-ai/context.md` 和 `.project-ai/manifest.yaml`；未完成这些步骤的新草案不得用于开发或审核。

每次执行前必须先验证：

```text
EXPECTED_WORKSPACE = E:\github\sports
EXPECTED_GIT_TOPLEVEL = E:/github/sports
EXPECTED_PROJECT = Gainode
EXPECTED_PRODUCT = AI 体育分析 + Football Pre-match 1X2 竞猜 + APT/Robot/Power/OTC
EXPECTED_BACKEND = PHP 8.2 + Webman 2.1 + Workerman
ARBITRAGE_DISPOSITION = 仅保留为内部 AI 经济引擎，不向 C 端暴露 BetBurger 信号、利润或仓位
```

任一不匹配时输出 `WORKSPACE_IDENTITY_MISMATCH` 并停止，禁止切换到或修改任何其他项目。

永久禁止：

- 不修改 `E:\github\AItradeos\一键交易转账\bnbchange` 或其他仓库。
- 不把后端改成 Go、Java、Node.js 或其他语言。
- 不把 Gainode 改写成链上项目；V2 的 APT 为中心化数量账，APT-C 仍为 Future/CLOSED。
- 不把旧 Figma、旧 Flutter、旧 Admin 代码或历史文档反推成当前业务规则。
- 不删除、覆盖或重做已经完成且已冻结的代码；只有绑定当前提交的有效 Finding 才能触发最小修复。
- 不执行生产 DDL、生产数据写入、生产部署、密钥访问、Signer、链上广播或真实价值开放。

---

## 1. 唯一权威来源与冲突处理

执行顺序固定如下：

1. `01_PRODUCT_FUNCTIONAL_BASELINE.md`：产品范围、P0/P1/Future。
2. `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`：APT、Robot、Reward、Power、OTC、Prediction 和四账分离。
3. `03_MOBILE_H5_HIFI_PROTOTYPE_SPEC_V2.4.md`：Mobile/H5 Page ID 和流程。
4. `04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md`：Admin Page ID、角色页面和流程。
5. `05_DATA_STATE_PERMISSION_API_CONTRACT.md`：对象、字段、状态、权限、API。
6. `06_PARAMETER_DICTIONARY.md`：参数结构和 TBC/null/closed 规则。
7. 本文件：开发顺序、工作包、验证、提审和验收。
8. `08_VISUAL_DESIGN_SYSTEM_V2.4.md`、`design-system/12_FIGMA_FRONTEND_DEVELOPMENT_BASELINE_V1.0.md` 和 Gainode2.0 Figma：视觉实现基线。
9. `.project-ai/**`：当前 Stage、冻结状态、任务、审核记录和代码治理。

冲突时不得自行选择。执行 Agent 必须输出：

```text
CONFLICT_STATUS = OPEN
CONFLICT_SOURCES =
HIGHER_AUTHORITY_SOURCE =
AFFECTED_OBJECTS =
AFFECTED_WORK_PACKAGE =
SAFE_WORK_THAT_CAN_CONTINUE =
OWNER_DECISION_REQUIRED =
```

涉及业务、经济、状态、API、数据库、权限、依赖、正式文案或 Stage 边界的冲突，必须生成 Change Request；不得把推测写进代码。

---

## 2. 截至 2026-08-16 的真实进度

### 2.1 已完成并冻结，禁止重做

| 成果 | 证据 | 状态 |
|---|---|---|
| STAGE-00 规划与文档基线 | `.project-ai/bootstrap.md`、Independent Review | COMPLETE |
| MC1：8 核心实体 DDL + canonical state | `sql/20260813_machine_contract_batch1_8_core_entities.sql`、MC1 Freeze | FROZEN |
| MC1 8 实体 Model/DAO/Service 骨架 | commit `5fb3d01`，24 个文件 | IMPLEMENTED |
| Ledger ORM append-only 防护 | Model + Builder + DAO | IMPLEMENTED |
| Ledger 回归入口 | `composer test` → 67 pass / 0 fail 的已记录证据 | PASS_AT_RECORDED_REVISION |
| MC2 Owner 裁决 | 22 项 + 2 项财务硬骨头 | COMPLETE |
| IR 686 修复 + MC2 最终复审 | 实现修订 `2795e38`；Round 7 = APPROVED（IR GAINODE-S01P01-MC2-IR-20260816-001） | FROZEN（0 P0 / 0 P1 / 0 blocking P2；1 非阻塞 P3） |
| S01-P02 2B-1 状态合同补齐 | Round 2 `GAINODE-S01P02-2B1-IR-20260816-002` | APPROVED（0 P0 / 0 P1 / 0 blocking P2） |
| S01-P03 2B-1 DDL 与骨架 | implementation `eedf313`；Quality Round 1 | APPROVED（8 表 + 25 类；0 P0 / 0 P1 / 0 blocking P2） |
| S01-P04 2B-2 合同补齐 | implementation `884cdf9` | IMPLEMENTED / REVIEW_PREPARATION（以最新提交与工作树复核为准） |

已完成的 8 个实体：

```text
apt_accounts
apt_ledger_entries
robots
robot_rewards
prediction_markets
prediction_orders
otc_orders
power_positions
```

执行 Agent不得重新创建这些表、Model、DAO 或 Service，不得为了“统一风格”重构它们。

### 2.2 当前未完成

- S01-P04 2B-2 合同补齐的独立审核与 Finding 收口。
- S01-P05 2B-2 DDL/骨架及其后续包。
- 未落表投影的服务端计算层。
- Affiliate/Agent、AI Operations 的正式机器合同与结构。
- OpenAPI 3.1、Environment Freeze、正式 API 路由和统一响应。
- 后端业务逻辑、状态机、幂等、并发、账本联动、Outbox。
- H5/Admin V2 正式开发目录与全量页面联调。
- Flutter App 工程。
- Sandbox E2E、迁移演练、发布就绪；生产仍为 NO-GO。

### 2.3 当前第一动作

```text
CURRENT_FORMAL_STAGE = STAGE-01_BACKEND_DOMAIN_OBJECTS
CURRENT_DEVELOPER_PACKAGE = S01-P04-2B2-STATE-CONTRACT
CURRENT_PACKAGE_STATUS = IMPLEMENTED_REVIEW_PREPARATION
CURRENT_KNOWN_IMPLEMENTATION_COMMIT = 884cdf978bec086e0f9cc5d1692481763bfbda59
S01_P01_REVIEW_STATUS = APPROVED_FROZEN
S01_P02_REVIEW_STATUS = APPROVED
S01_P03_REVIEW_STATUS = APPROVED
```

本节是冻结时进度快照，不替代实时 Git/Quality 证据。Agent 启动时必须读取最新 manifest、Quality Progress Ledger、task 和 Git；若进度已前移，只更新状态指针并从首个未关闭 Package 继续，禁止重做已 APPROVED/FROZEN/IMPLEMENTED 的成果。

---

## 3. 开发 Agent 与质量 Agent 的执行节奏

### 3.1 角色分离（五角色）

Gainode 执行层固定五个角色，职责分离是硬约束（完整定义与权限矩阵见 `.project-ai/rules/roles.md`）：

| 角色 | 定位 | 关键边界 |
|---|---|---|
| Owner（人工） | 唯一最终决策者 | 批准范围/提交/推送/合并/发布/资产 |
| Scheduler | 策划拆解派发 | 不写业务代码，不做 git 写操作 |
| Developer（Development Agent） | 唯一执行者，一开到底 | 只本地 commit，禁 push/merge/rebase/cherry-pick |
| Quality（Quality Agent） | 独立内审 + 提审 + 合并唯一执行者 | 唯一可 push/提审/merge，仍需 Owner 授权 |
| Reviewer（独立外审 Agent） | 只读、固定 SHA 外审 | 只给 Verdict + 证据，不改文件 |

- 全项目只允许一个 Developer 串行修改产品代码。
- Quality 默认只读，只写 `.project-ai/reviews/**` 审核报告，不修改产品代码。
- Developer 对 Finding 先复核再修复；Quality 负责独立复审，执行者不能自关 Finding。
- Quality 不得把自己的建议写进 Developer 的提交；任何获授权的质量修复也必须使用独立分支和 `Code-Origin: Quality` 提交（见 §3.3），默认流程不启用该例外。
- Scheduler 可由 Owner 兼任或独立承担；冻结执行路线唯一来源是本文件，Scheduler 不得自创 Stage。
- 计划、实现、质量控制、外部审核必须职责分离；参与实现的 Agent 不得代替独立 Reviewer 给外审结论。

### 3.2 开发进度门禁移交 Quality

自 V3.3 起，Development Agent **一开到底**：完成一个包后立即生成快照并继续下一个已定义包，不等待审核结论、不等待合同冻结、不等待 Owner 决策、不等待 Stage Gate 关闭。

Development Agent 的绝对硬停止（不可解除，非进度门禁）：

- §0.1 永久禁止项（修改其他仓库、改语言、改链上、执行生产 DDL/数据/部署/密钥/链上广播/真实价值、删除已完成冻结代码）。
- 下一包未在本文件定义（不得自行新增/删除/合并/拆分/跳过/重排 Package）。

其余原本会阻塞 Development Agent 进度的条件，一律降级为 Quality Agent 审核时的验证项（见 §15）。Development Agent 遇到这些条件时不停下，改为 best-effort 继续，并在交接文件显式声明：

```text
CONSUMED_UNFROZEN_CONTRACT = <合同名>
OPEN_OWNER_DECISION = <决策 ID>
OVERLAPS_LOCKED_SNAPSHOT = <path>
```

best-effort 继续的规则：

- 消费未冻结合同：按 05/Freeze 已知内容实现，受影响写路径保持 fail-closed；不得用旧值或 mock 补洞。
- Owner 决策未决：生成 Decision Request 后继续无依赖部分；依赖该决策的对象保持 fail-closed。
- 与 Quality 已锁定快照路径重叠：继续，但必须在交接声明重叠范围，供 Quality 复审。

不变式（仍成立）：

```text
DEV_NEXT_PACKAGE_ALLOWED != CURRENT_PACKAGE_MERGE_APPROVED
QUALITY_APPROVED != PRODUCTION_APPROVED
SNAPSHOT_LOCKED != FINDINGS_CLOSED
```

#### 双流水线

开发线与质量线是两条独立流水线，不得混成一个提交，也不得让外审进度阻塞 Developer 推进无依赖 Stage（执行细节见 `.project-ai/rules/workflow.md` §1）：

```text
开发线：IMPLEMENTING_STAGE_N → LOCAL_VALIDATED → DEVELOPER_COMMITTED → IMPLEMENTING_STAGE_N+1
质量线：DEVELOPER_COMMITTED → QUALITY_REVIEW → FIXED_REVIEW_HEAD → EXTERNAL_REVIEW → OWNER_APPROVED_STAGE_EXIT → MERGED
```

### 3.3 提交来源与提审不得混淆

V3.4 起，提交来源归属统一使用完整 trailer（`Code-Origin` + `Git-Operator`）；V3.3 及更早的 `origin:developer` / `origin:quality` 简写视为同一来源归属的等价记录，追溯有效，但新提交必须用完整格式。

Developer 实现提交：

```text
feat(<scope>): <stage-id> <summary>

Stage: <stage-id>
Code-Origin: Developer
Git-Operator: Developer
```

Quality 获授权修复提交（默认不启用，见 §3.1）：

```text
fix(review): <stage-id> close <finding-ids>

Stage: <stage-id>
Code-Origin: Quality
Git-Operator: Quality
Review-Findings: <P1-001,P2-002>
```

硬规则：

- Developer 与 Quality 的代码禁止混入同一 commit；
- 禁止 amend、squash、rebase 或重新复制文件来抹掉来源边界；
- 重新提审时审核范围覆盖当前 Stage 从基线到最新固定 SHA 的完整提交链，而非只审最后一个修复 commit。

Quality 报告文件：

```text
.project-ai/reviews/<review-id>-QUALITY-REVIEW.md
```

每个快照只审核明确的 `BASE_COMMIT..SNAPSHOT_COMMIT`。Developer 后续提交不自动进入旧审核范围。

### 3.4 独立审核 worktree 与门禁清单

Quality 审核对象 = 固定 SHA 的独立 worktree（分支 `gainode/review/<stage-id>`，base=`master`），物理隔离，不碰 Developer 的 HEAD 与工作区；建立/回滚命令见 `.project-ai/rules/git-review-worktree.md`。

Quality 在「审核—提审—合并」环节逐项把关以下 11 项门禁（见 `.project-ai/rules/workflow.md` §8），任一不满足即阻断对应 Stage，但不要求 Developer 停止后续无依赖 Stage：

1. 基线一致性；2. Stage 边界；3. 来源归属；4. 合同一致性；5. 安全红线；6. 测试真实性；7. 覆盖完整性；8. 待决项上报；9. P0/P1 阻断；10. 外审对象一致；11. 合并顺序。

以下仍为硬停止（任何角色不得绕过，需 Owner 单独授权）：push/merge/rebase/cherry-pick/tag/release/deploy；资产/账本/真实资金/生产数据变更；修改已完成只读基线。

---

## 4. 所有工作包统一执行模板

Development Agent 对每个工作包严格执行以下 12 步，不得自行调整顺序：

1. 校验工作区、分支、HEAD、工作树和当前 Stage。
2. 读取本工作包列出的 `INPUTS`，不读取历史文档补业务。
3. 输出 `REUSE_MATRIX`：`KEEP / EXTEND / NEW / FORBIDDEN_TO_TOUCH`。
4. 输出准确的 `ALLOWED_PATHS`、`LOCKED_PATHS`、`NON_GOALS`。
5. 已冻结合同先验证再写代码；未冻结合同按 05/Freeze 已知内容 best-effort 实现，受影响写路径 fail-closed，并在交接声明 `CONSUMED_UNFROZEN_CONTRACT`。
6. 按本工作包文件/对象顺序逐项实施；完成一项立即运行局部验证。
7. 完成包级静态检查、测试和 `git diff --check`。
8. 自审：业务规则、状态、幂等、并发、权限、账本、通知、安全、历史兼容。
9. 生成单一提交；禁止混入无关格式化、依赖升级或历史重构。
10. 生成快照、变更清单、测试证据、未执行项和风险清单。
11. 锁定 Snapshot 后交 Quality；报告必须附可直接使用的审核提示词和审核范围。
12. 不停下等待，继续下一个已定义包；未决合同/决策在交接文件声明（见 §3.2）。

每包交接文件必须包含：

```text
PROJECT = Gainode
WORKSPACE = E:\github\sports
FORMAL_STAGE =
PACKAGE_ID =
BASE_COMMIT =
SNAPSHOT_COMMIT =
PRODUCT_CODE_CHANGED =
MODIFIED_FILES =
COMPLETED_ITEMS =
NOT_IMPLEMENTED =
VALIDATIONS_RUN =
VALIDATIONS_NOT_RUN =
KNOWN_LIMITATIONS =
CONSUMED_UNFROZEN_CONTRACT =
OPEN_OWNER_DECISION =
OVERLAPS_LOCKED_SNAPSHOT =
SNAPSHOT_LOCKED =
NEXT_PACKAGE =
DEV_NEXT_PACKAGE_ALLOWED = YES（一开到底，见 §3.2）
CURRENT_PACKAGE_MERGE_APPROVED = NO
PRODUCTION_APPROVED = NO
```

---

## 5. 正式 Stage 总览

| Formal Stage | 目标 | 结束条件 |
|---|---|---|
| STAGE-00 | 产品、原型、架构和 MC1 基线 | 已完成 |
| STAGE-01 | 机器合同、DDL、全量领域对象/投影骨架 | 全部包通过审核，未冻结合同引用为 0 |
| STAGE-02 | OpenAPI、环境合同和后端 P0 业务闭环 | 后端 P0 API、状态、账本、权限、Outbox 测试通过 |
| STAGE-03 | H5 + Admin 增量升级与逐流程联调 | P0 页面、7 语言、三尺寸、API 联调通过 |
| STAGE-04 | Flutter App | P0 Page ID、状态、视觉和 API 联调通过 |
| STAGE-05 | Sandbox E2E 与迁移演练 | 15 个极端场景、账本守恒、回滚演练通过 |
| STAGE-06 | 发布就绪审查 | 安全、性能、观测、运维材料就绪；仍不自动部署生产 |

### 门禁语义统一覆盖条款（V3.3 起，V3.4 沿用）

自 V3.3 起，本文件所有 Package 内的「前置」「停止条件」「Stage Gate」字段**不再作为 Development Agent 的进度阻塞条件**。Development Agent 一开到底，这些字段统一解释为 Quality Agent 审核/提审时的验证项与风险登记点（见 §3.2 与 §15）。Development Agent 触达这些字段时不停下，按 best-effort 继续并在交接声明；Quality Agent 审核时逐项核对是否违反，违反则记为 Finding。唯二硬停止是 §0.1 永久禁止项与「包未在本文件定义」。

---

## 6. STAGE-01 · 机器合同与后端领域对象

**阶段根目录**：`0.5代码/gainode后端/gainode`。本阶段只冻结合同、DDL、Model/DAO/Service 骨架和只读投影；P0 业务写流程留给 STAGE-02。每个包必须输出 `OBJECT_COVERAGE_MATRIX`，列出对象、来源章节、持久化类型、DDL、Model、DAO、Service、Authoritative Writer、状态合同和测试证据。实际进度以 `.project-ai/manifest.yaml`、当前 task、Git 提交和 Quality 报告为准；已有 APPROVED/FROZEN/IMPLEMENTED 证据的包只复核证据，禁止重做。

### S01-P01 · MC2 修复快照重提与冻结

**输入/绑定**：实现提交 `2795e38`、`.project-ai/tasks/TASK-20260815-001/**`、`sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`、`20260815_machine_contract_batch2_audit_events.sql`、`20260815_machine_contract_batch2_ledger_object_version.sql`、`CHANGE_REQUEST_CR-20260815-001.md`。

**目标**：把绑定 `2795e38` 的 MC2 修复证据完整提交独立复审，并且只在 APPROVED 后形成可追溯的 FROZEN 状态；不得借提审实现新业务。

**允许路径**：上述 task/freeze/dated SQL、`.project-ai/reviews/S01-P01-*`、进度指针；**禁止路径**：MC1 SQL、8 个已实现实体骨架、业务 Service 实现。

**固定步骤**：

1. 检查 `2795e38` 存在并记录父提交、5 个变更文件、Git blob 和 SHA-256，不 reset 当前分支。
2. 从该 Git tree 建立隔离只读快照，生成 manifest、完整 diff、逐文件副本和 secret-scan 结果。
3. 对 IR 686 的统一经济锁、PRE_HOLD_MUTATION_GUARD、OBJECT_VERSION_CONFLICT 和证据完整性逐项做 design/freeze/acceptance 三方交叉检查。
4. 运行 Markdown 链接、SQL 对象引用、`git diff --check` 和未截断性检查；未执行运行时测试必须标 `NOT_RUN`。
5. Quality 逐条复审；Development 对 Finding 先 adjudicate，再做绑定原提交的最小修复并重提新快照。
6. 仅在 P0/P1/blocking P2=0 且 Final Review=APPROVED 后，用独立状态提交更新 MC2 为 FROZEN。

**验证/交付**：`git diff --check`、Freeze/SQL 交叉引用清单、review manifest/hash、独立审核报告、FROZEN 状态提交。

**停止条件**：哈希或 review range 不一致；报告截断；修复要求改变 Owner 裁决、经济语义或 MC1 DDL。前两项只重建提审包，后一项生成 Change Request。

**验收**：审核绑定不漂移；MC2 状态只由独立 APPROVED 证据推进；已冻结后 `EXECUTION_DISPOSITION=COMPLETED_DO_NOT_REDO`。

### S01-P02 · 2B-1 状态合同补齐

**固定对象**：`Result / Settlement / SettlementBatch / RefundCase / CorrectionCase / OtcTrade / RobotUpgradeOrder / ConsentReceipt / AuditEvent`。

**目标文件**：新 task 的 `requirement.md/design.md/acceptance.md`、`05_DATA_STATE_PERMISSION_API_CONTRACT.md` 对应 §3/§4、`sql/MACHINE_CONTRACT_BATCH2B1_STATE_FREEZE.md`、`.project-ai/reviews/S01-P02-*`。AuditEvent 只引用 MC2 `audit_events`，不得建立第二份 DDL。

**固定步骤**：

1. 从 05 §3 逐字段复制最低对象契约；为每字段标注 type/nullability/source/immutability/PII/authoritative writer。
2. Result/Settlement 只复制 05 §4 canonical enum，补齐 initial/transition/terminal/actor/writer/error/retry/idempotency/audit/ledger side-effect 表。
3. 对其余缺 enum 对象逐个生成 Owner Decision，固定给出 2–3 个互斥方案、推荐项、兼容性和失败关闭行为，不替 Owner 选择。
4. 将 Owner 已签方案写回 05 与 Freeze Candidate；未签项保持 `CONTRACT_GAP/FAIL_CLOSED`，不得先建表或在代码中发明 enum。
5. 检查 Result official、Settlement paid、Market settled 三条状态轴没有合并；修正/重结算协同未冻结时留到 STAGE-02 fail-closed。
6. 生成对象覆盖矩阵和机器可比 enum 清单，提交 Independent Review；按 Finding 最小修复并复审。

**验证/交付**：9 对象字段矩阵、状态转移矩阵、Owner Decision 记录、Freeze Candidate、enum 差异脚本输出、Markdown/引用检查、审核包。

**停止条件**：Owner 未确认缺失 enum；修复会改变 MC2；同名实体或 DDL 已存在。分别保持 fail-closed、发 Change Request、改为复用而非复制。

**验收**：9 对象均有明确 persistent/projection/value-object 分类；enum、writer、幂等和账本副作用无空白；独立审核后才置 FROZEN；已通过证据的内容禁止重写。

### S01-P03 · 2B-1 DDL 与 Model/DAO/Service 骨架

**前置**：S01-P02 对应合同 FROZEN；corrected 重结算等 deferred 业务继续 fail-closed。

**目标文件**：

```text
sql/YYYYMMDD_machine_contract_batch2b1_*.sql
library/model/prediction/{Result,Settlement,SettlementBatch,RefundCase,CorrectionCase}Model.php
library/dao/prediction/{Result,Settlement,SettlementBatch,RefundCase,CorrectionCase}Dao.php
library/service/prediction/{Result,Settlement,SettlementBatch,RefundCase,CorrectionCase}Service.php
library/{model,dao,service}/otc/OtcTrade*.php
library/{model,dao,service}/robot/RobotUpgradeOrder*.php
library/{model,dao,service}/policy/ConsentReceipt*.php
library/{model,dao,service}/audit/AuditEvent*.php
tests/contract/Stage01Batch2B1*Test.php
```

**固定步骤**：

1. 先盘点现有表和类，输出 KEEP/EXTEND/NEW；`audit_events`、ledger object_version 和已存在骨架不得重复生成。
2. 按 Freeze 生成单个 forward-only dated SQL；Snowflake `bigint unsigned`、decimal 禁止 float、FK/UNIQUE/CHECK/索引与 writer boundary 逐项落表。
3. 按 Result→Settlement→SettlementBatch→RefundCase→CorrectionCase→OtcTrade→RobotUpgradeOrder→ConsentReceipt 顺序，每次只完成一个对象的 Model→DAO→Service。
4. Model 只映射冻结字段/enum/主键/时间列；DAO 默认只读；Service 标 `@authoritative_writer` 并对未到 STAGE-02 的写流程显式 fail-closed。
5. AuditEvent 使用独立 append-only Builder/DAO 白名单和审计测试，禁止复制 ledger 专属列名。
6. 每完成一个对象立即运行 PHP syntax、autoload/class-load、enum 三方比对；全部完成后运行 composer test、DDL review 和 diff check。
7. 提交只含本包 DDL/骨架/测试，锁定 33 类目标文件或实际核定清单后提审。

**验证命令/方法**：`php -l` 覆盖全部变更 PHP；`composer dump-autoload --no-scripts`（仅本地依赖齐全时）；`composer test`；DDL parser/review；`enum(Freeze)=enum(DDL)=enum(Model)`；append-only negative matrix；`git diff --check`。

**停止条件**：合同非 FROZEN；目标表/类重复；需要新增未冻结字段或依赖；测试需要生产数据库。保持 fail-closed，生成冲突/依赖决策，不连接生产。

**验收**：每个冻结持久对象正好一套 DDL/Model/DAO/Service；AuditEvent 复用既有 DDL；无业务提前实现；P0/P1/blocking P2=0 后关闭。

### S01-P04 · 2B-2 合同补齐

**固定对象**：`ApprovalRequest / ParameterRelease / ParameterSnapshot / Notice / NotificationDelivery / AuthSession / MfaEnrollment / KycCase / RiskCase / Ticket / TicketMessage / TicketAttachment / SettlementMethod`。

**目标文件**：新 task 三件套、05 §3/§4/§8/§11、06 参数生命周期、`sql/MACHINE_CONTRACT_BATCH2B2_STATE_FREEZE.md`、`.project-ai/reviews/S01-P04-*`。

**固定步骤**：

1. 为 13 对象建立字段来源矩阵，逐字段标 persistent/workflow/projection/value-object、PII 等级、可见角色和 writer。
2. 复制 05 已有 Approval、ParameterRelease、AuthSession、KYC、Ticket canonical 状态，不修改 enum；缺失状态逐对象生成 Owner Decision。
3. 固化 Actor-level SoD：PARAM_EDITOR、PARAM_APPROVER、RELEASE_OPERATOR 三人分离；RISK_ANALYST 与 RISK_APPROVER 分离；申请人与审批人 ID 不同。
4. 固化 Parameter Candidate→Approval→Release→Schedule/Active/Pause/Rollback→Snapshot；approved 不等于 active，历史对象引用 immutable snapshot。
5. 固化 Notice 为用户只读聚合、NotificationDelivery 为投递工作流；业务提交成功后投递失败只进重试/死信，不回滚业务。
6. 固化 Ticket/Message/Attachment、MFA/KYC/Risk 的权限、PII、重试、失败和审计；SettlementMethod 只作值对象/投影，不引入资金写入者。
7. 将 Owner 已签项写入 05/Freeze Candidate，生成 schema/state/role/API dependency matrix，提交独立审核。

**验证/交付**：13 对象覆盖矩阵、状态/SoD/PII/通知事务边界表、2B-2 Freeze Candidate、Owner Decision、Markdown/enum/reference 检查、审核包。

**停止条件**：角色、状态、正式参数或紧急权限未签；任何对象要求直接改账；审批链允许同一 actor 自批。保持相应能力 CLOSED 并提交 Owner Decision。

**验收**：13 对象无未分类字段；writer/SoD/idempotency/audit/PII 全明确；未决项可追踪且 fail-closed；Independent Review 通过后置 2B-2 FROZEN。

### S01-P05 · 2B-2 DDL 与骨架

**前置**：S01-P04 FROZEN。**目标目录**：`library/{model,dao,service}/{approval,parameter,notice,auth,kyc,risk,support,settlement}/`、对应 dated SQL、`tests/contract/Stage01Batch2B2*Test.php`。

**对象批次顺序**：ApprovalRequest → ParameterRelease/ParameterSnapshot → AuthSession/MfaEnrollment/KycCase → RiskCase → Ticket/TicketMessage/TicketAttachment → Notice/NotificationDelivery → SettlementMethod。

**固定步骤**：

1. 盘点已有 V1 类/表并建立 KEEP/EXTEND/NEW/FORBIDDEN_TO_TOUCH，避免复制 sys_notice、member_user_kyc 等同义对象。
2. 对持久对象生成 forward-only dated SQL；Projection/Value Object 明确 `NOT_PERSISTED`，不得为了统一目录建空表。
3. 每个对象单独完成 DDL→Model→DAO→Service→syntax/class-load→contract test，再进入下一对象。
4. 对 Approval/Parameter/Risk 写入 Service 加 actor ID/role/object_version/approval binding guard；未冻结业务方法抛稳定 fail-closed 错误。
5. ParameterSnapshot、Notice、审计类历史字段 immutable；NotificationDelivery 重试键、dedupe key、next_retry_at 与死信状态遵循 Freeze。
6. KYC/Ticket 附件只存后端签发对象引用，禁止前端永久云密钥和任意 URL。
7. 汇总 DDL/Model enum、唯一约束、writer 和 tests，运行包级验证并提审。

**验证**：全部变更 PHP `php -l`；autoload/class-load；`composer test`；DDL parse/duplicate-table/enum/PK-FK-index review；SoD negative tests；append-only/immutable negative tests；`git diff --check`。

**停止条件**：对象合同非 FROZEN；与 V1 表语义冲突未裁决；需要新增依赖；写路径绕过 Service。只停止相关对象并生成 Decision/Change Request，其他无依赖对象可继续。

**验收**：13 对象逐项有交付或明确 NOT_PERSISTED；无重复表；权限与 writer 可由测试证明；本阶段不实现完整业务闭环。

### S01-P06 · 非持久投影服务

**禁止建表对象**：`FeatureEntitlement / OtcEligibility / OtcCapacity / PowerImpactPreview / SecurityProfile / SessionDevice / LoginAudit`。

**目标文件模式**：`library/response/<domain>/<Object>Response.php`、`library/service/<domain>/<Object>ProjectionService.php`、`tests/projection/<Object>ProjectionTest.php`；实际命名必须先匹配现有 namespace，禁止另造第二套根目录。

**固定步骤**：

1. 从 05 对每个对象生成 DTO 字段表和 source-of-truth 列表，标出必填/null、decimal string、data freshness 和脱敏。
2. 为每个投影定义输入读取顺序、依赖不可用行为和默认 deny；不得把聚合结果写回新表。
3. 实现 `data_status/as_of/updated_at/next_refresh_at/snapshot_id/source_status`；TBC/null 不回退旧值或 mock。
4. FeatureEntitlement 输出 allowed/denied/reason_codes/allowed_actions；OtcEligibility 与 OtcCapacity 分离；PowerImpactPreview 只返回服务端计算结果。
5. SecurityProfile/SessionDevice/LoginAudit 按字段权限脱敏，越权对象返回安全 reason，不泄露存在性。
6. 为 REALTIME/STALE/UNAVAILABLE、依赖超时、无 Active Release、Restricted 和跨用户访问写单元测试。

**验证**：PHP syntax/class-load；projection unit tests；JSON schema snapshot；decimal-string/no-float scan；无新 DDL 检查；`git diff --check`。

**停止条件**：DTO 字段在 05 缺失；聚合依赖未冻结；实现需要持久化。输出 Contract Gap，不自行建表。

**验收**：7 个对象全部 NOT_PERSISTED；默认 deny/数据新鲜度/脱敏有测试；无 mock fallback 和前端资格推导。

### S01-P07 · Affiliate/Agent P1 合同与骨架

**固定对象**：`Agent / Referral / AgentEarning`。**业务边界**：P1，P0 正式奖励关闭；不得使用用户本金、退款、Prediction 结算或未批准预算支付增长奖励。

**目标文件**：`.project-ai/tasks/<task-id>/{requirement,design,acceptance}.md`、`05` 的 P1 对象/API附录、`06` Growth/Team 参数章节、`sql/MACHINE_CONTRACT_AFFILIATE_AGENT_P1_FREEZE.md`；合同通过后才允许 `library/{model,dao,service}/growth/`、dated SQL 和 tests。

**固定步骤**：

1. 只从 01 §P1/核心对象、02 §Team/Referral、06 §Growth/Team、现有 `member_user_team` 代码提取候选，不把旧佣金语义自动继承。
2. 为三个对象生成字段候选表，至少要求 ID、关联 user/agent、source object、status/eligibility、snapshot/rule version、amount quantity string、audit timestamps；每个字段标 `SOURCE_CONFIRMED` 或 `OWNER_DECISION_REQUIRED`。
3. 生成状态、层级深度、重复归属、解绑/更换、earning 何时确认、预算来源、回滚/reversal、税务/合规和 PII 的固定 Decision Matrix。
4. Owner 未签前，API/DDL/Service 全部 `CONTRACT_GAP/FAIL_CLOSED`；只允许只读 Team 兼容盘点。
5. Owner 签署后，更新 05/06，形成 Freeze Candidate 并独立审核；不通过不得建表。
6. 合同 FROZEN 后，在同一 Package 的第二快照按 Agent→Referral→AgentEarning 顺序生成 DDL/Model/DAO/Service；Earning append-only/reversal，不允许 update 历史金额。
7. 写 duplicate referral、self referral、cycle、cross-tenant、budget unavailable、repeat earning、reversal 和权限负向测试。

**验证**：合同来源矩阵；Owner Decision 完整性；SQL/enum/writer review；PHP syntax/class-load；growth contract tests；资金来源扫描；无 P0 路径暴露检查。

**停止条件**：奖励预算、层级、状态或地区合规未签；旧 Team 数据无法一一映射。保持正式写操作关闭并输出迁移/Owner Decision，不让 Agent 自选。

**验收**：三个对象的合同与骨架均可追溯；P0 默认关闭；AgentEarning 不触碰用户本金/退款/结算账；合同快照和代码快照分别经 Quality 审核。

### S01-P08 · AI Operations P1 与内部套利引擎边界

**固定对象**：`AISignal / AIRecommendation / SimulationRun`。**固定边界**：BetBurger/API-Football 仅内部输入；C 端不得返回 arbitrage signal、profit、position 或供应商原始 payload。

**目标文件**：新 task 三件套、05 内部对象/API附录、06 AI 参数引用、`sql/MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md`；合同通过后才允许 `library/{model,dao,service}/aiops/`、`app/command/aiops/`、dated SQL 和 tests。

**固定步骤**：

1. 盘点现有 `library/{model,dao,service}/arbitrage` 和配置，输出 KEEP_INTERNAL/ADAPT/RETIRE/FORBIDDEN_TO_EXPOSE；不删除旧代码、不沿用硬编码 secret。
2. 为 AISignal 定义数据来源引用、received/observed time、normalized payload hash、quality/source status、dedupe key、retention 和 PII/secret 分类候选。
3. 为 AIRecommendation 定义输入 signal refs、model/rule/parameter version、explanation、安全 reason、有效期和 `INTERNAL_ONLY` 输出边界候选。
4. 为 SimulationRun 定义 deterministic input snapshot、seed/version、start/end/status、metrics、failure reason、artifact hash 和审计候选。
5. 对状态、retention、供应商许可、writer、重试、幂等、预算连接和模型版本生成 Owner Decision；未签时不建表。
6. 合同 FROZEN 后按 AISignal→AIRecommendation→SimulationRun 建 DDL/Model/DAO/Service/command 骨架，所有对外 serializer 明确 deny 内部字段。
7. 测试原始供应商 payload 不出现在 C 端 API/日志、重复 signal 去重、stale source、simulation 可复现、无 Active Parameter fail-closed。

**验证**：internal-boundary static scan；secret/PII scan；schema/enum/writer review；PHP syntax/class-load；determinism/dedupe/serializer negative tests。

**停止条件**：供应商许可、retention、状态或模型版本策略未签；需要把内部数据暴露 C 端。前者提交 Owner Decision，后者直接禁止并报 Scope Finding。

**验收**：三个对象合同/骨架可审计；内部与 C 端 serializer 隔离有负向测试；正式经济计算仍留 S02-P08。

### S01-P09 · STAGE-01 全量收口

**目标文件**：`.project-ai/reviews/STAGE-01-OBJECT-COVERAGE-MATRIX.md`、`STAGE-01-QUALITY-GATE.md`、manifest/context 进度指针；不得为通过 Gate 修改产品代码，缺陷回原 Package 修复。

**固定步骤**：

1. 从 01/02/05/06 枚举所有持久对象、工作流、Projection 和 Value Object，与实际 DDL/类建立双向矩阵。
2. 检查每个持久对象只有一份 forward-only DDL、一个 Authoritative Writer；每个 NOT_PERSISTED 对象无表。
3. 机械比对 Freeze/DDL/Model enum、字段、decimal、主键、object_version、immutable/append-only 和索引。
4. 检查所有未冻结转移、P1 未签能力、APT-C/Migration 和生产参数仍 fail-closed。
5. 运行全部 PHP syntax、autoload/class-load、`composer test`、contract/projection tests、DDL review、secret scan 和 diff check。
6. 对每个 S01 Package 核对 Snapshot、Quality report、Finding closure 和当前 Git blob；缺报告不得用自审替代。
7. Quality 单独输出 STAGE-01 Gate；只有 P0/P1/blocking P2=0 且未冻结合同引用=0 才建议 STAGE-02。

**验证**：执行步骤 2–6 的对象/enum/DDL/测试/审核证据检查，并把原始命令、退出码和未执行项写入 Stage Gate。

**停止条件**：对象漏项、重复表/writer、合同引用未冻结、测试证据缺失。回到责任 Package 最小修复，不在 Gate 包直接重构。

**验收矩阵**：对象覆盖=100%；重复 DDL=0；unknown writer=0；enum mismatch=0；未冻结可写路径=0；MC1 重做=0；全部 Package 独立审核完备；Production 仍 NO-GO。

---

## 7. STAGE-02 · OpenAPI、环境合同与后端 P0 业务闭环

**阶段根目录**：`0.5代码/gainode后端/gainode`。实现顺序固定为 Contract→Controller/Validator→Application Service→Domain Service/DAO→Transaction/Outbox/Audit→Tests。路由继续复用 Webman 的 `config/route/{api,admin}.php` 与数据库驱动 `sys_route` 模式；未经架构 Change Request 不建立第二套路由框架。每个写 API 必须绑定 auth、idempotency key、object_version/If-Match（适用时）、request_id、rule_version、parameter_release_id 和 snapshot_id。

### S02-P01 · OpenAPI 3.1、Environment 与通用内核

**目标文件**：`openapi/gainode-v2.yaml`、`openapi/{schemas,paths,components}/**/*.yaml`、`.env.example`、`library/response/`、`library/dict/ErrorDict.php`、`app/{api,admin}/middleware/`、`library/service/{idempotency,outbox,audit,transaction}/`、`tests/{Contract,Integration,Feature}/`。

**固定步骤**：

1. 从 05 §1/§6/§7 冻结 base URL、六请求头、认证、统一 success/error envelope、cursor pagination、decimal string、日期/时区和错误分类。
2. 按 Auth→Robot→Prediction→APT/OTC→Policy/Parameter→Admin 顺序拆 paths；schema 只引用 05 已冻结对象，不为未冻结字段补默认值。
3. 每个 operation 补唯一 operationId、security、required、closed schema、错误响应、idempotency、If-Match/object_version、敏感字段可见性和示例；示例只用 fixture。
4. 实现统一 Response/Error/RequestContext middleware，解析六请求头并生成 request_id；旧签名密钥移到 env，缺失时 fail-closed。
5. 实现 IdempotencyRecord、Outbox、AuditEvent 的通用接口和 transaction boundary；若持久对象未在 STAGE-01 冻结，先停该子项并发 Change Request。
6. `.env.example` 只写变量名、安全关闭值和说明，禁止 secret、生产 URL、正式参数；production 配置不在本包创建。
7. 建立 Contract/Integration/Feature 测试入口；新增依赖前核对 manifest，未批准依赖先生成 Dependency Decision。

**验证**：OpenAPI parse、local `$ref`、operationId uniqueness、required/closed schema/auth/idempotency lint；`.env.example` secret scan；PHP syntax/class-load；统一响应 contract tests；`git diff --check`。

**停止条件**：OpenAPI 与 05/Freeze 冲突；需要新增未冻结 API/DDL/依赖；配置要求真实 secret。分别提交 Contract/Dependency Decision，不用 mock 假装通过。

**验收**：所有 P0 path 可解析且无 dangling ref；所有写 operation 有安全/幂等/错误合同；环境变量缺失安全关闭；通用内核有正反向测试。

### S02-P02 · Auth / KYC / User / Eligibility

**目标模块**：`app/api/controller/{Login,Auth,Session,Kyc,User,Policy}Controller.php`（按现有命名合并而非强制新建同义类）、`library/{validator,service,response}/{auth,kyc,member,policy}/`、`tests/{Contract,Integration,Feature}/{Auth,Kyc,Eligibility}/`、对应 OpenAPI paths/schemas。

**固定步骤**：

1. 盘点 V1 登录/用户/KYC 代码，保留可用 hash/OTP/JWT/Casbin 入口，移除硬编码 key 的使用路径，不重写无关模块。
2. 按注册→登录→OTP resend/verify→找回→密码重置实现 validator/controller/service；所有失败使用不存在性安全文案和频控。
3. 按 MFA enrollment setup→confirm→challenge→recovery/disable 实现；secret 只经后端安全存储，日志/响应不得回显。
4. 按 session issue→refresh rotation→list devices→revoke one→logout all 实现；refresh 重放和已撤销 token 必须失败关闭。
5. 按 KYC submit→under_review→needs_info→resubmit→approve/reject 实现；Reviewer 与资产角色隔离，附件走后端签发对象引用。
6. 实现 FeatureEntitlement/allowed_actions 聚合：global_p、AI eligibility、Prediction eligibility 分开，policy/source timeout 默认 deny。
7. 写 LoginAudit 和安全 reason mapping；跨用户读取、账号枚举、OTP 爆破、MFA/session 并发和 KYC 越权负向测试。

**验证**：OpenAPI contract；PHP syntax；Auth/KYC/Policy unit+integration+feature tests；rate-limit/enum/no-account-leak/MFA replay/session rotation/ABAC negative tests；secret/log scan。

**停止条件**：OTP 供应商、正式 KYC 地区/年龄、MFA 恢复政策未批准。使用接口适配器和 TBC/closed 参数，禁止写生产默认值。

**验收**：六条子流程均有幂等/频控/审计；KYC 不触碰资产；restricted 与 error 区分；无权限与不存在不泄露对象信息。

### S02-P03 · Ledger / AptAccount / Power 基础

**目标模块**：`library/{model,dao,service}/{ledger,power,audit}/`、统一 economic mutation lock、Outbox adapter、`tests/{ledger,power,integration}/`、APT/Power OpenAPI。

**固定步骤（事务模板）**：

1. 按 idempotency key 查原结果；已完成返回原响应，处理中返回可查询对象，冲突拒绝。
2. 读取并 CAS 锁定 `apt_accounts.object_version`；读取 stored balances、aggregate dispute hold 和 PowerPosition version。
3. 计算 projected balance/effective_available/Power delta，执行 PRE_HOLD_MUTATION_GUARD、资格、Active Snapshot 和负数保护。
4. 追加 immutable LedgerEntry；经济字段禁止 update，仅冻结合同允许的 state/audit_event_id/object_version 受控转移。
5. CAS 更新 AptAccount/PowerPosition 投影；affected rows≠1 统一 `OBJECT_VERSION_CONFLICT(409)`。
6. 同事务追加 AuditEvent 和 Outbox；Outbox dedupe key 与业务对象/idempotency key 关联。
7. 提交后返回 decimal string、ledger entry IDs、rule/snapshot/version；通知异步失败不回滚业务。
8. reversal 必须追加反向记录并引用原 entry，禁止删除或覆盖；dispute 只按 MC2 四格矩阵处理。

**验证**：双击、并发余额/Power、超时后成功、negative balance、L4/L5 shortfall、reversal、pending reversal、dispute、CAS conflict、Outbox duplicate/replay、通知失败；账本/Power 前后守恒 SQL assertion。

**停止条件**：任何写路径需要直接 update ledger 经济列；无 Active Snapshot；跨账户锁顺序未定义。前两项拒绝写入，后一项提交并发设计 Decision。

**验收**：所有经济写路径复用同一事务模板；守恒与 exactly-once 可由测试证明；超级管理员无旁路。

### S02-P04 · Robot / Reward / Upgrade

**目标模块**：`library/{model,dao,service}/{robot,ledger,power,parameter}/`、`app/api/controller/RobotController.php` 或现有等价控制器、Robot/Reward/Upgrade OpenAPI、`tests/{robot,reward,integration}/`。

**固定步骤**：

1. 实现 56 级 RobotRule/ParameterSnapshot 读取器；level、capacity、coefficient、Power Cap、upgrade cost 全来自 Active Release/Snapshot。
2. 实现 Robot summary/detail/allowed_actions；无 Active Release 时读页面返回 unavailable reason，真实 start/upgrade/reward 写操作 closed。
3. 实现 start/stop quote：先返回 PowerImpactPreview 与 snapshot；confirm 时重新校验 object_version、资格和 snapshot 兼容，再调用 S02-P03 经济事务模板。
4. 实现 Upgrade quote→order pending→processing→completed/failed/cancelled；quote 过期、余额变化、并发升级和大额审批都按冻结合同处理。
5. 实现 Reward candidate→held→pending_claim→claiming→claimed；预算/资格/观察期/过期来自 snapshot，claim 使用 idempotency。
6. 实现 expiry budget return、review、失败和 reversal；原 Reward/ledger 历史不可覆盖。
7. 为每个状态转移写 actor/writer/guard/ledger/outbox/audit 集成测试，并验证前端不能传正式 coefficient/cap/cost。

**验证**：56级边界；无 Active Release；quote expiry；double start/upgrade/claim；并发升级；Power 不足；Reward budget cap；claim timeout；expiry/reversal；OpenAPI/PHP/tests/diff。

**停止条件**：56级规则或正式参数未 Active；升级资金去向/大额审批未冻结。保持相关 action closed，不用旧 Mining 值补洞。

**验收**：Robot、Upgrade、Reward 三状态轴分离；每次经济变化有 snapshot/ledger/audit；无固定收益或前端计算。

### S02-P05 · Prediction P0

**目标模块**：`library/{model,dao,service}/prediction/`、Consent/Policy/Ledger/Outbox 协作、`app/{api,admin}/controller/prediction/` 或现有等价路径、Prediction OpenAPI、`tests/{prediction,settlement,integration}/`。

**固定步骤**：

1. Market：create→open→closing→locked/exception/settling/settled；只允许 Football Pre-match 1X2、90分钟+伤停补时，数据源异常按 freshness/fail-closed。
2. Disclosure/ConsentReceipt：详情先取版本化 disclosure；提交订单前创建/校验 content_hash、consent_version、snapshot 和用户资格。
3. PredictionOrder：submit 与 same-selection addition 分开；不可取消、减少或换方向；锁定 market/order/account versions 后追加 stake ledger。
4. Result：provisional→official→disputed→corrected；主备源冲突进入人工/风险流程，official 不触发 UI 直接显示 paid。
5. SettlementBatch/Settlement：按 batch 切片、单笔计算、review/payable/paid/failed；每笔 posting 幂等且守恒，失败可重跑不重复支付。
6. RefundCase：只处理冻结场景，审批后按原 stake/relevant snapshot 追加退款 ledger；通知失败不回滚。
7. CorrectionCase：引用原 Result/Settlement/Ledger，先追加旧 posting reversal，再按 corrected snapshot 追加新 posting；Market/Result/Settlement 三轴协调按已签合同，未签部分 fail-closed。
8. 为用户、Admin、队列 writer 分别做 RBAC/SoD/状态 guard；保存 request_id、source evidence、approval、audit、outbox。

**验证**：market lock race、same-selection addition、opposite selection reject、double submit、result source conflict、batch partial failure/retry、refund duplicate、correction replay、official≠paid、ledger invariant、OpenAPI/PHP/tests。

**停止条件**：正式赛果源、锁盘参数、退款/修正协同未冻结；Result/Settlement 角色冲突。保持受影响 transition closed 并生成 Owner Decision。

**验收**：7 个对象流程均可重试、可审计、可回滚且不重复记账；三状态轴不合并；只有 P0 玩法开放。

### S02-P06 · OTC / Power

**目标模块**：`library/{model,dao,service}/{otc,power,ledger,policy}/`、OTC API/Admin controllers、OpenAPI、`tests/{otc,power,integration}/`。

**固定步骤**：

1. 每次进入 OTC 先实时计算 OtcEligibility/OtcCapacity；返回 allowed_actions/reason/snapshot/freshness，默认 deny。
2. Quote 读取 Active Parameter、余额、库存/容量并返回 decimal string、expiry、fee（有值时）和 PowerImpactPreview；quote 不产生账本。
3. Create order 重新校验 quote/user/account/Power versions；Sell 按 preview 冻结 Power，Buy 不套用 Sell Power 规则。
4. Review/matching 只能由冻结 writer 执行；匹配结果创建 append-only OtcTrade，并以 idempotency/dedupe 防重复成交。
5. Partial fill 每次只消耗成交部分 Power，remaining 继续冻结；订单与 trade/ledger/power 更新同事务或通过可证明 saga/outbox。
6. Completed 仅在 remaining=0 且订单/trade/ledger/Power 一致时进入。
7. Cancel/Expire 只释放 remaining；已成交部分不可回滚；并发 fill 与 cancel 以 object_version 决胜。
8. Dispute 保持冻结直到明确 Resolution；资金/Power 调整必须走 Approval+Ledger reversal，禁止直接改历史。

**验证**：eligibility timeout、quote expiry、balance changed、double create、partial fill accumulation、fill/cancel race、expire/release、buy/sell差异、trade append-only、dispute、守恒和 OpenAPI/PHP/tests。

**停止条件**：OTC 正式 fee/limit/库存/Power 参数未 Active；储备或 dispute 规则未冻结。真实写操作 closed，只保留安全只读投影。

**验收**：Sell Power freeze/consume/release 守恒；Buy 无误扣；OtcTrade 不可变；Completed 有三方一致证据。

### S02-P07 · Approval / Parameter / Risk / Support / Notice / Audit

**目标模块**：`library/{model,dao,service}/{approval,parameter,risk,support,notice,audit}/`、Admin/API controllers、OpenAPI、queue/outbox handlers、`tests/{approval,parameter,risk,support,notice,audit}/`。

**固定步骤**：

1. Approval：create→assign→approve/reject→consume/execute；校验 request type/object/id、initiator≠approver、未过期/未消费、object_version 和 evidence。
2. Parameter：Definition/Candidate→Approval→immutable Release→schedule/activate/pause/rollback→Snapshot；编辑、批准、激活由不同 actor，active 值不可直接改。
3. RiskCase：detect/create→assign→investigate→decision→execute/close；restricted 用户仍能访问历史、退款和 Support，allowed_actions 由投影返回。
4. Support：Ticket→Message→Attachment→assign/escalate/resolve/close；附件 presigned、病毒/类型/大小策略、PII 最小化和 SLA 审计。
5. Notice/Delivery：业务事务只写 Notice/Outbox；消费者按 channel/dedupe/retry/backoff 投递，失败进 dead-letter，不回滚业务。
6. Audit：append-only 写入；Admin 查询按 actor/object/action/time/request_id/audit_id，字段和数据范围双重脱敏。
7. Emergency：只实现已签合同；MFA、case_id/reason/evidence、actor-level SoD 和 48h post-review 缺一即拒绝。

**验证**：self-approval、role switching bypass、double decision/consume、parameter active mutation、rollback snapshot、risk restriction、attachment abuse、notice duplicate/failure、audit tamper/field leakage、super-admin bypass negative tests。

**停止条件**：紧急操作、风险策略、通知渠道或正式参数未签；所需依赖未批准。关闭对应写能力，其他模块继续。

**验收**：六域各有状态、权限、幂等、审计和失败恢复；业务成功/通知失败边界有测试；超级管理员无绕行。

### S02-P08 · 内部 AI 经济引擎

**输入/输出**：内部可审计执行结果、历史 reference、APT reference price snapshot、Active Parameter Release；只产出 `confirmed_profit/reference_profit/mapped_apt_budget/daily_ai_budget` 及其版本/审计，不直接给用户发 Reward。

**目标模块**：`library/service/aiops/{ConfirmedProfitAdapter,ReferenceProfitService,AptBudgetMappingService,DailyAIBudgetService}.php`、内部 DTO/Response、Parameter/Snapshot/Audit/Outbox adapter、`tests/{aiops,economics,integration}/`；禁止加入 C 端 serializer/route。

**固定步骤（计算流水线）**：

1. ConfirmedProfitAdapter 只接受已确认、可追溯、去重的内部结果，记录 source object/hash/currency/confirmed_at；未确认或重复输入拒绝。
2. 若 `confirmed_profit<=0`，确定性输出 `reference_profit=0`，不得调用 smoothing 产生正值。
3. 若大于 0，从 Active Release 读取 approved smoothing 规则和 historical_reference snapshot，计算并记录 algorithm/rule/version/input hash。
4. 读取同一时点可用的 APT reference price snapshot 与 mapping multiplier；缺失、过期、<=0 时 fail-closed，不除零、不回退 mock。
5. 计算 `mapped_apt_budget=reference_profit_USDT/apt_reference_price*mapping_multiplier`，全程 decimal/string/指定精度，禁止 float。
6. 从 Active Release/Snapshot 读取 stage_expected_budget、stage_hard_cap、cash_support_cap、human_approved_cap，取最小值得 daily_ai_budget；任一 required cap 缺失则 closed。
7. 以 source+parameter release+snapshot+business date 建 idempotency key，持久化/追加预算决定和 AuditEvent，Outbox 只通知内部预算消费者。
8. 对外 API/日志/通知 serializer 负向扫描：不得泄露供应商、arbitrage signal、profit detail、position、cap 明细或内部模型参数。

**验证**：profit<=0、positive smoothing、missing/stale price、zero price、missing cap、cap min selection、precision/rounding、same input replay、parameter changed snapshot isolation、concurrent budget generation、C端字段泄漏、audit reproducibility。

**停止条件**：smoothing、price source、mapping multiplier、任何 cap 或 rounding 未由 Active Release 定义；预算持久对象未冻结。保持引擎 closed，生成 Parameter/Contract Decision，不写默认值。

**验收**：四字段可从 snapshot 完整重放；daily budget 不超过五类上限中的最小值；不直接修改用户 APT；内部边界有自动负向测试。

### S02-P09 · 后端 Gate

**目标文件**：`.project-ai/reviews/STAGE-02-BACKEND-COVERAGE.md`、OpenAPI lint evidence、test/coverage/invariant evidence、`STAGE-02-QUALITY-GATE.md`；Gate 包不直接修产品代码。

**固定步骤**：

1. 将 05 §6 每个 P0 API 映射到 OpenAPI operation、route、controller、validator、service、test；缺一返回责任 Package。
2. 将所有状态转移映射到 writer/guard/idempotency/audit/outbox/test，检查非法出边和终态保护。
3. 运行 OpenAPI parse/ref/operationId/required/auth/idempotency/closed-schema；运行全部 PHP syntax、unit/integration/feature/contract tests。
4. 执行 RBAC/ABAC/SoD/field masking、Secret、依赖、SQL、append-only/immutable 审查。
5. 执行 Ledger/APT/Power/Reward/OTC/Prediction 守恒、reversal、Outbox replay、process restart 和至少 15 场景的后端部分。
6. 核对 S02-P01..P08 的独立 Snapshot/Quality/Finding closure，汇总未运行项和 Owner 条件。
7. Quality 独立给 STAGE-02 Gate；不通过的缺陷回责任包最小修复。

**验证**：执行步骤 1–6 的 API、状态、权限、守恒和完整测试检查，保存命令、退出码、覆盖矩阵和未运行项。

**停止条件**：OpenAPI/实现漂移、P0/P1/blocking P2、守恒失败、未冻结写路径、必要运行时证据缺失。

**验收矩阵**：P0 API 覆盖=100%；非法状态出边=0；直接账本经济列更新=0；SoD bypass=0；核心测试通过；独立 Gate APPROVED 后才进入 STAGE-03；Production 仍 NO-GO。

---

## 8. STAGE-03 · H5 与 Admin 增量升级

**阶段边界**：以前端目录决策和 STAGE-02 APPROVED OpenAPI 为前置；生产镜像 `_existing_prod/gainode_h5`、`_existing_prod/gainode_admin` 只读。所有页面以 03/04 Page ID、08、`design-system/12_FIGMA_FRONTEND_DEVELOPMENT_BASELINE_V1.0.md` 和 Gainode2.0 Figma 为基线；旧前端只能作为复用证据，不能反推业务。每页使用统一 `PAGE_IMPLEMENTATION_RECORD`：Page ID、route、Figma node、DTO/API、store、components/tokens、五态、写操作状态、权限、I18N keys、375/390/430 或 Admin 1280/1440/1920 截图、tests、Known Deviation。

**显式 Page ID 注册表（范围写法不得替代本清单）**：

```text
H5_P0 = M-AUTH-001,M-AUTH-002,M-AUTH-003,M-AUTH-004,M-AUTH-005,
         M-KYC-001,M-KYC-002,M-KYC-003,M-HOME-001,M-NOTICE-001,
         M-ROBOT-001,M-ROBOT-002,M-ROBOT-003,M-ROBOT-004,M-ROBOT-005,M-ROBOT-006,M-ROBOT-007,
         M-PREDICT-001,M-PREDICT-002,M-PREDICT-003,M-PREDICT-004,M-PREDICT-005,M-PREDICT-006,
         M-ME-001,M-ASSET-001,M-ASSET-002,M-ASSET-003,M-POWER-001,
         M-OTC-001,M-OTC-002,M-OTC-003,M-OTC-004,M-OTC-005,M-OTC-006,
         M-SEC-001,M-SEC-002,M-SUPPORT-001,M-SUPPORT-002,M-SUPPORT-003,M-SETTINGS-001
H5_P1 = M-AI-001,M-GROWTH-001,M-PREDICT-FREE-001
H5_FUTURE_CLOSED = M-MIGRATION-001

ADMIN_P0 = A-WORK-001,A-WORK-002,A-USER-001,A-USER-002,A-KYC-001,
           A-LEDGER-001,A-LEDGER-002,A-LEDGER-003,A-LEDGER-004,
           A-ROBOT-001,A-ROBOT-002,A-ROBOT-003,A-OTC-001,A-OTC-002,A-POWER-001,
           A-PREDICT-001,A-PREDICT-002,A-PREDICT-003,A-PREDICT-004,
           A-RISK-001,A-APPROVAL-001,A-CONFIG-001,A-CONFIG-002,A-POLICY-001,
           A-SUPPORT-001,A-SUPPORT-002,A-AUDIT-001,A-OPS-001,A-EMERGENCY-001
ADMIN_P1_CONDITIONAL = A-USER-004
ADMIN_P1 = A-REPORT-001,A-GROWTH-001
ADMIN_FUTURE_CLOSED = A-MIGRATION-001
```

### S03-P00 · 前端开发目录冻结

**目标文件**：`.project-ai/decisions/FRONTEND_TARGET_ROOT_DECISION.md`、manifest/context/bootstrap 路径指针、`FRONTEND_BASELINE_INVENTORY.md`；不修改 `_existing_prod/**`。

**必须冻结字段**：

```text
H5_SOURCE_ROOT = E:\github\sports\_existing_prod\gainode_h5
ADMIN_SOURCE_ROOT = E:\github\sports\_existing_prod\gainode_admin
H5_TARGET_ROOT = <owner-approved path>
ADMIN_TARGET_ROOT = <owner-approved path>
SOURCE_BASELINE_COMMIT = <verified git/tree hash>
MIGRATION_MODE = INCREMENTAL
PACKAGE_MANAGER_H5 = <locked from target>
PACKAGE_MANAGER_ADMIN = pnpm@8.14.0 or approved replacement
NODE_VERSION = <locked compatible version>
TARGET_ROOT_WRITE_POLICY = DEVELOPMENT_ONLY
```

**固定步骤**：

1. 对两个 source root 只读盘点 package/scripts、routes、stores、API、i18n、components、views、assets、secret 风险和链上依赖，生成 KEEP/REPLACE/REMOVE_LATER/DO_NOT_COPY。
2. 验证 source baseline Git tree/hash；若 `_existing_prod` 非 Git 对象，生成逐文件 SHA-256 manifest，不把时间戳当版本。
3. 固定给 Owner 两个路径方案：A=在批准的新 V2 目录增量复制并保留 source 只读；B=批准的现有正式目录原位升级。默认推荐 A，但 Agent 不得自行签字。
4. Owner 选择后写入四个项目指针和写入边界；检查 target 不在 `_existing_prod`、不在其他仓库、不是 source 的子目录循环。
5. 只复制构建最小集并保留 Git 历史/来源清单；不复制 node_modules、dist、.env、secret、mock production data。
6. 在 target 执行原始 install/build/typecheck 作为迁移前基线，记录现有失败，不在 P00 顺手修页面。

**验证**：路径 canonicalize/containment；source SHA manifest；target/source 文件清单；package lock一致性；baseline build/typecheck；secret scan；`git diff --check`。

**停止条件**：Owner 未冻结 target roots；target 与 source/其他项目重叠；基线无法追溯。只输出 Decision/风险，不复制或移动文件。

**验收**：四路径/版本字段齐全；source 仍只读；迁移模式和允许写路径明确；P01 不需要自行选择目录。

### S03-P01 · H5 基础设施

**前置**：`H5_TARGET_ROOT` 已冻结。**目标文件模式**：`package.json/lockfile`、`src/{api,stores,i18n,router,tokens,components,utils}/`、`vite.config.ts`、`vitest.config.*`、`playwright.config.*`、`tests/{unit,e2e,visual}/`。

**固定步骤**：

1. 先运行 baseline build/typecheck；建立 migration checklist，保留 Vue 3+TypeScript 和可复用 route/view/component，不全量重写。
2. 按 manifest 批准 Pinia+persistedstate、vue-i18n、Vant 4、Axios、Vitest、Playwright；现有依赖不满足时先提交 Dependency Decision，不静默升级所有包。
3. 建立 `src/api/http.ts` 和 domain clients：注入六请求头、auth refresh single-flight、request_id、Idempotency-Key、If-Match；超时写请求进入 RESULT_UNKNOWN 并按原对象查询，不自动重复 POST。
4. 建立 Pinia stores：session、entitlement、robot、prediction、asset/power/otc、notice；持久化只存 token 引用/安全偏好，不持久化 secret 或权威余额。
5. 建立 router meta：pageId/auth/restricted/feature；深链无权限安全降级，语言切换保留 route/object/tab/filter/form draft。
6. 将 08 与 Figma tokens 映射到 `src/tokens/{color,spacing,typography,radius,elevation}.ts/css`；业务页面禁止散落硬编码品牌色。
7. 导入 7 语言资源和 ui-copy-manifest；建立 key parity、one-locale-one-language、hardcoded-copy 和敏感文案 PENDING_HUMAN_REVIEW 检查。
8. 把硬编码 AES/sign/S3 key 移到后端/环境；上传改 presigned URL；删除链上依赖只能在引用清单为 0 且单独提交时进行。
9. 配置 unit/component/E2E/375-390-430 visual 基线，并先为 ApiErrorBoundary、FiveStateContainer、RestrictedState、UnknownResult 写测试。

**验证命令**：使用锁定包管理器执行 install frozen-lockfile、`type-check`、`build`、unit、Playwright smoke、i18n parity、hardcoded secret/copy scan；脚本缺失由本包新增后运行。

**停止条件**：target 未冻结；依赖未经批准；OpenAPI 未 APPROVED；Figma token冲突。只停止受影响项并生成 Decision/Deviation。

**验收**：基础设施可在无页面业务逻辑下 build/test；六请求头/RESULT_UNKNOWN/7语言/tokens/五态组件有自动测试；无生产 secret。

### S03-P02 · H5 页面实现顺序

**每页固定文件**：`src/views/<domain>/<page-id-lower>/index.vue`、route meta、domain API/DTO、Pinia selector/action、i18n keys、`tests/unit/<page-id>.spec.ts`、`tests/e2e/<flow>.spec.ts`、`tests/visual/<page-id>/{375,390,430}.png`。如果复用旧 view，仍需在 Page ID registry 映射真实路径，禁止复制两份页面。

**每页固定步骤**：①抄录 03 的目标/数据/动作/状态/限制；②绑定 OpenAPI DTO，不手写第二套字段；③先完成 Default/Loading/Empty/Error/Restricted；④实现 Confirm/Submitting/Processing/Success/Failed/State Changed/Unknown（适用时）；⑤接入 allowed_actions，按钮不可由本地金额/等级推导；⑥接入 7 语言/tokens/Figma node；⑦写 unit/E2E/visual；⑧生成 PAGE_IMPLEMENTATION_RECORD 后提审。

**批次与明确对象**：

| 批次 | Page ID | API/状态重点 | 批次验收 |
|---|---|---|---|
| H5-01 Auth | `M-AUTH-001..005` | login/register/OTP/reset/MFA；频控、session、账号不存在性安全 | OTP resend、MFA、expired session、网络失败 E2E |
| H5-02 KYC/Notice | `M-KYC-001..003`,`M-NOTICE-001` | KycCase/FeatureEntitlement/Notice；needs_info/restricted/deep link | 补件、无权限关联对象、通知正文安全 |
| H5-03 Home | `M-HOME-001` | entitlement/Robot/Prediction/Notice 聚合；局部 freshness | 任一模块失败不显示 0、不拖垮全页 |
| H5-04 Robot | `M-ROBOT-001..007` | Robot/UpgradeOrder/Reward/Power preview；56级和三状态轴 | start/upgrade/claim quote、unknown/result恢复 |
| H5-05 Prediction | `M-PREDICT-001..006` | Market/Consent/Order/Result/Settlement/Refund/Correction | lock race、official≠paid、异常/退款/更正时间线 |
| H5-06 Asset/Power | `M-ASSET-001..003`,`M-POWER-001` | AptAccount/Ledger/PowerPosition；decimal string/freshness | 流水关联对象、可用/冻结/待确认分离 |
| H5-07 OTC | `M-OTC-001..006` | eligibility/capacity/quote/order/trade/power | partial/cancel/expire/dispute、Sell Power 守恒展示 |
| H5-08 Me/Security | `M-ME-001`,`M-SEC-001..002`,`M-SETTINGS-001` | profile/session/device/MFA/locale | revoke、语言切换保留上下文、无 secret 缓存 |
| H5-09 Support | `M-SUPPORT-001..003` | Ticket/Message/Attachment/presigned | 创建、上传、状态更新、restricted仍可申诉 |
| H5-10 P1 | `M-AI-001`,`M-GROWTH-001`,`M-PREDICT-FREE-001` | 仅在各 P1 合同/Feature Gate APPROVED 后；AI 不泄露套利细节 | 未开放时 Closed/Restricted，不伪造 API |
| H5-11 Future | `M-MIGRATION-001` | Future/CLOSED | 只显示关闭说明，无创建/钱包/链上操作 |

**停止条件**：某 Page API/DTO/Feature Gate 未冻结；Figma node 缺失；敏感文案未签。该页保持 Contract Gap/Closed/PENDING，不阻止无依赖批次。

**验证**：逐页 unit/E2E/visual、Page ID route完整性、OpenAPI DTO、五态、7语言、三尺寸和前端无权威推导检查。

**验收**：全部 P0 Page ID 有 route、五态、API/DTO/store、7语言、三尺寸截图和测试；P1/Future 不越权开放；所有写操作可从 request_id 查询终态。

### S03-P03 · Admin 基础设施与逐页实现

**目标文件模式**：`{ADMIN_TARGET_ROOT}/src/views/<nav>/<page-id-lower>/index.vue`、`src/api/module/<domain>.ts`、`src/store/modules/<domain>.ts`、`src/router/module/<nav>.ts`、Schema adapters、i18n、`tests/{unit,e2e,visual}/`。保留 Schema 驱动、Pinia、多 Tab、面包屑、RBAC、v-permission 和保存视图；Layui→Element Plus 采用 adapter→逐组件替换→删除零引用旧依赖，不改变业务合同。

**Admin 基础设施步骤**：

1. 建旧组件/API/store/route复用矩阵；先实现 Element Plus Schema adapter、Table/Form/Drawer/Approval/ImpactPreview/AuditLink 公共组件。
2. 统一 Admin API client、request context、RESULT_UNKNOWN、导出任务轮询、字段/数据范围权限和 object_version 冲突提示。
3. 建 8 个导航 route registry 和 Page ID meta；菜单由 RBAC 过滤但直接 URL 仍需服务端授权。
4. 建 `AdminFiveState`：Default/Loading/Empty/Error/No Permission/Dependency Unavailable；写页再加 Invalid/Confirm/Submitting/Processing/Success/Failed/State Changed。
5. 建 1280/1440/1920 visual、键盘焦点、table density、480/640 Drawer 基线；不得做黑色收益大屏。

**逐导航批次（Quality 每批单独快照）**：

| 批次 | Page ID | 固定实现重点 |
|---|---|---|
| ADM-01 工作台 | `A-WORK-001`,`A-WORK-002` | 指标口径深链、局部失败、SLA/领取/转派 CAS；待办不复制业务编辑页 |
| ADM-02 用户准入 | `A-USER-001`,`A-USER-002`,`A-KYC-001` | 字段脱敏、User360 九 Tab、KYC decision/reason/evidence/SoD |
| ADM-02B 条件页 | `A-USER-004` | P1_CONDITIONAL/CONTRACT_GAP；只有 Impact Preview，执行按钮禁用 |
| ADM-03 资产账本 | `A-LEDGER-001..004` | account/ledger/pool/reconciliation/reversal；影响预览、Approval、Audit ID；禁止直接改历史 |
| ADM-04 Robot | `A-ROBOT-001..003` | Robot状态/等级/规则快照、Reward/Claim异常；正式参数只读 |
| ADM-05 OTC/Power | `A-OTC-001..002`,`A-POWER-001` | eligibility/order/trade/partial/dispute、Power freeze/consume/release |
| ADM-06 Prediction | `A-PREDICT-001..004` | Market/Result/Settlement/Refund/Correction 三状态轴、source evidence、batch retry、reversal |
| ADM-07 风控治理 | `A-RISK-001`,`A-APPROVAL-001`,`A-CONFIG-001..002`,`A-POLICY-001` | SoD、Candidate/Release/Snapshot、restricted actions、策略 preview |
| ADM-08 客服审计运维 | `A-SUPPORT-001..002`,`A-AUDIT-001`,`A-OPS-001` | 工单/附件、append-only audit、async/reconciliation/system status |
| ADM-08B P1 | `A-REPORT-001`,`A-GROWTH-001` | 对应合同 Gate 后实现；报表口径可追溯，Growth 不动 P0 资金 |
| ADM-08C Future | `A-MIGRATION-001` | Future/CLOSED，只显示 Gate/预留信息，不出现执行控件 |
| ADM-08D Emergency | `A-EMERGENCY-001` | 仅已签 override contract；MFA/case/reason/evidence/expiry/post-review，缺一禁用 |

**每页固定步骤**：从 04 抄录页面目标/读取数据/接口/动作/状态/权限/验收→绑定 OpenAPI/Admin DTO→实现列表/详情/Drawer/Confirm→接 allowed_actions/SoD/object_version→接 I18N/Figma tokens→unit/E2E/visual→PAGE_IMPLEMENTATION_RECORD→Quality。

**高风险统一验收**：资产调整、账本更正、赛果、结算、退款、冲正、参数发布和紧急操作必须展示来源对象、before/after impact、reason、evidence、approval actor、执行终态、request_id 和 Audit ID；状态变化时不允许用旧表单重复提交。

**验证命令**：锁定包管理器的 typecheck/build/unit/E2E/visual；Page ID route completeness；RBAC/field/data-scope negative tests；i18n parity/hardcoded copy；Element/Layui reference inventory；secret scan。

**停止条件**：页面 Contract Gap、OpenAPI 未冻结、Element 迁移需未批准依赖、紧急合同未签。页面显示 Preview/Closed/Dependency Unavailable，其他批次继续。

**验收**：33 个 Admin Page ID 均在 registry；所有 P0 页有状态/权限/API/tests/视觉证据；P1/Conditional/Future 边界准确；8 导航顺序和 04 一致。

### S03-P04 · 前端 Gate

**目标文件**：H5/Admin Page Coverage、API coverage、i18n/visual/accessibility evidence、`.project-ai/reviews/STAGE-03-QUALITY-GATE.md`。

**固定步骤**：

1. 机械比对 03/04 Page ID 与 route/view/test/visual record，P0 漏页=阻塞。
2. 执行 H5/Admin frozen-lock install、typecheck、build、unit、E2E；检查无测试脚本不得写 PASS。
3. 比对 OpenAPI operation 与前端 client/DTO；扫描前端金额/资格/Power/Reward 本地推导和 float。
4. 比对 7 语言 key 集、terminology lock、one-locale-one-language、敏感文案签核状态和长文本布局。
5. 执行 H5 375/390/430、Admin 1280/1440/1920 视觉回归和键盘/焦点/对比度/减少动效检查。
6. 执行五态、Unknown Result、State Changed、deep link无权限、token refresh、断网重连和写请求超时恢复 E2E。
7. 核对所有批次 Snapshot/Quality/Finding closure，Quality 独立输出 Stage Gate。

**验证**：执行步骤 1–7 并保存 H5/Admin build、typecheck、unit、E2E、i18n、visual、accessibility 与 API coverage 原始证据。

**停止条件**：P0 页面/状态/API/语言缺失；未批准视觉偏差；C端暴露内部套利/生产 mock/secret；P0/P1/blocking P2 未关闭。

**验收矩阵**：P0 Page/route=100%；7语言 key parity=100%；三尺寸证据=100%；前端权威业务推导=0；build/typecheck/tests通过；Gate APPROVED 后进入 STAGE-04。

---

## 9. STAGE-04 · Flutter App

**阶段边界**：当前没有 Flutter 工程，S04-P00 是强制架构 Decision Gate。App 复用同一 OpenAPI、Page ID、Figma、I18N 和状态语义，不建立“App 专用业务规则”。平台差异仅限 safe area、键盘、系统返回、通知权限、生物识别和设备能力。所有金额/数量使用 String+批准的 decimal value object，禁止 `double`。

### S04-P00 · Flutter 工程决策冻结

**目标文件**：`.project-ai/decisions/FLUTTER_ENGINEERING_DECISION.md`、manifest/context/bootstrap Flutter 指针；在决定前不得运行 `flutter create`。

**必须裁决字段**：

```text
FLUTTER_TARGET_ROOT = <owner-approved path inside E:\github\sports>
APPLICATION_ID_IOS = <owner-approved>
APPLICATION_ID_ANDROID = <owner-approved>
MINIMUM_IOS = <owner-approved>
MINIMUM_ANDROID_SDK = <owner-approved>
EXACT_FLUTTER_VERSION = <pinned stable version>
DART_VERSION = <derived from pinned Flutter>
STATE_MANAGEMENT = RIVERPOD | BLOC
ROUTING = GO_ROUTER | APPROVED_EQUIVALENT
SECURE_STORAGE = FLUTTER_SECURE_STORAGE | APPROVED_EQUIVALENT
HTTP_CLIENT = DIO | APPROVED_EQUIVALENT
DECIMAL_LIBRARY = <approved package>
FLAVOR_SET = dev,test,sandbox
PRODUCTION_FLAVOR = SCAFFOLD_ONLY_NO_REAL_VALUES
```

**固定步骤**：

1. 检查仓库内无现有 Flutter 工程/重复 application ID，记录可复用 Logo/font/i18n/Figma assets。
2. 为 state management、routing、secure storage、HTTP、decimal 各给固定 A/B 选项，比较维护性、测试、deep link、安全和依赖许可；推荐项可写但 Agent 不得代签。
3. 校验 target path 在 sports 内且不覆盖 H5/Admin/backend/source mirror；定义允许写路径和平台目录。
4. 明确 flavor、bundle/application IDs、minimum OS、签名边界、CI runner 和 iOS 必须在 macOS 构建；不生成真实证书/keystore。
5. Owner 签署后更新 manifest；Dependency Decision 与锁定版本一起进入审核，未签字段不得默认选择。

**验证/交付**：路径 containment、application ID 格式/冲突、Flutter/Dart兼容矩阵、依赖许可证/维护性、安全存储 threat model、Decision 审核报告。

**停止条件**：任一必填字段或依赖未批准；target 与其他目录重叠；生产签名值被要求写入仓库。

**验收**：P01 可直接按已签字段执行，不需要选择框架/目录/版本；production 仍无真实值。

### S04-P01 · 工程基础设施

**目标结构**：

```text
{FLUTTER_TARGET_ROOT}/lib/app/{app,router,guards,flavors}.dart
{FLUTTER_TARGET_ROOT}/lib/core/{api,auth,errors,decimal,i18n,storage,theme,widgets}/
{FLUTTER_TARGET_ROOT}/lib/features/<domain>/{data,domain,presentation}/
{FLUTTER_TARGET_ROOT}/assets/{fonts,icons,images}/
{FLUTTER_TARGET_ROOT}/test/{unit,widget,golden}/
{FLUTTER_TARGET_ROOT}/integration_test/
```

**固定步骤**：

1. 用锁定 Flutter 版本在批准 root 创建工程；只建 dev/test/sandbox flavor，production 仅空骨架且无 URL/key/signing value。
2. 配置 analysis_options、format、lockfile、flavor entrypoints 和 environment loader；运行空工程 Android build，iOS 构建条件记录给 macOS runner。
3. 从 08/Figma 建 theme/tokens、dark theme、字体、图标、Logo 和 FiveState/Restricted/UnknownResult/StatusBadge/DecimalText 公共 widget。
4. 将 7 语言 JSON/ui-copy-manifest 转为 ARB 或批准流程，生成 localization；建立 key parity、术语锁和长文本 golden。
5. 建 API client：六请求头、request_id、auth refresh single-flight、Idempotency-Key、If-Match、统一错误、RESULT_UNKNOWN 原对象查询；禁止失败 POST 自动重试。
6. 建 secure session storage；access/refresh token、MFA 和设备标识不进普通 preferences/log/screenshot。
7. 建 DecimalValue，parse/format/compare/rounding 全部从 string；业务 DTO 禁止 double。
8. 建 router/session/restricted/deep-link guards；无权限深链只显示安全页面，不泄露对象存在性。
9. 建 fake API 仅供 tests，fixture 与 production client编译边界分离；配置 unit/widget/integration/golden 和 CI 命令。

**验证命令**：`flutter doctor -v`（记录环境，不把缺 iOS runner当代码失败）、`dart format --output=none --set-exit-if-changed .`、`flutter analyze`、`flutter test`、sandbox Android build、secret scan、dependency/license inventory。

**停止条件**：依赖/版本漂移；OpenAPI/Figma/i18n 输入不完整；需要真实签名或生产 URL。分别锁定依赖、报告输入缺口、保持 production 空值。

**验收**：空功能工程可 analyze/test/build；核心 API/decimal/storage/router/i18n/theme 有测试；页面包不需要重新设计基础设施。

### S04-P02 · Auth / KYC / Notice

**Page ID**：`M-AUTH-001..005`、`M-KYC-001..003`、`M-NOTICE-001`。**feature roots**：`features/{auth,kyc,notice}/`。

**固定步骤**：

1. 从 OpenAPI 生成或手写一次 DTO/mapper/repository interface，字段与 nullable/enum 完全一致；不得在 widget 解析 raw JSON。
2. Auth store/controller 实现 login/register/OTP/reset/MFA/session refresh；OTP resend cooldown来自服务端，MFA secret 不持久化到普通 storage。
3. KYC 实现 overview→submit/attachment→needs_info/resubmit→status；entitlement/restricted reason 与 KycCase 状态分开。
4. Notice 实现 list/read/deep link；关联对象无权限时正文安全显示、目标数据不加载。
5. 按 Page ID 建 screen/widget/route，逐页完成五态和 Confirm/Submitting/Unknown（适用）；接 Figma tokens/7语言。
6. 写 repository unit、state controller unit、widget/golden、Auth/KYC/Notice integration flow。

**验证场景**：OTP resend/expired、账号不存在性安全、MFA challenge/replay、session expired/refresh race、KYC needs_info/restricted、upload failure、notice deep link无权限、断网恢复。

**停止条件**：KYC/OTP/MFA 合同或敏感文案未批准。页面保持 Dependency Unavailable/PENDING，不写默认流程。

**验收**：9 Page ID 全覆盖；token/PII 无泄漏；Restricted≠Error；iOS/Android golden 与 H5语义一致。

### S04-P03 · Home / Robot

**Page ID**：`M-HOME-001`、`M-ROBOT-001..007`。**feature roots**：`features/{home,robot,reward,power}/`。

**目标文件**：上述 feature roots 的 data/domain/presentation、对应 routes、tests 和 golden；Page ID 到文件的映射写入 Flutter Page Registry。

**固定步骤**：

1. 实现 Home 聚合 repository，把 entitlement/Robot/Prediction/Notice 分块；局部失败保留其他块，不把 unavailable 显示为 0。
2. 实现 Robot/Rule/UpgradeOrder/Reward/PowerImpact DTO 和 repository；level/capacity/cap/reward/allowed_actions 全部服务端下发。
3. Robot controller 分开维护 robot state、upgrade action、claim action 和 records，不用一个 loading 覆盖整页。
4. Start/Stop 与 Upgrade 先请求 quote/PowerImpactPreview，Confirm 显示 snapshot/version/expiry；提交后 processing 轮询原 action ID。
5. Reward Claim 使用 Idempotency-Key；超时进入 Unknown Result 并查询 claim，不再次创建。
6. 逐页实现 Figma、五态、56级地图、timeline/records 和 7语言；不得显示固定收益/套利仓位。
7. 写 golden/widget/integration：并发点击、quote过期、Power不足、参数变化、claim超时、Reward expiry/reversal。

**验证**：Home partial failure、Robot start/stop、upgrade quote/state changed、double claim、Unknown recovery、decimal/no-double scan、internal arbitrage copy scan。

**停止条件**：Active Release/Power preview/Reward合同不可用；保持 Action Closed，读页可显示 unavailable reason。

**验收**：8 Page ID、四动作状态轴和测试完整；任何金额/资格/Power不由 App 推导。

### S04-P04 · Prediction

**Page ID**：`M-PREDICT-001..006`。**feature root**：`features/prediction/`，DTO 分 `Market/Disclosure/Consent/PredictionOrder/Result/Settlement/Refund/Correction`。

**目标文件**：Prediction feature 的 DTO/repository/controller/screens/widgets、routes、unit/widget/integration/golden tests。

**固定步骤**：

1. Market list/detail 使用 server filters/cursor/freshness；Locked/Exception/Unavailable 不允许进入提交。
2. Detail 加载 disclosure version；Confirm 前创建/校验 ConsentReceipt，显示 1X2、stake、rule/snapshot/lock time，不显示博彩盘口或固定收益叙事。
3. Submit 使用 Idempotency-Key 和 object version；超时查询 order receipt，State Changed 回详情重新确认。
4. My Orders/Detail 分开渲染 Market、Order、Result、Settlement 状态；official 绝不映射 paid。
5. Exception 页渲染 disputed/refund/correction timeline、原/反向/新 ledger refs 和安全 reason；不允许本地计算退款。
6. 写 unit/widget/golden/integration：market lock race、same-selection addition、opposite change禁止、source conflict、settlement delay、refund/correction。

**验证**：6 Page ID五态；consent version；double submit/Unknown；official≠paid断言；三状态轴映射；decimal/string；7语言/三尺寸 golden。

**停止条件**：正式 disclosure/锁盘/退款更正合同未冻结；对应 action disabled，历史与Support入口保留。

**验收**：完整 market→consent→confirm→order→settlement→exception闭环；无 App 侧结算或资格推导。

### S04-P05 · APT / Power / OTC

**Page ID**：`M-ASSET-001..003`、`M-POWER-001`、`M-OTC-001..006`。**feature roots**：`features/{asset,power,otc}/`。

**目标文件**：三个 feature roots 的 data/domain/presentation、routes、共享 decimal/power widgets、unit/widget/integration/golden tests。

**固定步骤**：

1. AptAccount/Ledger DTO 保留 available/frozen/pending/held/payable/claimed/burned、reference valuation状态和 freshness；所有数值 string。
2. Asset pages 实现 summary→ledger list→entry detail，关联对象深链和无权限安全降级；不展示币价炒作/刚兑。
3. Power page 分 available/frozen/consumed/released/recovering/cap，服务端无值显示 unavailable，不估算恢复量。
4. OTC market 先取 eligibility/capacity；输入页只接受服务端 min/max/fee/allowed_actions，quote 显示 expiry和PowerImpact。
5. Confirm/Create 使用 quote/version/idempotency；结果页处理 accepted/processing/unknown/failed；订单列表/详情分别渲染 partial/completed/cancelled/expired/disputed。
6. Partial progress 显示 filled/remaining、Power consumed/frozen；cancel只提交 remaining，不在 App 修改本地余额。
7. 写 golden/integration：quote expiry、balance changed、double submit、partial→cancel、fill/cancel race、expired/release、buy/sell差异、dispute。

**验证**：10 Page ID；decimal/no-double；Power守恒展示；OTC状态映射；Unknown recovery；Restricted/No Active Release；i18n/golden。

**停止条件**：OTC/Power Active Parameter或资格服务不可用；create action closed，历史和Support仍可访问。

**验收**：APT/Power/OTC 三域不混账；partial/cancel/expire/dispute准确；App不保存权威余额。

### S04-P06 · Me / Security / Support / Settings

**Page ID**：`M-ME-001`、`M-SEC-001..002`、`M-SUPPORT-001..003`、`M-SETTINGS-001`。**feature roots**：`features/{profile,security,support,settings}/`。

**固定步骤**：

1. Me 聚合 profile/KYC/security/support入口，字段脱敏；无权限模块不显示敏感摘要。
2. Security 实现 MFA状态、SessionDevice list、revoke one/logout all；当前 session 与目标 session ID 明确，State Changed 后刷新。
3. Support 实现 Ticket list/create/detail、Message timeline、Attachment presigned upload；本地只保留上传临时状态，不保存永久云凭证。
4. Restricted 用户仍可访问历史、退款解释和工单；Support reason不泄露内部风险规则。
5. Settings 实现7语言/主题/通知偏好；语言切换保留 route/object/tab/filter/form draft，高风险 Confirm用新语言重绘但不改原 request。
6. 写 unit/widget/golden/integration：revoke race、current session、upload过期/失败、ticket retry、restricted support、locale切换和长文本。

**验证**：7 Page ID；secure storage/log/screenshot scan；session权限；presigned upload；i18n parity；golden；accessibility。

**停止条件**：Session/attachment/notification preference API 未冻结；显示 Dependency Unavailable，不接直连 S3 或本地假成功。

**验收**：安全和工单闭环可恢复；无 token/PII/云密钥泄漏；7语言切换一致。

### S04-P07 · Flutter Gate

**目标文件**：Flutter Page/API/test/golden coverage、Android/iOS Sandbox build evidence、`.project-ai/reviews/STAGE-04-QUALITY-GATE.md`。

**固定步骤**：

1. 比对全部 P0 Mobile Page ID 与 Flutter route/screen/test/golden；P1/Future 单独标 Gate/Closed。
2. 运行 `dart format --output=none --set-exit-if-changed .`、`flutter analyze`、全部 unit/widget/golden/integration tests。
3. 在支持环境构建 Android sandbox；iOS sandbox 必须在 macOS runner `--no-codesign` 或批准 CI 构建，Windows 未执行不得写 PASS。
4. 比对 Flutter/H5 OpenAPI DTO、状态映射、7语言 keys、Figma/token、allowed_actions 和 Unknown恢复。
5. 扫描 double、硬编码金额/资格/文案、secret/PII、内部套利字段、fixture进入production flavor。
6. 执行冷启动、后台恢复、token refresh、断网重连、deep link无权限、键盘/safe area/系统返回和减少动效测试。
7. 核对 P00..P06 Quality/Finding closure，独立输出 Stage Gate。

**验证**：执行步骤 1–7 的 format/analyze/test/build/Page/API/i18n/Figma/安全扫描并保存原始退出码与平台证据。

**停止条件**：P0 Page/API/state/golden缺失；平台 build失败；App/H5语义漂移；P0/P1/blocking P2未关闭。

**验收矩阵**：P0 Page覆盖=100%；analyze/format/tests通过；Android+iOS Sandbox证据齐全；API/state/i18n/Figma跨端一致；Production signing/config仍未授权。

---

## 10. STAGE-05 · Sandbox E2E 与迁移演练

**阶段边界**：只允许隔离 Sandbox、合成/脱敏 fixture 和非生产 secret。固定后端、H5、Admin、Flutter 四端 revision 后再执行；任何测试写入生产域名、生产数据库、真实消息渠道或真实价值网络立即停止。

### S05-P01 · Sandbox 环境与确定性 Fixture

**目标文件**：`sandbox/environment-manifest.yaml`、`sandbox/fixtures/**`、`sandbox/scripts/{init,reset,seed,verify,cleanup}.*`、`tests/e2e/fixtures/**`、`SANDBOX_RUNBOOK.md`。

**固定步骤**：

1. 记录四端 commit、OpenAPI hash、DDL version、Parameter Release fixture version、镜像/运行时/lockfile hash；禁止使用 floating latest。
2. 建独立数据库/Redis/队列/对象存储/邮件短信 stub 配置，变量名与 production 分离，网络 ACL 阻止生产地址。
3. 生成可重复 seed：13角色、普通/受限用户、KYC全状态、MFA/session、Robot 1/边界/56级、APT/Power、Market/Order、OTC、Approval/Parameter/Risk/Ticket/Notice。
4. 每个 fixture 使用稳定 external key，不依赖自增顺序；金额/时间/seed/时区固定，初始化两次结果 hash 相同。
5. 建 API-Football/内部信号/通知/上传 stub 和故障注入开关；开关只在 sandbox 编译/环境可用。
6. 实现 reset 前环境身份二次校验，删除只限 Sandbox schema/bucket/queue；记录 init/reset/verify/cleanup 命令和预期输出。

**验证**：环境 URL/DB identity guard；seed两次hash一致；production endpoint scan；PII/secret scan；reset containment；fixture referential integrity；四端 health check。

**停止条件**：环境身份不清、发现真实 PII/secret/production endpoint、fixture 不可重放。不得继续 E2E。

**验收**：全环境可一键初始化/验证/重置；两次 seed 一致；故障注入可控且不会进入 production build。

### S05-P02 · 五条主流程 E2E

**目标文件**：`tests/e2e/flows/FLOW-{USER,ROBOT,PREDICTION,OTC,SUPPORT}.*`、每次 run 的 API/UI/Ledger/Audit evidence manifest。

**固定步骤**：reset→seed→记录初态→通过真实 Sandbox API/UI执行→记录 request/idempotency/object IDs→查询权威终态→运行 ledger/power/audit invariants→保存截图/日志/hash→再次执行关键请求验证幂等。

| Flow | 固定业务顺序 | 必验结果 |
|---|---|---|
| USER | 注册→OTP→MFA→KYC submit/needs_info/approve→entitlement | session rotation、KYC/资格分离、无账号泄漏 |
| ROBOT | start quote/confirm→upgrade quote/confirm→reward held/pending→claim | Power/upgrade/reward三轴、snapshot、ledger/audit |
| PREDICTION | market→disclosure/consent→order→official result→settlement | official≠paid、stake/payout守恒、receipt可追溯 |
| OTC | eligibility→quote→sell→partial→complete/cancel/expire | filled/remaining、Power freeze/consume/release守恒 |
| SUPPORT | 业务异常→notice→ticket/message/attachment→Admin处理→audit | 通知失败不回滚、restricted可申诉、字段脱敏 |

**验证**：每 Flow 在 H5、Admin、App相关页面核对同一对象状态；API response/OpenAPI schema；数据库只读 invariant query；Outbox/Audit correlation。

**停止条件**：任一 Flow 需手工改数据库才能继续、使用 mock 假成功、权威终态无法查询。返回责任 Stage 修复。

**验收**：五条 Flow 可从空 Sandbox重复执行；无重复经济效果；证据包含 UI/API/Ledger/Audit 四层。

### S05-P03 · 15 个故障与边界场景

**目标文件**：`tests/e2e/failures/F01..F15.*`、`sandbox/faults/<fault-id>`、`FAILURE_EVIDENCE_MATRIX.md`。每场景固定记录初态、注入点、request_id、Idempotency-Key、object_version、对象终态、Ledger/APT/Power delta、Audit ID、Outbox/通知、重试/二次执行和恢复时间。

| ID | 场景 | 固定断言 |
|---|---|---|
| F01 | 双击 Upgrade/Claim/Prediction/OTC | 一个业务对象/一次经济效果，重复返回原结果或冲突 |
| F02 | 客户端超时但服务端成功 | UI进入Unknown并查询原对象，不重复POST |
| F03 | Confirm期间Parameter Release变化 | 原snapshot继续或明确State Changed，绝不混用新旧参数 |
| F04 | Confirm期间Market Locked | 订单拒绝且不扣账，已有成功请求可查询 |
| F05 | quote后余额变化 | CAS/重新报价，禁止负余额 |
| F06 | KYC/地区资格提交瞬间受限 | 写操作拒绝，历史/退款/Support仍可访问 |
| F07 | OTC部分成交后取消remaining | 已成交不回滚，只释放remaining Power |
| F08 | Result主备源冲突 | disputed/review，无自动结算 |
| F09 | Settlement成功、通知失败 | Settlement/ledger保持成功，Delivery重试 |
| F10 | Refund某batch失败 | 成功项不重复，失败项可幂等重跑 |
| F11 | Correction重复执行 | 原posting只reversal一次，新posting只追加一次 |
| F12 | Audit/Outbox重放 | Audit不重复经济效果，Outbox dedupe有效 |
| F13 | Policy超时 | 默认deny并返回安全reason |
| F14 | 无Active Release | 真实价值写操作closed，读页显示unavailable |
| F15 | 受限用户查看历史/退款/工单 | 安全可读且不泄露内部限制规则 |

**固定步骤/验证顺序**：每场景先跑正向基线→启用单一 fault→执行一次→恢复 fault→重试/查询→运行 invariants→重置环境；禁止同时开启多个 fault 掩盖根因。

**停止条件**：无法证明注入生效、数据无法恢复、出现经济不守恒或跨场景污染。隔离环境并回责任包修复。

**验收**：F01–F15 全部有可重复自动化和证据；P0/P1=0；环境 reset 后 hash 回到基线。

### S05-P04 · 迁移 dry-run 与回滚

**目标文件**：`migration/{inventory,mapping,transform,reconcile,rollback}/**`、`V1_V2_MAPPING_MATRIX.md`、`MIGRATION_REHEARSAL_{01,02,03}.md`；不得修改历史 `sql/database.sql`，迁移 SQL/script forward-only。

**必须逐表盘点的映射组**：

| V1 范围 | V2 目标/处置 | 必须裁决 |
|---|---|---|
| `member_user`,`member_user_auth`,`member_user_oauth`,`member_user_logs` | User/AuthSession/LoginAudit/身份兼容 | ID、账号去重、密码hash、PII、session失效 |
| `member_user_kyc` | KycCase/附件引用 | 状态映射、证据/PII、拒绝原因 |
| `member_user_wallet`,`member_user_wallet_log`,`member_platform_wallet` | AptAccount+AptLedgerEntry+对账 | 余额口径、初始journal、冻结/待确认、差异拒绝 |
| `member_recharge_order`,`member_withdraw_order`,`member_transfer_order`,`member_order_record` | source refs/保留历史/明确不迁 | 终态、重复单、APT-C/链上字段处置 |
| `member_level`及实际盘点出的Mining表/记录 | Robot/RobotUpgradeOrder/历史归档 | V1 Mining与56级Robot不可自动等价，需Owner映射 |
| `member_user_team` | Referral/Agent（仅P1合同冻结后）或历史只读 | 层级、循环、解绑、earning禁止自动生成 |
| `arbitrage_*` | 内部AI Operations输入/历史归档，禁止C端 | retention、许可、secret、去重和不迁对象 |
| `sys_admin`,`sys_role`,`sys_casbin_*`,`sys_route`,`sys_menus` | Admin/RBAC/route保留升级 | 13角色、SoD、失效权限、超级管理员边界 |
| `sys_lang*`,`sys_notice*`,`sys_upload_files` | I18N/Notice/Attachment引用 | 7语言、敏感文案、URL/对象存储迁移 |
| `sys_web3_*` | `DO_NOT_MIGRATE_TO_V2_RUNTIME`/合规归档 | V2无链上能力、secret清除、保留期 |
| 其余 `sys_*` | KEEP/TRANSFORM/ARCHIVE/DROP_CANDIDATE逐表裁决 | owner、数据范围、依赖、保留期 |

**固定步骤**：

1. 对 V1 只读导出 schema、row count、PK/FK/unique、min/max/null/duplicate/orphan、金额汇总和PII分类，记录 snapshot hash。
2. 每个表/字段填 source→transform→target→default/null→reject→reconcile→rollback；无规则项不得猜值，进入 rejection/Owner Decision。
3. 建全新 Sandbox DB，恢复同一脱敏快照；执行 rehearsal 01 功能正确性，修脚本不改源快照。
4. rehearsal 02 加异常/重复/断点重跑，验证幂等、reject清单和回滚。
5. rehearsal 03 使用目标规模，测时、锁、磁盘、校验和4小时窗口假设；仍不接生产。
6. 每轮比对用户/余额/APT数量/冻结/订单终态/对象关联/Audit/不可迁清单，差异必须为0或有Owner接受记录。
7. 演练失败时恢复迁移前 DB/Redis/对象存储快照，启动旧应用健康检查；证明 rollback 后数据hash/关键查询一致。

**验证**：row-count/checksum/reconciliation SQL；ledger opening balance守恒；duplicate/orphan/reject；rerun idempotency；rollback hash/health；secret/PII；runtime/容量证据。

**停止条件**：生产连接、未脱敏样本、字段映射未签、余额差异、rollback失败。不得进入下一轮或发布 Gate。

**验收**：三次全量 dry-run+rollback均通过；映射表无空白；所有 reject可追溯；零生产写入。

### S05-P05 · Sandbox Gate

**目标文件**：`SANDBOX_EVIDENCE_INDEX.md`、五 Flow/15 Failure/三次 Migration 汇总、`.project-ai/reviews/STAGE-05-QUALITY-GATE.md`。

**固定步骤**：

1. 验证 environment/fixture hash和四端revision固定。
2. 重跑五主流程与F01–F15，汇总对象终态、ledger/power/audit/outbox invariants。
3. 执行断网重连、队列/定时任务重试、Webman进程重启、Redis恢复、stale data恢复和跨端一致性。
4. 审核三次迁移/回滚证据、mapping/reject/reconciliation和运行时间。
5. 核对全部 Package Quality/Finding closure；Quality独立给 Gate。

**停止条件**：守恒失败、跨端漂移、P0/P1/blocking P2、迁移少于3次、rollback不可证明、任何生产触碰。

**验收矩阵**：五Flow=PASS；F01–F15=PASS；三次migration+rollback=PASS；APT/Power守恒=PASS；跨端一致；Production始终未触碰。

---

## 11. STAGE-06 · 发布就绪，不等于生产部署

**阶段边界**：本阶段只产出安全、性能、可观测性、运维和恢复证据，不执行生产部署、生产 Migration、DNS/证书切换、真实消息、生产参数激活或真实价值开放。所有阈值必须引用已批准 SLO/Parameter；未批准时记录基线并把 Release Readiness 标为 Owner Condition，不由 Agent填写“行业默认值”。

### S06-P01 · 安全与依赖

**目标文件**：`docs/security/{THREAT_MODEL,DEPENDENCY_INVENTORY,LICENSE_INVENTORY,SECRET_PII_REPORT,RBAC_SOD_MATRIX,UPLOAD_SECURITY}.md`、自动扫描输出、`.project-ai/reviews/S06-P01-QUALITY-REVIEW.md`。

**固定步骤**：

1. 锁定 backend/H5/Admin/Flutter commit和lockfile hash，生成直接/传递依赖、版本、许可证、来源、运行/开发范围和已知EOL清单。
2. 使用现有工具运行 backend `composer validate --strict`、`composer audit --locked`、`composer show --locked --format=json`；H5/Admin 用锁定包管理器 audit/list；Flutter 用 `flutter pub deps --json`。网络/工具不可用标 NOT_RUN，不伪造 PASS。
3. 扫描 Git tracked files、配置、日志fixture和构建产物中的 secret/private key/token/password/AES/sign/S3/生产URL；发现值只记录路径和类型，不在报告回显完整 secret。
4. 按 User/Auth/MFA/Session/KYC/Upload/Admin/API/DataStore 更新 threat model，列资产、信任边界、攻击路径、控制、剩余风险和Owner接受项。
5. 对13角色执行页面/API/字段/数据范围正向与反向矩阵；重点验证PARAM三人分离、Risk、Finance、KYC、Support和超级管理员无旁路。
6. 重跑账号枚举、OTP/MFA频控、refresh replay、session revoke、IDOR、mass assignment、injection、XSS/CSRF（适用）、上传类型/大小/路径/恶意内容、日志脱敏和rate limit测试。
7. 紧急操作验证MFA、case_id、reason、evidence、expiry、actor-level SoD和48h post-review；合同缺失则功能必须closed。
8. 每个高危/严重依赖漏洞给 reachable/not-reachable 证据、最小升级范围和回归；禁止为清零报告批量升级无关依赖。

**验证**：依赖/许可证/secret/PII报告；安全测试；RBAC/SoD矩阵；构建产物scan；`git diff --check`。

**停止条件**：可达 critical/high 漏洞无处置、有效 secret入库、权限旁路、上传RCE/PII泄漏、必要扫描未执行且无替代证据。

**验收**：可达critical/high=0或有Owner书面接受且Release标条件；secret=0；13角色矩阵完整；安全Finding P0/P1/blocking P2=0。

### S06-P02 · 性能与可观测性

**目标文件**：`docs/operations/{SLO_BASELINE,LOAD_TEST_PLAN,METRICS_CATALOG,ALERT_MATRIX,DASHBOARD_SPEC}.md`、`tests/performance/**`、索引/慢查询报告、`.project-ai/reviews/S06-P02-QUALITY-REVIEW.md`。

**固定步骤**：

1. 从已批准SLO/Parameter建立 endpoint tier、吞吐、p50/p95/p99、error rate、timeout、queue lag、cron drift和资源上限；未批准数值标 `OWNER_SLO_DECISION_REQUIRED`。
2. 在与Sandbox Gate同revision/fixture环境建立只读、普通写、高风险写、batch/queue五类负载脚本；新增k6/JMeter等工具必须先过Dependency Decision。
3. 先单用户正确性→阶梯负载→稳态→短峰值→恢复，期间不混入故障注入；记录客户端/服务端请求数和数据守恒。
4. 检查MySQL EXPLAIN/索引/慢查询、连接池和锁等待；Redis hit/eviction/memory；queue depth/age/retry/dead-letter；Webman worker memory/restart。
5. 为 Ledger/Reward/OTC/Prediction/Settlement/Refund/Correction/Outbox/Audit 定义业务计数器、延迟、失败、重试和不变量告警，标签禁止高基数PII。
6. 为外部数据源定义timeout/retry/circuit/stale/source_status/fail-closed指标；模拟不可用并验证恢复后不重复经济写入。
7. 将每条告警映射owner、severity、threshold source、runbook和抑制/去重；没有runbook的告警不得标完成。
8. 修复只针对有证据瓶颈；索引变更走dated SQL/Change Request，优化后重跑相同fixture和脚本。

**验证**：负载脚本可重复；SLO结果；EXPLAIN/slow query；Redis/queue/worker；业务不变量；故障恢复；metrics label/PII scan。

**停止条件**：无批准SLO无法给PASS；负载导致守恒失败；观测缺失无法定位；需要生产压测。保持Sandbox、提交Owner/SLO Decision。

**验收**：批准SLO全部达标或明确FAIL；核心业务指标/告警/runbook三方映射100%；负载后账本/Power/Outbox守恒。

### S06-P03 · 运维与恢复材料

**目标文件**：`docs/runbooks/{ENVIRONMENT,DEPLOYMENT,MIGRATION,ROLLBACK,INCIDENT,BACKUP_RESTORE,QUEUE_REPLAY,PARAMETER_ROLLBACK,DEGRADED_MODE,RECONCILIATION,EXTERNAL_SOURCE}.md`、值班/Owner矩阵、演练证据。

**固定步骤**：

1. Environment Runbook 列每变量owner/scope/secret source/required/safe default/validation，禁止记录真实值。
2. Deployment Runbook 只写可逆步骤、artifact hash、precheck、health、smoke、abort point和审批；生产命令标 `NOT_EXECUTED_REQUIRES_OWNER`。
3. Migration/Rollback引用S05三次演练，列快照、停写、校验、拒绝、cutover、回退判据和4小时假设；不得复制一套不同脚本。
4. Backup/Restore在Sandbox执行数据库、Redis必要状态、对象存储和配置恢复；记录RPO/RTO来源、hash和应用健康。
5. Queue Replay规定可重放事件、dedupe/idempotency、批量、暂停/恢复、dead-letter和经济不变量；演练重复Outbox不重复记账。
6. Parameter Rollback只创建新Release/Snapshot，不修改旧active历史；验证回滚期间进行中对象仍用原snapshot。
7. Degraded Mode覆盖外部源、通知、OTC、Prediction、Reward、上传、报表；明确read-only/closed/stale，不以0或mock替代失败。
8. Incident Runbook定义severity、检测、止损、证据保护、沟通、权限、恢复、对账和postmortem；高风险case保留Audit ID。
9. 运行tabletop：数据源中断、队列积压、错误Parameter Release、Settlement异常、secret泄露、迁移失败；逐项记录operator动作和缺口。

**验证**：Runbook链接/命令dry-run；Sandbox backup/restore；queue replay；parameter rollback；degraded mode；incident tabletop；Owner/on-call字段完整。

**停止条件**：恢复依赖未授权生产访问；RPO/RTO/Owner未确认；回滚或对账无法证明。报告缺口，不执行生产动作。

**验收**：11类Runbook齐全且相互引用同一artifact；Sandbox恢复和tabletop通过；正式参数未批准项保持null/closed。

### S06-P04 · 最终 Release Readiness Gate

**目标文件**：`RELEASE_READINESS_EVIDENCE_INDEX.md`、`OPEN_FINDINGS_AND_OWNER_CONDITIONS.md`、`.project-ai/reviews/STAGE-06-FINAL-RELEASE-READINESS-GATE.md`。

**固定步骤**：

1. 锁定候选revision和artifact hash；核对STAGE-01..05独立Gate均APPROVED，后续提交未使旧快照失效。
2. 汇总产品/API/DDL/state/Page ID/i18n/Figma/security/performance/observability/runbook/migration证据，链接必须可打开且hash匹配。
3. 重跑最小发布候选验证：OpenAPI、backend tests、H5/Admin build/tests、Flutter analyze/tests/build evidence、五主Flow、核心守恒和secret scan。
4. 核对P0/P1/blocking P2=0；非阻塞项有owner/date/风险；任何 `NOT_RUN` 说明Gate影响。
5. 核对正式地区、年龄、fee、Robot/Reward、OTC/Prediction、Treasury、SLO和通知渠道 Active Release；缺失值不得用Sandbox fixture替代。
6. 核对Production deployment/migration/signing/real-value仍需Owner独立审批；本Gate不执行、不排期、不推送。
7. Quality输出唯一Final报告，分别给Development、Sandbox、Release Readiness、Production四个结论。

**停止条件**：任一前置Stage Gate失效；证据hash漂移；开放P0/P1/blocking P2；必要运行时检查未执行；生产参数/风险接受缺失。

**验收输出**：

```text
DEVELOPMENT = GO | NO-GO
SANDBOX = GO | NO-GO
RELEASE_READINESS = PASS | FAIL | PASS_WITH_OWNER_CONDITIONS
OPEN_P0 = 0
OPEN_P1 = 0
OPEN_BLOCKING_P2 = 0
PRODUCTION_DEPLOYMENT = NOT_EXECUTED
PRODUCTION_MIGRATION = NOT_EXECUTED
PRODUCTION_REAL_VALUE = NO-GO_UNTIL_SEPARATE_OWNER_APPROVAL
```

---

## 12. P0 功能验收矩阵

| 模块 | 必须通过 |
|---|---|
| Auth | 注册、OTP、登录、MFA、找回、refresh/revoke、频控和安全失败状态 |
| KYC | not_started→pending→needs_info/approved/rejected；准入与 KYC 分开 |
| Home | 任一卡片失败不拖死整页，当前状态/原因/下一步明确 |
| Robot | 1–56、启动/停止、升级 quote/snapshot、Power preview、状态恢复 |
| Reward | 公式、系数 0、Claim 幂等、过期、review、reversal |
| APT | available/frozen/pending 分开；append-only；历史不覆盖 |
| Power | cap 服务端下发；freeze/consume/release/recover 可解释 |
| OTC | submitted≠completed；partial/cancel/expire/dispute 和 Power 联动 |
| Prediction | Football 1X2、Consent、锁定、Result/Settlement 分离、Refund/Correction |
| Parameter | Candidate/Approval/Release/Snapshot；TBC=null；Active immutable |
| Support/Notice | 工单闭环；通知失败不回滚业务；深链安全 |
| Admin | 8 导航、13 canonical roles、审批/账本/参数/审计闭环 |
| Audit | request/object/user/approval/case 全链路可追踪 |

---

## 13. 通用 Definition of Done

### 后端

- OpenAPI 与实现一致；所有写操作幂等。
- 并发写有 object_version/lock，经济写走 AptAccount 统一 CAS 锁域。
- Service 为唯一写入者；Controller 不直接写 DAO/Model。
- 账本 append-only，修正用 reversal；历史通过 snapshot 回算。
- Policy/Parameter/allowed_actions 服务端解析，TBC fail-closed。
- Outbox 重试不重复资金效果。
- RBAC + ABAC + MFA + SoD（适用时）有测试。

### H5/Admin/Flutter

- Page ID、Route、Figma Node 可追溯。
- Default/Loading/Empty/Error/Restricted 和写操作状态完整。
- 金额不用 float/double 做业务计算。
- 刷新/重连后查服务端终态。
- 7 语言 key 一致，无用户文案硬编码。
- H5 375/390/430 与 Figma 对照通过。

### Quality

- 审核绑定准确 commit/range/hash。
- 每条 Finding 有文件、行/函数、证据、可达场景、最小修复、验收和回归。
- Development Agent 已对 Finding adjudicate。
- P0=0、P1=0、blocking P2=0 后才允许当前包合并。

---

## 14. 执行 Agent 固定首轮提示词

把以下内容连同本文件交给 Development Agent：

```text
你是 Gainode 唯一 Development Agent。工作区只能是 E:\github\sports。

先完整读取：
1. Gainode_Development_Ready_V6.1_Latest/01-08 当前基线；
2. Gainode_Development_Ready_V6.1_Latest/design-system/12_FIGMA_FRONTEND_DEVELOPMENT_BASELINE_V1.0.md；
3. .project-ai/bootstrap.md、context.md、architecture.md、manifest.yaml、rules/coding.md、rules/review.md、rules/roles.md、rules/workflow.md、rules/git-review-worktree.md；
4. 当前 package 指定的 task/freeze/review；
5. 通过agent开发前规则/EXECUTOR_AGENT_PROTOCOL.md。

严格执行 07 §4 的 12 步和当前 Package 的目标/固定步骤/验证/停止条件/验收。先从 manifest、Quality Progress Ledger、task 和 Git 确认首个未关闭 Package；不得使用本提示词中的历史包号覆盖实时证据，不自行创造 Stage、业务规则、状态、API、DDL、依赖或正式参数。

已完成的 MC1 8 实体和 Ledger append-only 防护禁止重做。你**一开到底**：完成一个包后立即生成快照并继续下一个已定义包，不等待审核结论、合同冻结、Owner 决策或 Stage Gate；唯一硬停止是 §0.1 永久禁止项与「包未在本文件定义」。未冻结合同/未决决策按 best-effort 实现并在交接声明，受影响写路径 fail-closed。

每包结束必须输出完整交接字段（含 `CONSUMED_UNFROZEN_CONTRACT`、`OPEN_OWNER_DECISION`、`OVERLAPS_LOCKED_SNAPSHOT`）、审核提示词、精确文件范围、验证证据和 NEXT_PACKAGE。
涉及规则性修改时生成 Change Request/Decision Request 后继续无依赖部分，不停下等待；不得猜测，不得把推测写进代码。
允许为当前 Package 创建范围纯净的本地提交，trailer 用 `Code-Origin: Developer` + `Git-Operator: Developer`；禁止 push、merge、rebase、cherry-pick、deploy，除非派发单另有明确授权。
```

---

## 15. Quality Agent 固定审核提示词

```text
你是 Gainode Independent Quality Agent，默认只读。工作区只能是 E:\github\sports。

唯一审核计划基线是本文件 V3.4，Freeze ID=GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.4-20260816，状态必须为 FROZEN_FOR_EXECUTION。先读取 DEVELOPMENT_EXECUTION_PLAN_FREEZE_V3.4.md，并按凭证规定的 `UTF8_LF_NO_BOM` 规范化方式核对本文件 SHA-256；不一致时输出 EXECUTION_PLAN_FREEZE_MISMATCH 并停止使用该快照，不得自行选择旧版计划。

必须按本文件定义的 Package 顺序逐包锁定 Snapshot 和审核，并在每个 Formal Stage 的全部 Package 完成后单独执行 Stage Gate。不得新增、删除、合并、拆分、跳过或重排 Package；计划变更必须先取得 Owner 明确批准并生成新版本和新 Freeze ID。

自 V3.3 起，Development Agent 一开到底、不因门禁停止，因此你承担全部进度门禁的验证职责。审核每包时必须逐项验证并记入 Finding：
- 消费了未冻结合同（CONSUMED_UNFROZEN_CONTRACT 是否如实声明、受影响写路径是否 fail-closed、是否用旧值/mock 补洞）。
- Owner 决策未决即被实现（OPEN_OWNER_DECISION 是否声明、依赖对象是否 fail-closed）。
- 与已锁定快照路径重叠（OVERLAPS_LOCKED_SNAPSHOT 是否声明）。
- 本包「前置」字段（合同 FROZEN / 目录冻结 / Decision Gate）是否被违反。
- 本包「停止条件」所述风险是否已如实登记而未掩盖。

先验证 PROJECT/STAGE/PACKAGE/BASE_COMMIT/SNAPSHOT_COMMIT/REVIEW_RANGE/PACKAGE_SHA256/SNAPSHOT_PATHS。
只审核锁定快照，不把 Developer 后续提交混进本轮，不修改产品代码。

依据 01-08、当前 Freeze、.project-ai/rules/review.md、.project-ai/rules/workflow.md（§8 十一项门禁清单）、.project-ai/rules/roles.md 和 INDEPENDENT_REVIEW_AGENT_PROTOCOL.md 审核。
每条 Finding 必须给：严重度、精确文件、行/函数、当前行为、期望行为、证据、根因、触发条件、可达场景、影响、最小修复步骤、禁止扩展、验收、回归、Gate 影响。

区分：
- 当前包代码是否可合并；
- Development Agent 是否违反了进度门禁（即使违反，Dev 仍可继续，但必须记为 Finding 并要求修复）；
- Formal Stage 是否关闭；
- Production 是否授权。

禁止把它们合并成一个结论。报告末尾必须输出：
SNAPSHOT_LOCKED
CODE_MERGE_RECOMMENDATION
DEV_GATE_VIOLATIONS
FORMAL_STAGE_GATE
PRODUCTION_APPROVAL = NO
```

---

## 16. 文档变更与后续维护

- 需求变化优先修改 01–08 对应文件，不新增“差不多一样”的说明书。
- 本文件是唯一开发路线和 Agent 派发基线；`.project-ai/bootstrap.md` 只保存当前状态和指针，不复制整份计划。
- 每完成一个工作包，只更新 §2 进度、当前包、对应验收状态和证据链接。
- 任何规则性修改必须有 Owner 决策记录；执行 Agent 不得把自己的推理当成 Owner Signoff。
- 文档修改完成后应同步项目上下文发布工具；若工具不可用，记录 `CONTEXT_PUBLISH = NOT_RUN_TOOL_UNAVAILABLE`，不得伪造发布成功。

### 变更流水

| 版本 | Freeze ID | 变更内容 | Owner 决策 |
|---|---|---|---|
| V3.3 | `...V3.3-20260816` | 开发进度门禁全部移交 Quality（Dev 一开到底，DEV_GATE_MODEL=NO_PROGRESS_GATES_QUALITY_ENFORCES） | CR-20260816-003 |
| V3.4 | `...V3.4-20260816` | 整合五角色执行纪律：§3.1 五角色表（新增 Scheduler 显式化）、§3.2 双流水线、§3.3 提交来源 trailer 统一为 `Code-Origin`+`Git-Operator`、新增 §3.4 独立审核 worktree + 11 项门禁清单；§14/§15 提示词引用 `rules/roles.md`/`workflow.md`/`git-review-worktree.md`。不改变 40 个 Package 的 ID/顺序/范围/步骤/停止条件/验收/Gate 语义 | CR-20260816-004 |

