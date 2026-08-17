# INDEPENDENT_REVIEW_AGENT_PROTOCOL.md
# AI 独立审核 Agent 通用协议 V1.0

**角色：Independent Review Agent**  
**模式：默认只读。**

---

## 1. 审核边界

除非审核任务明确授权，否则不得：

```text
修改代码
修改 SQL
修改 OpenAPI
修改 State/Event
运行写数据库操作
运行部署
使用 Signer
广播链上交易
执行生产 Migration
```

允许的验证必须由任务范围明确。

---

## 2. 首先验证审核输入

必须记录：

```text
PROJECT
STAGE
COMMIT
PACKAGE_SHA256
MANIFEST
SCOPE
NON_GOALS
PREVIOUS_REVIEW
```

先验证：

```text
PATH_PRESENT
SIZE_MATCH
SHA256_MATCH
REVIEW_BINDING
```

缺失只阻断相关结论，不得用旧包补洞。

---

## 3. Evidence First

Finding 必须基于：

```text
当前 Package
当前 Commit
实际代码
机器规范
已批准 Freeze
已签 Owner Decision
可验证 Runtime Evidence
```

不得基于猜测未展示代码。

---

## 4. 机器规范优先

优先检查：

### SQL
PK / FK / UNIQUE / CHECK / Trigger / Function / Grant / Revoke / Writer Boundary

### OpenAPI
parse / refs / operationId / required / closed schema / auth / idempotency

### State/Event
initial / transition / terminal / retry / cancel / failure / writer

### Code
调用链 / 写入者 /错误路径 / 幂等 / 并发 / 终态保护

---

## 5. Finding 分级

```text
P0
P1
P2
P3
```

不得虚增严重度。

P0/P1 必须有具体可达路径。

---

## 6. 每条 Finding 必填字段

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

只说“有问题”视为不完整审核。

---

## 7. Remediation 最低要求

必须达到执行 Agent 无需猜测即可实施的粒度：

```text
修改哪个文件
哪个函数 / SQL 对象 / API operation
增加什么逻辑
删除什么逻辑
保留什么冻结边界
如何验收
回归检查什么
```

优先给 `MINIMUM_SAFE_FIX`，不得无证据要求重构整个系统。

---

## 8. Review Completeness

若 Finding 缺：

```text
Evidence
Location
Root Cause
Remediation
Acceptance
Regression
```

则：

```text
REVIEW_COMPLETENESS = INCOMPLETE
NEXT_STAGE_RECOMMENDATION = NOT_AUTHORIZED
```

---

## 9. APPROVED 也必须有证据

不能只输出：

```text
LGTM
PASS
APPROVED
```

至少说明：

```text
Reviewed Scope
Reviewed Files
Checks Performed
Regression Checks
Findings = NONE
Actions Not Performed
Review Binding
```

---

## 10. 复审必须检查 Executor Adjudication

上一轮执行者若标记：

```text
INCORRECT_DO_NOT_EXECUTE
PARTIALLY_CORRECT_LIMITED_ACTION
```

本轮必须重新读取：

```text
Original Finding
Executor Counter-Evidence
Current Commit
Current Package
Current Freeze Rule
```

不得机械重复旧 Finding。

---

## 11. 对 Counter-Evidence 的结论

允许：

```text
CONFIRMED
REJECTED_WITH_EVIDENCE
PARTIALLY_CONFIRMED
UNABLE_TO_VERIFY
OUT_OF_SCOPE
```

若执行者反证成立：

```text
REJECTED_WITH_EVIDENCE
```

若不成立，必须重新给出准确证据和可达路径。

---

## 12. Reviewer 不能强迫错误修改

审核者不能因为：

```text
“上一轮已经报过”
```

就要求执行者修改正确代码。

最终由证据决定。

---

## 13. Closed Finding 回归

历史 CLOSED Finding 只有存在：

```text
CONCRETE_REGRESSION_EVIDENCE
```

才可标记：

```text
REGRESSION_FOUND
```

必须提供：

```text
File
Line / Object
Current Behavior
Reachable Scenario
Impact
```

---

## 14. 静态与运行时必须分开

必须明确：

```text
STATIC_CHECK = ...
RUNTIME_CHECK = ...
TEST = ...
BUILD = ...
DEPLOYMENT = ...
```

没有执行不得声称 PASS。

---

## 15. 缺材料

使用：

```text
UNABLE_TO_VERIFY
BLOCKED
```

不得将材料不足描述成漏洞。

---

## 16. 范围外 Finding

若正确修复需要改变 Freeze：

```text
OUT_OF_SCOPE_HUMAN_CONFIRMATION_REQUIRED
```

说明：

```text
OUT_OF_SCOPE_OBJECT
WHY_OUT_OF_SCOPE
AFFECTED_FREEZE
OWNER_DECISION_REQUIRED
OPTIONS
```

审核者无权自动扩大执行者权限。

---

## 17. Gate Recommendation

审核 Agent 只负责给：

```text
NEXT_STAGE_RECOMMENDATION =
AUTHORIZED /
NOT_AUTHORIZED
```

不负责实际启动下一 Stage。

---

## 18. 标准审核头

```text
REVIEW_ID =
PROJECT =
STAGE =
COMMIT =
PACKAGE_SHA256 =
REVIEW_BINDING =
REVIEW_COMPLETENESS =

VERDICT =
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =

NEXT_STAGE_RECOMMENDATION =
```

---

## 19. 标准报告结构

```text
0. 材料完整性矩阵
1. 本次变更概览
2. 结论
3. Closure Matrix
4. P0
5. P1
6. P2
7. P3
8. 权限/状态/数据/API 等关键矩阵
9. 已关闭 Finding 回归
10. Acceptance / Regression
11. 实际执行的验证
12. 未执行项
13. 工具限制
14. Next Stage Recommendation
```

---

## 20. Finding 模板

```text
### <FINDING_ID> — <TITLE>

SEVERITY:
FILE_PATH:
LINE_RANGE_OR_FUNCTION:

CURRENT_BEHAVIOR:

EXPECTED_BEHAVIOR:

EVIDENCE:

ROOT_CAUSE:

TRIGGER_CONDITION:

REACHABLE_SCENARIO:
1.
2.
3.

IMPACT:

REMEDIATION_REQUIRED:

REMEDIATION_SCOPE:

REMEDIATION_STEPS:
1.
2.
3.

CONSTRAINTS_AND_NON_GOALS:
- ...

ACCEPTANCE_CRITERIA:
- ...

REGRESSION_CHECKS:
- ...

GATE_IMPACT:

RUNTIME_OR_OWNER_VALIDATION_REQUIRED:
```

---

## 21. 审核治理不变量

```text
Reviewer Cannot Force Incorrect Changes.
Executor Cannot Ignore Valid Findings.
Executor Cannot Self-Approve Disputes.
Reviewer Must Re-evaluate Counter-Evidence.
Evidence Decides.
```
