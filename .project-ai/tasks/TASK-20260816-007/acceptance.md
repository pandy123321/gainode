# Acceptance: S01-P08 AI Operations P1 合同盘点（快照 1）

## 状态

- **本快照**：合同盘点（不建表）。Owner 未签 → 不建表不建 Service。
- **Independent Review：未开始**

## 验收清单（快照 1）

| # | 验收项 | 状态 |
|---|---|---|
| 1 | V1.x arbitrage 盘点表完整（KEEP_INTERNAL/ADAPT/RETIRE/FORBIDDEN_TO_EXPOSE） | 待独立审核 |
| 2 | 三对象（AISignal/AIRecommendation/SimulationRun）字段候选表完整 | 待独立审核 |
| 3 | 每字段标 SOURCE_CONFIRMED / OWNER_DECISION_REQUIRED | 待独立审核 |
| 4 | Decision Matrix 覆盖 7 维度（状态/retention/供应商许可/writer/重试幂等/预算/模型版本）+ C 端边界 LOCKED | 待独立审核 |
| 5 | Freeze 文档（候选）+ Decision Request 生成 | 待独立审核 |
| 6 | Owner 未签 → 三对象 CONTRACT_GAP/FAIL_CLOSED，无 DDL/Service | 待独立审核 |
| 7 | C 端泄露边界 FORBIDDEN（signal/profit/position/payload 禁止 C 端） | 待独立审核 |
| 8 | 不继承 V1.x 矿机套利语义（arbitrage_project* 不迁移） | 待独立审核 |
| 9 | V1.x arbitrage_* 只读盘点，未改动，不沿用硬编码 secret | 待独立审核 |

## 机械一致性断言

```text
OBJECT_COUNT = 3（AISignal / AIRecommendation / SimulationRun）
DDL_TABLE_COUNT_DELTA = 0（快照 1 不建表）
OWNER_DECISION_COUNT = 9（D1~D9）+ 1 LOCKED（D10 C 端边界）
C_ENDPOINT_INTERNAL_LEAK = FORBIDDEN
V1X_MINER_MODE_RETIRED = YES
V1X_ARBITRAGE_UNTOUCHED = YES
AI_PREDICTION_BUDGET_ISOLATION = YES
CONTRACT_GAP = YES（三对象）
```

## 非目标验证（NOT_RUN，属快照 2 / STAGE-02）

```text
DDL/Model/DAO/Service/command 生成 = NOT_RUN（合同未 FROZEN）
负向测试 = NOT_RUN（快照 2）
AI 信号采集/推荐/模拟执行写流程 = NOT_RUN（STAGE-02）
```

## 交付物（快照 1）

- `.project-ai/tasks/TASK-20260816-007/{requirement,design,acceptance,decision_request}.md`
- `sql/MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md`（候选）
