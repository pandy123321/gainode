# MASTER_PROJECT_GOVERNANCE.md
# AI 软件项目全生命周期开发、审核、证据与阶段治理总母版 V1.0

**状态：MASTER_BASELINE / MANDATORY**  
**适用范围：组织内部所有由 AI Agent 参与规划、开发、审核、验证或发布治理的软件项目。**

---

## 1. 母版目标

本母版用于统一项目在正式开发前、开发中、审核中、运行时验证和发布前的治理方法。

核心目标：

```text
需求有来源
设计有基线
开发有范围
变更有证据
执行者与审核者分离
审核有独立性
Finding 有证据和修复规格
修复有独立复验
阶段有 Gate
范围内可自动推进
范围外必须人工确认
运行时验证与静态审核分离
高风险操作单独授权
所有结论可追溯
```

标准闭环：

```text
PROJECT_BOOTSTRAP
→ BASELINE_REVIEW
→ DESIGN / MACHINE CONTRACT FREEZE
→ RESPONSIBLE_OWNER_FREEZE
→ STAGE_IMPLEMENTATION
→ SELF_CHECK
→ EVIDENCE_PACKAGE
→ EXTERNAL_REVIEW_BRIDGE
→ INDEPENDENT_REVIEW
→ EXECUTOR_ADJUDICATION
→ FIX / COUNTER_EVIDENCE
→ INDEPENDENT_REVIEW_RETEST
→ STAGE_GATE
→ AUTO_ADVANCE
→ RUNTIME_GATE
→ TEST / SECURITY / RELEASE GATE
```

---

## 2. 权威层级

发生冲突时默认按以下层级判定：

```text
L1 运行时不可变事实 / 已部署事实 / 固定运行证据
L2 固定 Commit、源码、构建产物、数据库对象、机器规范
L3 Owner Decision、Independent Review、Runtime Evidence
L4 Product / Architecture / Design / Freeze 文档
L5 Self Review / Historical Report
L6 Agent Summary / Conversation / Oral Description
```

低层级材料不得静默覆盖高层级事实。

冲突时必须：

```text
CONFLICT_STATUS = OPEN
```

并记录：

- 冲突双方；
- 各自证据；
- 权威层级；
- 影响范围；
- 所需 Owner / Independent Review 决策。

---

## 3. 核心角色

至少区分：

```text
PROJECT_OWNER
PRODUCT_OWNER
ARCHITECTURE_OWNER
DATABASE_OR_DATA_OWNER
API_OWNER
EVENT_STATE_OWNER
ENVIRONMENT_OWNER
SECURITY_OWNER
RBAC_OR_SIGNER_OWNER
DEPENDENCY_OWNER
LEGAL_OR_OPEN_SOURCE_OWNER
EXECUTION_AGENT
INDEPENDENT_REVIEW_AGENT
EXTERNAL_REVIEW_TOOL
```

必须长期保持：

```text
EXECUTION_AGENT != INDEPENDENT_REVIEW_AGENT
```

`EXTERNAL_REVIEW_TOOL` 是组织内部自动化审核桥梁，用于绑定：

```text
Execution Agent
→ Review Package
→ Independent Reviewer
→ Review Result
→ Retest / Closure
```

“External” 表示位于当前执行 Agent 的权限边界之外，不等于第三方 SaaS。

---

## 4. 状态不得混用

必须严格区分：

```text
PLANNED
DESIGNED
FROZEN
IMPLEMENTED
SELF_CHECKED
STATIC_VALIDATED
RUNTIME_VALIDATED
TESTED
INDEPENDENTLY_REVIEWED
OWNER_SIGNED
DEPLOYMENT_APPROVED
RELEASE_APPROVED
```

以下表达禁止互相替代：

```text
文档通过 != 代码通过
静态通过 != Runtime 通过
Build 通过 != Test 通过
Test 通过 != Deployment Approval
FIX_READY != CLOSED
Stage APPROVED != Production Approval
```

---

## 5. 开发前必须建立的基线

项目至少应建立以下类别；不适用时必须显式 `NOT_APPLICABLE` 并说明原因：

```text
README / INDEX
00_EXISTING_BASELINE_REVIEW
01_ARCHITECTURE_AND_MIGRATION
02_DATA_AND_DATABASE_FREEZE
03_API_AND_INTEGRATION_FREEZE
04_EVENT_AND_STATE_FREEZE
05_BUSINESS_RULES_AND_INHERITANCE
06_ENVIRONMENT_AND_DEPLOYMENT
07_FRAMEWORK_AND_DEPENDENCIES
08_RULES_COMPLIANCE_AND_DECISIONS
09_SELF_REVIEW
10_INDEPENDENT_REVIEW
11_RESPONSIBLE_OWNER_FREEZE
12_RUNTIME_GATE
STAGE_GATE_STATUS
```

---

## 6. 机器规范优先

若项目包含可机器解析的规范，审核和开发必须优先核对：

### SQL
- TABLE / COLUMN
- PK / FK / UNIQUE / CHECK / INDEX
- FUNCTION / TRIGGER / VIEW
- GRANT / REVOKE
- 对象创建顺序
- 权限最小化
- 不可变证据
- 单写者边界

### OpenAPI
- YAML parse
- local `$ref`
- operationId 唯一
- requestBody.required
- required fields
- closed schema
- auth / permission
- idempotency
- async semantics

### Event / State
- initial state
- transition
- terminal state
- retry / cancel / expire / failure / recovery
- writer
- event source
- transition target 有效性

Markdown 声明不能覆盖机器规范事实。

---

## 7. 证据优先与 Fail Closed

缺失以下任一关键证据时不得猜测 PASS：

```text
File
Commit
Package
Manifest
Hash
Runtime Input
Owner Decision
Approved Dependency
Review Result
```

应使用：

```text
BLOCKED
UNABLE_TO_VERIFY
PAUSED
NOT_READY
UNAVAILABLE
```

而不是“默认成功”。

---

## 8. 单一完整提审包

每次正式 Review 必须使用单一权威提审包。

禁止：

```text
新 Markdown + 旧 SQL + 历史 ZIP + 会话旧附件
```

拼接成一个虚拟 revision。

提审包至少绑定：

```text
PROJECT
STAGE
BASELINE_COMMIT
IMPLEMENTATION_COMMIT
SOURCE_REVISION
PACKAGE_SHA256
PAYLOAD_MANIFEST
SCOPE
NON_GOALS
PREVIOUS_REVIEW_VERDICT
OPEN_FINDINGS
```

---

## 9. PAYLOAD_MANIFEST

推荐字段：

```text
relative_path
size
sha256
```

审核者必须独立验证：

```text
PATH_PRESENT
SIZE_MATCH
SHA256_MATCH
```

当前包必须只有一个权威 Manifest。

---

## 10. Secret / Sensitive Data 出包 Gate

任何自动提审前必须执行：

```text
REVIEW_PACKAGE_SECRET_SCAN = PASS
REVIEW_PACKAGE_DATA_CLASSIFICATION = PASS
```

默认禁止进入提审包：

```text
.env*
private keys
mnemonic
seed
keystore
database password
production dump
unredacted RPC API key
session secret
production credential
PII export
```

内部审核桥梁也不得成为 Secret 扩散渠道。

---

## 11. Stage 计划

所有正式开发必须处于预定义 Stage 中。

每个 Stage 必须有：

```text
STAGE_ID
GOAL
INPUTS
ALLOWED_PATHS
FORBIDDEN_PATHS
NON_GOALS
DEPENDENCIES
IMPLEMENTATION_REQUIREMENTS
VALIDATION_REQUIREMENTS
REVIEW_REQUIREMENTS
EXIT_CRITERIA
NEXT_STAGE
```

禁止执行 Agent 自行创造未冻结 Stage。

---

## 12. Scope 自动判定

每个新任务开始时必须判断：

```text
Q1 是否属于当前 Stage？
Q2 是否已在 Freeze / Stage Plan 定义？
Q3 是否只修改允许路径、批准依赖或必要引用？
Q4 是否保持业务、API、DB、State/Event、权限、部署边界不变？
```

全部 YES：

```text
SCOPE_DECISION = IN_SCOPE
HUMAN_CONFIRMATION = NOT_REQUIRED
ACTION = CONTINUE
```

任一 NO 或不确定：

```text
SCOPE_DECISION = OUT_OF_SCOPE_OR_UNCLEAR
HUMAN_CONFIRMATION = REQUIRED
ACTION = PAUSE
```

---

## 13. 范围内自动执行

已批准 Stage 内可以自动：

- 实施当前任务；
- 执行已批准 Build / Static Check / Test；
- 使用已批准依赖；
- 生成 Commit；
- 生成 Evidence Package；
- 生成 Manifest / Hash；
- 提交 Independent Review；
- 修复已确认且范围内的 Finding；
- 重新提审；
- Gate 通过后自动进入已定义下一 Stage。

不得因正常范围内工作反复要求人工确认。

---

## 14. 范围外必须人工确认

出现以下任一情况必须暂停：

- 改业务规则；
- 改经济/计费/会计逻辑；
- 改数据库 Freeze；
- 改 API Contract；
- 改 Event / State；
- 改权限 / Role；
- 改已部署合约；
- 改部署地址 / 网络；
- 引入新外部系统；
- 引入未批准依赖；
- 改 Stage 边界；
- 改 Review Gate；
- 改 Freeze；
- 重开已关闭 Finding；
- Production Migration；
- Production Deployment；
- Mainnet；
- Signer / Private Key；
- On-chain Broadcast。

---

## 15. 外部独立审核强制规则

每个正式 Stage 完成后必须：

```text
锁定 Stage
→ 自检
→ 生成完整包
→ Manifest
→ SHA-256
→ External Review Tool
→ Independent Reviewer
→ Final Verdict
```

审核结果返回前：

```text
NEXT_STAGE_AUTHORIZATION = NO
```

---

## 16. Review Binding

Review 结果至少绑定：

```text
REVIEW_ID
PROJECT
STAGE
IMPLEMENTATION_COMMIT
PACKAGE_SHA256
SCOPE
REVIEWER_ROLE
TIMESTAMP
```

若 Hash / Commit / Scope 不匹配：

```text
REVIEW_BINDING = INVALID
NEXT_STAGE_AUTHORIZATION = NO
```

---

## 17. Finding 强制结构

每条正式 Finding 至少包含：

```text
FINDING_ID
SEVERITY
FILE_PATH
LINE_RANGE_OR_FUNCTION
CURRENT_BEHAVIOR
EXPECTED_BEHAVIOR
EVIDENCE
ROOT_CAUSE
TRIGGER_CONDITION
REACHABLE_SCENARIO
IMPACT
REMEDIATION_REQUIRED
REMEDIATION_SCOPE
REMEDIATION_STEPS
CONSTRAINTS_AND_NON_GOALS
ACCEPTANCE_CRITERIA
REGRESSION_CHECKS
GATE_IMPACT
RUNTIME_OR_OWNER_VALIDATION_REQUIRED
```

若只报告“有问题”而没有可执行修复规格：

```text
REVIEW_COMPLETENESS = INCOMPLETE
NEXT_STAGE_AUTHORIZATION = NO
```

---

## 18. Finding 分级

### P0
资金损失、Secret 泄露、权限完全绕过、错误生产/主网操作、不可逆破坏。

### P1
核心流程不可执行、认证/权限绕过、状态闭环缺失、重放/并发/篡改、错误成功、核心数据不一致。

### P2
文档漂移、实现歧义、非资金生命周期不收敛、审计/维护风险、非核心 fail-closed 问题。

### P3
低风险改进、命名、注释、可读性、非阻断优化。

---

## 19. 外部审核结论二次判定

执行 Agent 不得盲从 Review Finding。

必须逐项分类：

```text
CORRECT_ACTIONABLE
PARTIALLY_CORRECT_LIMITED_ACTION
INCORRECT_DO_NOT_EXECUTE
UNVERIFIABLE_PAUSE
OUT_OF_SCOPE_HUMAN_CONFIRMATION_REQUIRED
```

---

## 20. CORRECT_ACTIONABLE

正确且属于当前 Scope：

```text
MUST_FIX = YES
```

修复后只能标：

```text
FIX_READY / INDEPENDENT_RETEST_PENDING
```

不得自行 CLOSED。

---

## 21. INCORRECT_DO_NOT_EXECUTE

审核结论错误时不得为迎合报告修改正确代码。

必须提交：

```text
COUNTER_EVIDENCE
```

并保持：

```text
FINDING_CLOSURE = PENDING_INDEPENDENT_CONFIRMATION
```

执行者可以挑战 Review，但不能自行裁决自己赢。

---

## 22. PARTIALLY_CORRECT_LIMITED_ACTION

必须拆：

```text
VALID_PART
INVALID_PART
ALLOWED_REMEDIATION_SCOPE
FORBIDDEN_EXTENSION_SCOPE
```

只执行证据支持部分。

---

## 23. UNVERIFIABLE_PAUSE

证据不足：

```text
ACTION = PAUSE
NEXT_STAGE_AUTHORIZATION = NO
```

必须说明：

```text
MISSING_EVIDENCE
WHY_REQUIRED
HOW_TO_OBTAIN
REVIEW_RESUME_CONDITION
```

---

## 24. OUT_OF_SCOPE_HUMAN_CONFIRMATION_REQUIRED

Finding 即使正确，只要修复超出当前 Freeze Scope：

```text
ACTION = PAUSE
HUMAN_CONFIRMATION = REQUIRED
```

Review Finding 不能自动扩大执行权限。

---

## 25. Review Adjudication 记录

每轮必须记录：

```text
EXTERNAL_REVIEW_ID
ORIGINAL_VERDICT
REVIEW_PACKAGE_SHA256
ADJUDICATION_STATUS
FINDING_ID
EXTERNAL_CONCLUSION
INDEPENDENT_ASSESSMENT
EVIDENCE_CHECK
ACTION_TAKEN
ACTION_REASON
SCOPE_DECISION
NEXT_STAGE_AUTHORIZATION
```

整体状态：

```text
ACCEPTED
PARTIAL
DISPUTED
PAUSED
```

---

## 26. 下一轮提审必须带入上一轮裁决

必须携带：

```text
Previous Review ID
Original Verdict
Finding Adjudication
Correct Findings
Incorrect Findings
Partial Findings
Counter Evidence
Implemented Fixes
Previous Package SHA256
Current Package SHA256
```

形成：

```text
Finding
→ Adjudication
→ Fix / Counter-Evidence
→ Acceptance Evidence
→ Independent Retest
```

完整闭环。

---

## 27. 独立 Reviewer 必须重新检查反证

Reviewer 不得因为 Finding 来自上一轮 Review 就默认正确。

收到 Executor Counter-Evidence 后必须：

```text
Current Commit
Current Package
Original Finding
Counter Evidence
Current Freeze Rule
```

重新核验。

允许结论：

```text
CONFIRMED
REJECTED_WITH_EVIDENCE
PARTIALLY_CONFIRMED
UNABLE_TO_VERIFY
OUT_OF_SCOPE
```

---

## 28. Stage Gate 完整条件

进入下一阶段不能只看 `APPROVED`。

必须同时满足：

```text
IMPLEMENTATION_COMPLETE = YES
STATIC_SELF_CHECK = PASS
REQUIRED_VALIDATIONS = PASS_OR_FORMALLY_DEFERRED
PACKAGE_MANIFEST = VALID
PACKAGE_HASH_MATCH = YES
REVIEW_BINDING = VALID
FINAL_REVIEW_VERDICT = APPROVED / APPROVED_FOR_NEXT_STAGE
REVIEW_COMPLETENESS = COMPLETE
EXTERNAL_REVIEW_ADJUDICATION = ACCEPTED
ALL_FINDINGS_ASSESSED = YES
DISPUTED_BLOCKING_FINDINGS = 0
UNVERIFIABLE_BLOCKING_FINDINGS = 0
P0_OPEN = 0
P1_OPEN = 0
P2 = CLOSED_OR_DISPOSITIONED
ACCEPTANCE_CRITERIA = PASS
NEXT_STAGE_DEFINED = YES
NEXT_STAGE_WITHIN_FROZEN_PLAN = YES
```

才可：

```text
STAGE_GATE = PASSED
```

---

## 29. 自动推进

Stage Gate 通过：

```text
AUTO_ADVANCE_DECISION = APPROVED
NEXT_STAGE_AUTHORIZATION = YES
HUMAN_CONFIRMATION_REQUIRED = NO
```

执行 Agent 自动：

1. 归档当前 Stage；
2. 保存 Commit / Package / Review / Adjudication；
3. 更新 Stage 状态；
4. 读取下一 Stage；
5. 继承上一阶段上下文；
6. 切换当前 Stage；
7. 执行下一 Stage 范围内工作。

保持：

```text
One Stage
→ One Independent Review
→ One Gate
→ One Advancement Decision
```

---

## 30. 自动推进暂停条件

以下任一成立即暂停：

```text
Hash mismatch
Commit mismatch
Scope mismatch
Review incomplete
Adjudication disputed
Finding unverifiable
P0 open
P1 open
Blocking P2 unresolved
Acceptance failed
Required validation failed
Out-of-scope change
Freeze change required
New dependency not approved
Next Stage undefined
High-risk operation required
```

输出：

```text
AUTO_ADVANCE_DECISION = PAUSED
NEXT_STAGE_AUTHORIZATION = NO
HUMAN_CONFIRMATION_REQUIRED = YES
```

---

## 31. Responsible Owner Freeze

正式业务开发前应完成 Owner Freeze。

每项签署至少包含：

```text
Owner
Date
Approved Files / Version
Scope
Known Risks
Exceptions
Deferred Items
Change Process
```

P2 在 Freeze 前必须：

```text
FIX_BEFORE_FREEZE
ACCEPTED_DEFERRED
REJECTED_AS_INVALID
```

之一。

---

## 32. Runtime Gate

静态设计通过不代表 Runtime 通过。

按项目适用性执行：

```text
Database Migration
Role Runtime
Build
Approved Validation Tools
External Readback
Runtime Config
Integration
```

必须生成可复核证据。

---

## 33. 高风险操作独立授权

以下永远不得由 Stage Review 自动授权：

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

必须存在单独高风险 Gate。

---

## 34. 依赖治理

未批准：

```text
NO_DOWNLOAD_AUTHORIZED
```

每个重要依赖至少记录：

```text
name
purpose
exact version/tag/commit
source
license
NOTICE
CVE/security advisory
POC
benchmark
TCO
SBOM
upgrade plan
exit plan
Owner
Security Review
Legal Review
APPROVE_DOWNLOAD
```

---

## 35. Git / Version / Evidence

正式审核必须绑定：

```text
Commit
Diff
Files
Package
Manifest
Hash
```

不得用 public main 或当前 workspace 静默替代目标 Commit。

---

## 36. 历史审核不可重写

历史 Finding 必须保留：

```text
Original Finding
Original Verdict
Fix Commit
Retest
Closure Evidence
```

不能把历史问题改写成“从未发生”。

---

## 37. Closed Finding 重开

必须有：

```text
CONCRETE_REGRESSION_EVIDENCE
```

至少：

```text
File
Line / Object
Current Behavior
Reachable Scenario
Impact
```

否则保持 CLOSED。

---

## 38. 标准状态枚举

### Document
```text
DRAFT
CANDIDATE
FROZEN
DEPRECATED
HISTORICAL
```

### Finding
```text
OPEN
CONFIRMED
DISPUTED
FIX_READY
INDEPENDENT_RETEST_PENDING
CLOSED_BY_INDEPENDENT_REVIEW
ACCEPTED_DEFERRED
REJECTED_WITH_EVIDENCE
BLOCKED_EVIDENCE
```

### Stage
```text
NOT_STARTED
IN_PROGRESS
SELF_CHECK_PENDING
REVIEW_PACKAGE_READY
INDEPENDENT_REVIEW_PENDING
CHANGES_REQUIRED
FIX_READY
INDEPENDENT_RETEST_PENDING
APPROVED
BLOCKED
PAUSED
COMPLETED
```

---

## 39. 标准 Gate

推荐至少区分：

```text
DESIGN_GATE
OWNER_FREEZE_GATE
DEPENDENCY_GATE
IMPLEMENTATION_GATE
STATIC_REVIEW_GATE
RUNTIME_GATE
TEST_GATE
SECURITY_GATE
DEPLOYMENT_GATE
RELEASE_GATE
```

---

## 40. 永久治理不变量

```text
Evidence First.
Machine Contract Over Narrative.
No Guessing.
No Silent Scope Expansion.
Executor and Reviewer Must Be Separated.
Independent Review Is Mandatory.
Reviewer Cannot Force Incorrect Changes.
Executor Cannot Ignore Valid Findings.
Executor Cannot Self-Approve Disputes.
Counter-Evidence Must Be Re-reviewed.
FIX_READY Is Not CLOSED.
Self Review Is Not Independent Review.
Static PASS Is Not Runtime PASS.
Build PASS Is Not Test PASS.
Test PASS Is Not Deployment Approval.
Stage Approval Is Not Production Approval.
Least Privilege Is Mandatory.
Missing Evidence Must Fail Closed.
One Stage = One Review Unit.
Every Stage Must Pass Its Gate.
Valid Findings Are Fixed Within Scope.
Invalid Findings Are Rejected With Evidence.
Unverifiable Findings Pause Advancement.
Approved Stage Work Does Not Repeatedly Require Human Confirmation.
Stage PASS Auto-Advances Only Within Frozen Scope.
High-Risk Operations Require Separate Authorization.
Manifest and Hash Bind Every Formal Review.
Closed Findings Need Concrete Regression Evidence to Reopen.
Freeze Changes Require Formal Change Control.
Automation Reduces Repeated Confirmation; It Never Expands Agent Authority.
