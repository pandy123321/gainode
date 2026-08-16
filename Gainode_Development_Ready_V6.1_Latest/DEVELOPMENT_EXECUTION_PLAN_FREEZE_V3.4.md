# Gainode 开发执行计划冻结凭证 V3.4

```text
PROJECT = Gainode
WORKSPACE = E:\github\sports
FREEZE_ID = GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.4-20260816
FREEZE_STATUS = FROZEN_FOR_EXECUTION
FREEZE_DATE = 2026-08-16
TIMEZONE = Asia/Shanghai
OWNER_DIRECTIVE = ROLE_DISCIPLINE_INTEGRATED
SUPERSEDES = GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.3-20260816
OLDER_PLAN_STATUS = SUPERSEDED_DO_NOT_EXECUTE
CHANGE_REQUEST = CR-20260816-004
```

## 冻结对象

```text
FROZEN_PLAN_PATH = Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md
FROZEN_PLAN_VERSION = V3.4
FROZEN_PLAN_SHA256 = E48D6B8B22AF1C575528D2FB270D625C1EDEB2BF3B2C718C06171473A08CC8E8
FROZEN_PLAN_HASH_MODE = UTF8_LF_NO_BOM
EXECUTION_MODEL = ONE_DEVELOPMENT_AGENT_SERIAL_PACKAGES
QUALITY_MODEL = INDEPENDENT_READ_ONLY_SNAPSHOT_REVIEW
DEV_GATE_MODEL = NO_PROGRESS_GATES_QUALITY_ENFORCES
PLAN_CHANGE_CONTROL = OWNER_APPROVAL_REQUIRED
```

计算 SHA-256 前必须把文本解码为 UTF-8，把 CRLF 和单独 CR 统一为 LF，再按 UTF-8 无 BOM 编码。Development、Quality 和复审 Agent 启动时必须核对路径、版本、Freeze ID、状态和规范化 SHA-256；不匹配时输出 `EXECUTION_PLAN_FREEZE_MISMATCH`，不得回退旧计划。

## 冻结完整性

```text
TOTAL_UNIQUE_PACKAGES = 40
L1_DETAILED_PACKAGES = 40
H5_PAGE_IDS = 44
ADMIN_PAGE_IDS = 33
FORMAL_STAGES = STAGE-01,STAGE-02,STAGE-03,STAGE-04,STAGE-05,STAGE-06
CHANGE_REQUEST = CR-20260816-004
```

每个 Package 已冻结以下六类信息：目标、目标文件/对象、固定步骤、验证、停止条件和验收。Agent 不得新增、删除、合并、拆分、跳过或重排 Package，也不得用旧版框架级描述覆盖 V3.4 详细步骤。

## V3.4 核心变更：整合五角色执行纪律

```text
DEV_GATE_MODEL = NO_PROGRESS_GATES_QUALITY_ENFORCES（沿用 V3.3，未改变）
ROLE_MODEL = FIVE_ROLES（Owner/Scheduler/Developer/Quality/Reviewer，新增 Scheduler 显式化）
COMMIT_ORIGIN_TRAILER = Code-Origin + Git-Operator（取代 origin:developer/origin:quality 简写）
REVIEW_WORKTREE = gainode/review/<stage-id>（base=master，固定 SHA 物理隔离）
QUALITY_GATE_CHECKLIST = 11_ITEMS（见 07 §3.4 与 .project-ai/rules/workflow.md §8）
```

1. §3.1 升级为五角色表（Owner/Scheduler/Developer/Quality/Reviewer），Scheduler 由 Owner 兼任或独立承担，不写业务代码、不做 git 写操作、不自创 Stage；完整权限矩阵见 `.project-ai/rules/roles.md`。
2. §3.2 追加双流水线（开发线连续产出 / 质量线逐 Stage 收口），与 `DEV_GATE_MODEL` 一致。
3. §3.3 提交来源 trailer 统一为 `Code-Origin: Developer`/`Git-Operator: Developer` 与 `Code-Origin: Quality`/`Git-Operator: Quality`/`Review-Findings:`；V3.3 及更早的 `origin:developer`/`origin:quality` 视为等价追溯记录，新提交必须用完整格式。
4. 新增 §3.4 独立审核 worktree 与 11 项门禁清单，明确硬停止（push/merge/rebase/cherry-pick/tag/release/deploy；资产/账本/真实资金/生产数据；修改只读基线）仍需 Owner 单独授权。
5. §14/§15 提示词同步引用 `rules/roles.md`、`rules/workflow.md`、`rules/git-review-worktree.md` 并升级 trailer。

V3.4 不改变任何 Package 的 ID、顺序、范围、固定步骤、停止条件、验收条件或 Stage Gate 语义；仅补充执行层角色纪律与提交/审核规范。

## V3.3 既有核心变更（仍生效，V3.4 沿用）

```text
DEV_GATE_MODEL = NO_PROGRESS_GATES_QUALITY_ENFORCES
FAIL_CLOSED_SCOPE = RELAXED（Dev best-effort 建表/写骨架，交接声明消费未冻结合同）
OWNER_DECISION_GATE = CONTINUE_AFTER_DECISION_REQ（Dev 生成 Decision Request 后继续无依赖部分）
```

1. Development Agent 一开到底：完成一个包后立即生成快照并继续下一个已定义包，不等待审核结论、合同冻结、Owner 决策或 Stage Gate 关闭。
2. Development Agent 的唯二硬停止：§0.1 永久禁止项；「包未在本文件定义」。
3. 未冻结合同按 05/Freeze 已知内容 best-effort 实现，受影响写路径 fail-closed，交接声明 `CONSUMED_UNFROZEN_CONTRACT`。
4. Owner 决策未决时生成 Decision Request 后继续无依赖部分，交接声明 `OPEN_OWNER_DECISION`。
5. 所有 Package 的「前置」「停止条件」「Stage Gate」字段降级为 Quality 审核时的验证项与风险登记点。
6. Quality Agent 审核每包时逐项验证门禁违反情况并记为 Finding（见 07 §15）。

## 当前进度与禁止重做

```text
CURRENT_FORMAL_STAGE = STAGE-02_OPENAPI_ENV_BACKEND_P0
CURRENT_DEVELOPER_PACKAGE = S02-P05_PREDICTION_P0
CURRENT_KNOWN_IMPLEMENTATION_COMMIT = 916e815
STAGE-01 = LOCALLY_APPROVED（S01-P01..P09 全包本地 APPROVED，Production=NO-GO）
S02-P01 = APPROVED_DO_NOT_REDO
S02-P02 = APPROVED_DO_NOT_REDO
S02-P03 = APPROVED_DO_NOT_REDO
S02-P04 = COMPLETE_AWAITING_QUALITY_REVIEW
PRODUCTION_APPROVAL = NO
```

进度字段是冻结时快照。Agent 必须从 manifest、Quality Progress Ledger、task 和 Git 获取实时状态；进度已前移时从首个未关闭 Package 继续，不因本凭证的历史快照回退或重做。

## 执行与审核约束

1. Developer 串行修改产品代码，每包独立本地提交（trailer `Code-Origin: Developer` + `Git-Operator: Developer`）；一开到底，不停下等待审核/冻结/Owner/Stage Gate；禁止 push/merge/rebase/cherry-pick。
2. Quality 是唯一可执行审核分支组装、push、提审、merge 的 Agent，仍需 Owner 授权；质量修复用独立 commit（`Code-Origin: Quality` + `Review-Findings:`），不得混入 Developer 提交。
3. Quality 对每包锁定 exact commit/range/path/hash Snapshot，按 V3.4 对应 Package 验收，并按 07 §3.4 十一项门禁清单逐项验证（含未冻结合同消费、Owner 决策未决实现、路径重叠、前置/停止条件风险登记）。
4. 独立审核使用固定 SHA 的 worktree（`gainode/review/<stage-id>`，base=`master`），物理隔离，不碰 Developer HEAD。
5. `DEV_NEXT_PACKAGE_ALLOWED`、`CODE_MERGE_RECOMMENDATION`、`DEV_GATE_VIOLATIONS`、`FORMAL_STAGE_GATE`、`PRODUCTION_APPROVAL` 必须分开。
6. 每个 Formal Stage 的全部 Package 关闭后，Quality 单独提交 Stage Gate。
7. 未冻结业务、经济、状态、API、DDL、权限、依赖和参数必须生成 Owner Decision/Change Request；Dev 生成后继续无依赖部分，不得把推测写进代码，不得用旧值或 mock 补洞。
8. H5/Admin 严格按显式 Page ID、Figma 和 OpenAPI；Flutter 先完成工程 Decision Gate；Migration 至少三次 Sandbox dry-run+rollback；Release Readiness 不等于生产部署。

## 变更控制

改变 Stage、Package、范围、依赖、验收或 Gate，必须取得 Owner 明确批准，升级计划版本和 Freeze ID，重新计算 SHA-256，同步 bootstrap/context/manifest，并重新提交计划审核。在新冻结完成前，草案只能标 `DRAFT_NOT_EXECUTABLE`。
