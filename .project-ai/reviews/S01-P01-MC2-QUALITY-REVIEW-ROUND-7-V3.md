# Quality Review — S01-P01-MC2（IR 686 修复复审，Round 7）

```text
REVIEW_ID = GAINODE-S01P01-MC2-IR-20260816-001
PROJECT = Gainode
QUALITY_AGENT = QUALITY-01
FORMAL_STAGE = STAGE-01
PACKAGE_ID = S01-P01-MC2-REVIEW-LOCK
REVIEW_ROUND = 7
QUALITY_MODE = INDEPENDENT_READ_ONLY_SNAPSHOT_REVIEW
PRODUCT_CODE_CHANGED_BY_QUALITY = NO
```

## 0. 审核绑定

```text
BASE_COMMIT = 7e6f828a9566b7382dae6aa7c918a63d0747b79a
SNAPSHOT_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
REVIEW_RANGE = 7e6f828..2795e38
SNAPSHOT_PATHS = 5 文件（见 Developer Snapshot）
PACKAGE_SHA256 = 7789e3933113e7c29e89d85e608885b99cf8704b667127540ef54ec0b88b25a2
SNAPSHOT_LOCKED = YES
```

## 1. 材料完整性矩阵

| 材料 | 是否存在 | 状态 |
|---|---|---|
| REVIEW_REQUEST.md | 是 | PASS |
| SELF_REVIEW.md | 是 | PASS |
| VALIDATION_RESULTS.md | 是 | PASS |
| PAYLOAD_MANIFEST.csv | 是 | PASS（5 文件 SHA256 与 git tree 逐字节一致） |
| PACKAGE_SHA256.txt | 是 | PASS |
| CHANGED_FILES.txt | 是 | PASS |
| REVIEW_RANGE.txt | 是 | PASS |
| IMPLEMENTATION_COMMIT.txt | 是 | PASS |
| DIFF.txt（41930 字符，未截断） | 是 | PASS |
| SECRET_SCAN.md | 是 | PASS（0 hits） |
| KNOWN_LIMITATIONS.md | 是 | PASS |

## 2. 变更概览

`2795e38` 为 MC2（Machine Contract 第二批 State Transition Freeze）对 IR 686 六审 Findings 的修复 commit，共 5 个文档文件（+92/-43），无业务代码、无 DDL 变更。

## 3. 审核结论（逐条）

### P1-1 — 统一 Economic Mutation Lock：CLOSED

`apt_accounts.object_version` CAS 已从「dispute hold 锁」升级为整个账户经济写操作的统一并发锁域：

```text
APT_ACCOUNT_ECONOMIC_MUTATION_LOCK = apt_accounts.object_version
ALL_ACCOUNT_ECONOMIC_MUTATIONS_REQUIRE_ACCOUNT_CAS = YES
覆盖域 = balance_apt_i / balance_apt_c / frozen_apt_i / frozen_apt_c / aggregate_dispute_hold
```

- 已冻结 11 步同事务顺序，任何步骤失败全 rollback。
- 跨操作并发同域串行（L5 vs Withdrawal / Prediction stake / OTC debit / L1 debit / L6-L7 / hold release）。
- 新增机械断言 `ACCOUNT_ECONOMIC_OVERSUBSCRIPTION = 0`、`L5_WITHDRAWAL_CONCURRENCY = PASS`、`L5_PREDICTION_STAKE_CONCURRENCY = PASS`。
- **独立验证**：锁基础字段真实存在于 MC1 DDL（`apt_accounts.object_version` 第 32 列；`balance_apt_i/c`、`frozen_apt_i/c` 均存在），非凭空发明 schema。
- 一致性：design.md A.1.2 / Freeze §3.1 / manifest / acceptance 四处一致。

### P2-1 — PRE_HOLD_MUTATION_GUARD：CLOSED

`SHORTFALL_CHECK_PHASE = PRE_L5` 已改为通用 `PRE_HOLD_MUTATION_GUARD`，显式列出适用 transition（L4 pending DEBIT→disputed + L5 posted CREDIT→disputed）及未来正向 hold transition，新增 `PENDING_DEBIT_SHORTFALL_PRECHECK` / `POSTED_CREDIT_SHORTFALL_PRECHECK`。残留活跃 `PRE_L5` 无（仅历史 provenance 记录，属正确追溯）。

### P2-2 — 并发错误码统一：CLOSED

对外统一 `OBJECT_VERSION_CONFLICT`（HTTP 409），`ACCOUNT_LOCK_CONFLICT` 仅 `INTERNAL_ONLY=YES` + `API_ERROR_MAPPING=OBJECT_VERSION_CONFLICT`，新增 `ACCOUNT_CONFLICT_API_CODE = OBJECT_VERSION_CONFLICT`。

### P2-3 — Review 证据完整性：CLOSED

门禁保持 `REVIEW_PACKAGE_TRUNCATED = NO`。本轮 Development Agent 已直接生成完整未截断 `DIFF.txt`（41930 字符）。QUALITY-01 独立逐字节核对 5 文件 SHA256 与 git tree 完全一致，且直接读取完整 diff（非工具 25000 字符硬截断输出）。

## 4. 需求与验收覆盖矩阵

| IR 686 Finding | 落点 | 验收断言 | 状态 |
|---|---|---|---|
| P1-1 | design A.1.2 + Freeze §3.1 | ACCOUNT_ECONOMIC_OVERSUBSCRIPTION=0 / L5_WITHDRAWAL_CONCURRENCY=PASS / L5_PREDICTION_STAKE_CONCURRENCY=PASS | CLOSED |
| P2-1 | design A.1.2 + Freeze §3.1 | PENDING_DEBIT_SHORTFALL_PRECHECK=PASS / POSTED_CREDIT_SHORTFALL_PRECHECK=PASS | CLOSED |
| P2-2 | design A.1.2 + Freeze §3.1 | ACCOUNT_CONFLICT_API_CODE=OBJECT_VERSION_CONFLICT | CLOSED |
| P2-3 | acceptance IR 686 行 + Freeze §8 | REVIEW_PACKAGE_TRUNCATED=NO | CLOSED |

## 5. Freeze / Machine Contract 一致性

- MC2 状态正确保持 `IMPLEMENTED / RE_REVIEW_PENDING`（未 FROZEN），无越权声明。
- 既有已冻结合同无回退：`POSTED_DEBIT_DISPUTE_AVAILABLE_INCREASE=0`、`PENDING_DEBIT_DISPUTE_RESERVATION=PASS`、`PENDING_REVERSAL_ECONOMIC_ENTRY_COUNT=0`、`POSTED_REVERSAL_DIRECTION=PASS` 全部保留。
- RiskCase 冻结状态（`CONTRACT_GAP` / `TARGET_BATCH 2B-2`）、`SHORTFALL_UNDECIDED_EXECUTION=0`、settling→refunding（P5）RefundCase 触发等均未变。
- 未定义维度仍 deferred 至 2B-2，未自行发明。

## 6–9. Findings

### P0 Findings

无。

### P1 Findings

无。

### P2 Findings（Blocking / Non-Blocking）

无。

### P3 Findings

#### S01-P01-P3-001 — 锁域枚举将存储列与推导投影混列，建议标注 `aggregate_dispute_hold` 为 DERIVED

```text
SEVERITY = P3
FILE_PATH = .project-ai/tasks/TASK-20260815-001/design.md（A.1.2 D 节）
          0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md（§3.1）
LINE_RANGE_OR_SECTION = 「凡改变以下任一字段/经济容量…」枚举
CURRENT_BEHAVIOR = 枚举将 balance_apt_i / balance_apt_c / frozen_apt_i / frozen_apt_c / aggregate_dispute_hold 并列，
                   未标注 aggregate_dispute_hold 是推导投影（MC1 DDL 无此列，= Σ active disputed/reserved entries 的 hold）
EXPECTED_BEHAVIOR = 在枚举中标注 aggregate_dispute_hold 为 (DERIVED: Σ ledger)，避免 STAGE-02 实现者误以为存在物理列需直接 CAS/UPDATE
EVIDENCE = MC1 DDL（20260813_..._8_core_entities.sql）apt_accounts 无 aggregate_dispute_hold 列；design.md 已另处定义其为 ACCOUNT-LEVEL AGGREGATE 推导公式
IMPACT = 非阻塞；仅影响 STAGE-02 实现可读性
MINIMUM_SAFE_FIX = 在枚举行把 aggregate_dispute_hold 改为 aggregate_dispute_hold（DERIVED，经 ledger entry mutation 重算，非物理列）
GATE_IMPACT = NONE（不阻塞 FROZEN / 不阻塞后续包）
RUNTIME_OR_OWNER_VALIDATION_REQUIRED = NO
```

## 10. Closed Finding 回归

上一轮 IR 686 四项 P1-1/P2-1/P2-2/P2-3 全部 CLOSED（见 §3），无残余。

## 11. 权限/状态/资金/数据/API 关键矩阵

```text
负余额允许 = NO（NEGATIVE_EFFECTIVE_AVAILABLE=0）
部分冲正 = FORBIDDEN
自动债务 = FORBIDDEN
账户级经济超卖 = FORBIDDEN（ACCOUNT_ECONOMIC_OVERSUBSCRIPTION）
并发冲突对外码 = OBJECT_VERSION_CONFLICT(409)
未冻结维度执行 = 0（SHORTFALL_UNDECIDED_EXECUTION=0）
Ledger 权威写入方 = 唯一 Authoritative Writer
```

## 12. 实际执行的验证

```text
STATIC_CHECK = PASS（git diff --check 无空白/冲突标记）
DIFF 完整性 = PASS（完整读取 189 行 git diff，非截断）
SECRET_SCAN = PASS（0 hits）
逐文件 SHA256 与 git tree 一致 = PASS（5/5）
MC1 schema 锁基础字段存在性 = PASS（object_version + 4 余额列）
残留旧名扫描（PRE_L5 / ACCOUNT_LOCK_CONFLICT） = PASS
TEST = NOT_RUN（本包为合同文档，无可执行代码）
BUILD = NOT_RUN（无编译产物）
RUNTIME_CHECK = NOT_RUN（属 STAGE-05 Sandbox）
DEPLOYMENT = NOT_RUN
```

## 13. 未执行验证

`php -l` / `composer test` / 运行时数据库验证：本包无 PHP/DDL 变更，均 NOT_RUN，属 STAGE-05 Sandbox 范围。

## 14. 工具限制

- 上一轮 IR 686 的 `REVIEW_PACKAGE_TRUNCATED` 根因是 AI Code Review Assistant 的 25000 字符硬截断。本轮 QUALITY-01 直接从 git tree 读取（`git show`/`git diff`/`git cat-file`），不受该限制，已完成完整验证。
- 工作树含后续未提交 V3 策划文档修改（`fd7968b` 之后），已按要求隔离，不混入本包审核。

## 15. Development Agent 继续条件

```text
SNAPSHOT_LOCKED = YES
SNAPSHOT_COMMIT_VERIFIED = YES
SNAPSHOT_PATHS_VERIFIED = YES
NEXT_PACKAGE_OVERLAP = NO
NEXT_PACKAGE_DOES_NOT_CONSUME_UNFROZEN_CONTRACT = YES（MC2 本审 APPROVED 后可 FROZEN）
NEXT_PACKAGE_IS_DEFINED_IN_V3_PLAN = YES（S01-P02 = 2B-1 状态合同）
```

## 16. Package 合并建议

```text
CODE_MERGE_RECOMMENDATION = APPROVED
```

MC2 可置 FROZEN（Owner Signoff 已完成 2026-08-15，独立复审本轮 APPROVED）。

## 17. Formal Stage Gate 状态

```text
FORMAL_STAGE_GATE = NOT_APPLICABLE（包级审核；STAGE-01 仍有 S01-P02 ~ S01-P09 未完成）
```

## 18. 可直接交给 Development Agent 的修复提示词

> 本包无 P0/P1/P2。仅 1 条非阻塞 P3（S01-P01-P3-001，见 §9），可在后续任意 STAGE-01 包中顺手修正，不要求单独提交。

```text
PROJECT = Gainode
WORKSPACE = E:\github\sports
FORMAL_STAGE = STAGE-01
PACKAGE_ID = S01-P01-MC2-REVIEW-LOCK
ORIGINAL_REVIEW_ID = GAINODE-S01P01-MC2-IR-20260816-001
ORIGINAL_SNAPSHOT_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
FIX_BASE_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
ALLOWED_FIX_PATHS = .project-ai/tasks/TASK-20260815-001/design.md; 0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md
LOCKED_PATHS = 0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql（MC1 冻结，禁改）
FINDINGS_TO_ADJUDICATE = S01-P01-P3-001（非阻塞，P3）
NON_GOALS = 不得改 MC1 DDL、不得解除 FAIL_CLOSED、不得自行发明经济规则、不得触碰 2B-1/2B-2 范围
REQUIRED_TESTS = NOT_RUN（文档澄清，无代码）
RE_SUBMISSION_REQUIREMENTS = 无需单独重提；可在 S01-P02 提交中顺带修正 S01-P01-P3-001
```

```text
SNAPSHOT_LOCKED = YES
REVIEW_COMPLETENESS = COMPLETE
VERDICT = APPROVED
P0_OPEN = 0
P1_OPEN = 0
BLOCKING_P2_OPEN = 0
NON_BLOCKING_P2_OPEN = 0
P3_OPEN = 1
CODE_MERGE_RECOMMENDATION = APPROVED
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
BLOCKED_PACKAGE = NONE
FORMAL_STAGE_GATE = NOT_APPLICABLE
PRODUCTION_APPROVAL = NO
```

No P0/P1 blocks on the MC2 freeze candidate. MC2 may be marked FROZEN after the Development Agent applies the status update.
