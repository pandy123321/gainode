# S01-P08 复审请求（供 Quality Agent）

## 提审绑定

```text
PACKAGE_ID           = S01-P08-AI-OPS-P1
TASK_ID              = TASK-20260816-007
IMPLEMENTATION_COMMIT = 799d588
BASE_COMMIT           = f1b28c4
BRANCH               = feature/gainode-v3-serial-development
PACKAGE_SHA256       = （见 PACKAGE_SHA256.txt）
DIFF_UNTRUNCATED     = YES
REVIEW_PACKAGE_TRUNCATED = NO
SECRET_SCAN          = PASS（见 SECRET_SCAN.txt）
DDL_TABLE_COUNT_DELTA = 0（本包为合同盘点，不新增任何表）
```

## 范围

S01-P08 AI Operations P1 **合同盘点快照 1**（Owner 未签 → 不建表）。交付 5 文件（601 insertions）：

```text
.project-ai/tasks/TASK-20260816-007/requirement.md        范围 + 非目标 + 步骤映射
.project-ai/tasks/TASK-20260816-007/design.md             V1.x 盘点 + 字段候选表 + Decision Matrix（9 决策 + 1 LOCKED）
.project-ai/tasks/TASK-20260816-007/acceptance.md         验收清单 + 机械断言
.project-ai/tasks/TASK-20260816-007/decision_request.md   Owner Decision Request（D1~D9 + D10 LOCKED）
0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md  合同候选 Freeze
```

## 非目标

- 不建表（Owner 未签）。
- 不改动 V1.x `arbitrage_*` 代码（只读盘点，不删除、不迁移、不沿用 secret）。
- 不实现 AI 信号采集/推荐/模拟执行写流程（属 STAGE-02）。
- 不把内部套利数据暴露到 C 端。

## 关键不变量（请逐项验证）

```text
OBJECT_COUNT = 3（AISignal / AIRecommendation / SimulationRun）
DDL_TABLE_COUNT_DELTA = 0（快照 1 不建表）
OWNER_DECISION_COUNT = 9（D1~D9）
LOCKED_COUNT = 1（D10 C 端边界）
C_ENDPOINT_INTERNAL_LEAK = FORBIDDEN（signal/profit/position/payload 禁止 C 端）
NO_LEGACY_MINER_INHERITANCE = YES（arbitrage_project* 矿机/分销不迁移）
V1X_ARBITRAGE_UNTOUCHED = YES（只读盘点，不沿用 secret）
AI_PREDICTION_BUDGET_ISOLATION = YES（02 §11 双向 FORBIDDEN）
CONTRACT_GAP = YES（Owner 未签前不建表不建 Service）
NO_SELF_INVENTED_STATE = YES（三对象 enum 全部候选，未冻结）
NO_SELF_INVENTED_ROLE = YES（复用 05 §8，未新增角色）
```

## 交接声明（Dev → Quality）

按 CR-20260816-003 OPTION_A（开发 agent 一开到底），本包在 Owner 未签、依赖合同未冻结的情况下完成**合同盘点**（非实现）：三对象 status enum 全部候选、状态机 FAIL_CLOSED、不建表。
C 端泄露边界（D10）为 07 §S01-P08 固定边界（LOCKED），非 Owner 决策，违反即 Scope Finding。
Quality 审核时请将 07 §S01-P08 的「前置/停止条件/Stage Gate」作为验证项登记，不阻塞 Dev；Owner 签后进入快照 2（建 DDL/Model/DAO/Service/command）。

## 审核重点

1. V1.x arbitrage 盘点表是否准确（KEEP_INTERNAL/ADAPT/RETIRE/FORBIDDEN_TO_EXPOSE，核对 `_existing_prod/gainode_api/sql/database.sql` 的 `arbitrage_*` 表）。
2. 字段候选表是否完整覆盖三对象，每字段标 SOURCE_CONFIRMED / OWNER_DECISION_REQUIRED。
3. Decision Matrix 是否覆盖 7 维度（状态/retention/供应商许可/writer/重试幂等/预算/模型版本）+ C 端边界 LOCKED。
4. Freeze 文档是否显式声明 CANDIDATE（未 FROZEN）+ FAIL_CLOSED，无自创状态/角色。
5. 是否明确不继承 V1.x 矿机套利语义 + 不沿用硬编码 secret。
6. C 端泄露边界（D10）是否被锁定为 FORBIDDEN 且不可豁免。
7. Decision Request D1~D9 与 design.md Matrix 一致；D10 标记 LOCKED。
