# S01-P07 复审请求（供 Quality Agent）

## 提审绑定

```text
PACKAGE_ID           = S01-P07-AFFILIATE-AGENT-P1
TASK_ID              = TASK-20260816-006
IMPLEMENTATION_COMMIT = 4f01bad
BASE_COMMIT           = 593775f
BRANCH               = feature/gainode-v3-serial-development
PACKAGE_SHA256       = （见 PACKAGE_SHA256.txt）
DIFF_UNTRUNCATED     = YES
REVIEW_PACKAGE_TRUNCATED = NO
SECRET_SCAN          = PASS（见 SECRET_SCAN.txt）
DDL_TABLE_COUNT_DELTA = 0（本包为合同盘点，不新增任何表）
```

## 范围

S01-P07 Affiliate/Agent P1 **合同盘点快照 1**（Owner 未签 → 不建表）。交付 5 文件（582 insertions）：

```text
.project-ai/tasks/TASK-20260816-006/requirement.md        范围 + 非目标 + 步骤映射
.project-ai/tasks/TASK-20260816-006/design.md             字段候选表 + Decision Matrix（11 决策）
.project-ai/tasks/TASK-20260816-006/acceptance.md         验收清单 + 机械断言
.project-ai/tasks/TASK-20260816-006/decision_request.md   Owner Decision Request（D1~D11）
0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_AFFILIATE_AGENT_P1_FREEZE.md  合同候选 Freeze
```

## 非目标

- 不建表（Owner 未签）。
- 不改动 V1.x `member_user_team` / `UserTeam*` 代码（只读盘点）。
- 不实现奖励发放写流程（属 STAGE-02）。
- 不自动继承 V1.x 佣金语义（invite_income_money/team_income_money 等不迁移）。

## 关键不变量（请逐项验证）

```text
OBJECT_COUNT = 3（Agent / Referral / AgentEarning）
DDL_TABLE_COUNT_DELTA = 0（快照 1 不建表）
OWNER_DECISION_COUNT = 11（D1~D11）
NO_LEGACY_COMMISSION_INHERITANCE = YES（V1.x 佣金字段不迁移）
P0_DEFAULT_CLOSED = YES（增长奖励写路径 fail-closed）
V1X_MEMBER_USER_TEAM_UNTOUCHED = YES（只读盘点）
CONTRACT_GAP = YES（Owner 未签前不建表不建 Service）
NO_SELF_INVENTED_STATE = YES（三对象 enum 全部候选，未冻结）
NO_SELF_INVENTED_ROLE = YES（复用 05 §8，未新增角色）
```

## 交接声明（Dev → Quality）

按 CR-20260816-003 OPTION_A（开发 agent 一开到底），本包在 Owner 未签、依赖合同未冻结的情况下完成**合同盘点**（非实现）：三对象 status enum 全部候选、状态机 FAIL_CLOSED、不建表。
Quality 审核时请将 07 §S01-P07 的「前置/停止条件/Stage Gate」作为验证项登记，不阻塞 Dev；Owner 签后进入快照 2（建 DDL/Model/DAO/Service）。

## 审核重点

1. 字段候选表是否完整覆盖三对象，每字段是否准确标 SOURCE_CONFIRMED / OWNER_DECISION_REQUIRED。
2. Decision Matrix 是否覆盖 9 维度（状态/层级/重复归属/解绑/确认时点/预算/回滚/税务/PII）且每项给 OPTION_A/B + RECOMMENDED_OPTION。
3. Freeze 文档是否显式声明 CANDIDATE（未 FROZEN）+ FAIL_CLOSED，无自创状态/角色。
4. 是否明确不继承 V1.x 佣金语义（invite_income_money/team_income_money/reward 不迁移）。
5. Decision Request 的 11 项是否与 design.md 的 Matrix 一致（ID/对象/选项/推荐）。
6. 本包无 DDL 变更、无代码变更、无 `.env` 触碰。
