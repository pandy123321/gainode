# Requirement: S01-P07 Affiliate/Agent P1 合同与骨架（快照 1：合同盘点）

## 状态

- **执行授权**：CR-20260816-003 OPTION_A（一开到底；Owner 决策未决 → 生成 Decision Request 后继续无依赖部分）
- **本快照**：合同盘点（字段候选表 + Decision Matrix + Freeze 文档 + Decision Request）
- **建表门禁**：Owner 未签 → API/DDL/Service 全部 CONTRACT_GAP/FAIL_CLOSED，不建表

## 背景

S01-P06 已交付非持久投影。本包（S01-P07）进入 Affiliate/Agent 领域（P1），先做合同盘点，不建表。

V1.x 用扁平表 `member_user_team`（邀请关系 + 团队统计 + 佣金金额混在一张表）。V6.1 要求三对象分离，且**不自动继承旧佣金语义**（07 §S01-P07 步骤 1）。

## 固定对象与业务边界

```text
Agent         — 代理商（用户可成为 Agent，具有层级关系）
Referral      — 邀请关系（邀请码 → 上下级绑定）
AgentEarning  — 代理商收益（append-only，reversal）
```

**业务边界（07 §S01-P07 + 02 §12）**：

```text
- P1：P0 正式奖励关闭（growth 奖励全部 fail-closed）
- 不得使用用户本金、退款、Prediction 结算或未批准预算支付增长奖励
- AI 与 Prediction 奖励基础分开
- 邀请关系可以共用
- Candidate/HELD 不可当成已支付
```

## 范围（快照 1 交付物）

```text
.project-ai/tasks/TASK-20260816-006/{requirement,design,acceptance}.md
sql/MACHINE_CONTRACT_AFFILIATE_AGENT_P1_FREEZE.md       合同 Freeze 文档（候选）
.project-ai/tasks/TASK-20260816-006/decision_request.md Owner Decision Request
```

字段候选表（Agent/Referral/AgentEarning）+ Decision Matrix 并入 design.md / Freeze 文档。

## 非目标（NON_GOALS）

- 不建表（Owner 未签）。
- 不改动 V1.x `member_user_team` / `UserTeam*` 代码（只读盘点，不删除不沿用）。
- 不实现任何奖励发放写流程（属 STAGE-02 / 合同 FROZEN 后）。
- 不自动继承 V1.x 佣金语义（invite_income_money/team_income_money 等旧字段不迁移）。

## 固定步骤映射（07 §S01-P07）

| 步骤 | 内容 | 本快照是否执行 |
|---|---|---|
| 1 | 从 01/02/06 + V1.x member_user_team 提取候选，不继承旧佣金语义 | ✅ |
| 2 | 三对象字段候选表（SOURCE_CONFIRMED / OWNER_DECISION_REQUIRED） | ✅ |
| 3 | Decision Matrix（状态/层级/重复归属/解绑/确认时点/预算/回滚/税务/PII） | ✅ |
| 4 | Owner 未签 → CONTRACT_GAP/FAIL_CLOSED；只读 Team 兼容盘点 | ✅ |
| 5 | Owner 签署后更新 05/06 → Freeze Candidate → 独立审核 | ⏳（等 Owner） |
| 6 | 合同 FROZEN 后建 DDL/Model/DAO/Service（Agent→Referral→AgentEarning） | ⏳（快照 2） |
| 7 | 负向测试 | ⏳（快照 2） |

## 交接声明

```text
OPEN_OWNER_DECISION = YES（层级深度/预算来源/状态/税务合规/PII 等，见 decision_request.md）
CONTRACT_GAP = YES（Owner 未签前，Agent/Referral/AgentEarning 不建表不建 Service）
P0_DEFAULT_CLOSED = YES（growth 奖励写路径 fail-closed）
```

## 信息来源

- `01_PRODUCT_FUNCTIONAL_BASELINE.md` §3 P1
- `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md` §12 Team/Referral、§13 收入确认、§14 经济写操作
- `06_PARAMETER_DICTIONARY.md` §9 Growth/Team（P1）
- `07_DEVELOPMENT_AND_ACCEPTANCE.md` S01-P07
- V1.x 代码 `library/{model,dao,service}/member/UserTeam*.php`
