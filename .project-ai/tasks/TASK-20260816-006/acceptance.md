# Acceptance: S01-P07 Affiliate/Agent P1 合同盘点（快照 1）

## 状态

- **本快照**：合同盘点（不建表）。Owner 未签 → 不建表不建 Service。
- **Independent Review：未开始**

## 验收清单（快照 1）

| # | 验收项 | 状态 |
|---|---|---|
| 1 | 三对象（Agent/Referral/AgentEarning）字段候选表完整 | 待独立审核 |
| 2 | 每字段标 SOURCE_CONFIRMED / OWNER_DECISION_REQUIRED | 待独立审核 |
| 3 | Decision Matrix 覆盖 9 维度（状态/层级/重复归属/解绑/确认时点/预算/回滚/税务/PII） | 待独立审核 |
| 4 | Freeze 文档（候选）+ Decision Request 生成 | 待独立审核 |
| 5 | Owner 未签 → 三对象 CONTRACT_GAP/FAIL_CLOSED，无 DDL/Service | 待独立审核 |
| 6 | 不继承 V1.x 佣金语义（invite_income_money 等不迁移） | 待独立审核 |
| 7 | V1.x member_user_team 只读盘点，未改动 | 待独立审核 |
| 8 | P0 正式奖励关闭（growth 写路径 fail-closed） | 待独立审核 |

## 机械一致性断言

```text
OBJECT_COUNT = 3（Agent / Referral / AgentEarning）
DDL_TABLE_COUNT_DELTA = 0（快照 1 不建表）
OWNER_DECISION_COUNT = 11（D1~D11）
NO_LEGACY_COMMISSION_INHERITANCE = YES
P0_DEFAULT_CLOSED = YES
V1X_MEMBER_USER_TEAM_UNTOUCHED = YES
CONTRACT_GAP = YES（三对象）
```

## 非目标验证（NOT_RUN，属快照 2 / STAGE-02）

```text
DDL/Model/DAO/Service 生成 = NOT_RUN（合同未 FROZEN）
负向测试 = NOT_RUN（快照 2）
奖励发放写流程 = NOT_RUN（STAGE-02）
```

## 交付物（快照 1）

- `.project-ai/tasks/TASK-20260816-006/{requirement,design,acceptance,decision_request}.md`
- `sql/MACHINE_CONTRACT_AFFILIATE_AGENT_P1_FREEZE.md`（候选）
