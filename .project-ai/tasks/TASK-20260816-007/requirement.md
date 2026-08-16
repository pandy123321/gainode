# Requirement: S01-P08 AI Operations P1 与内部套利引擎边界（快照 1：合同盘点）

## 状态

- **执行授权**：CR-20260816-003 OPTION_A（一开到底；Owner 决策未决 → 生成 Decision Request 后继续无依赖部分）
- **本快照**：合同盘点（V1.x arbitrage 盘点 + 字段候选表 + Decision Matrix + Freeze 文档 + Decision Request）
- **建表门禁**：Owner 未签 → API/DDL/Service 全部 CONTRACT_GAP/FAIL_CLOSED，不建表

## 背景

S01-P07 已交付 Affiliate/Agent 合同盘点。本包（S01-P08）进入 AI Operations P1 领域（内部套利引擎边界），先做合同盘点，不建表。

V1.x 用 `arbitrage_*` 表族（`arbitrage_signal`/`arbitrage_signal_raw`/`arbitrage_fixture`/`arbitrage_attempt`/`arbitrage_day_plan`/`arbitrage_position`/`arbitrage_project*`）实现"矿机套利"模式。V6.1 要求三对象分离，边界收紧为 **内部 AI Operations**，C 端不得暴露套利细节。

## 固定对象与业务边界

```text
AISignal        — AI 信号（供应商行情归一化后内部信号）
AIRecommendation — AI 推荐（基于 signal 的内部推荐/解释）
SimulationRun   — 模拟运行（确定性回放/验证内部引擎）
```

**业务边界（07 §S01-P08 + 02 §5.4 + 02 §11 + 07 §1136）**：

```text
- BetBurger/API-Football 仅内部输入
- C 端不得返回 arbitrage signal、profit、position 或供应商原始 payload
- C 端不显示成「套利固定收益」（02 §5.4）
- AI budget → Prediction = FORBIDDEN；Prediction funds → AI budget = FORBIDDEN（02 §11）
- V1.x arbitrage_* 为内部输入/历史归档，禁止 C 端；不迁移、不沿用硬编码 secret
```

## 范围（快照 1 交付物）

```text
.project-ai/tasks/TASK-20260816-007/{requirement,design,acceptance}.md
sql/MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md        合同 Freeze 文档（候选）
.project-ai/tasks/TASK-20260816-007/decision_request.md Owner Decision Request
```

V1.x arbitrage 盘点表（KEEP_INTERNAL/ADAPT/RETIRE/FORBIDDEN_TO_EXPOSE）+ 字段候选表 + Decision Matrix 并入 design.md / Freeze 文档。

## 非目标（NON_GOALS）

- 不建表（Owner 未签）。
- 不改动 V1.x `arbitrage_*` 代码（只读盘点，不删除、不沿用硬编码 secret）。
- 不实现任何 AI 信号采集/推荐/模拟执行写流程（属 STAGE-02 / 合同 FROZEN 后）。
- 不自动继承 V1.x 矿机套利语义（`arbitrage_project*` 分销/矿机模式不迁移）。
- 不把内部套利数据暴露到 C 端。

## 固定步骤映射（07 §S01-P08）

| 步骤 | 内容 | 本快照是否执行 |
|---|---|---|
| 1 | 盘点 V1.x `library/{model,dao,service}/arbitrage` 与配置，输出 KEEP_INTERNAL/ADAPT/RETIRE/FORBIDDEN_TO_EXPOSE；不删旧代码、不沿用硬编码 secret | ✅ |
| 2 | AISignal 字段候选（来源引用/received/observed time/payload hash/quality/source status/dedupe/retention/PII-secret 分类） | ✅ |
| 3 | AIRecommendation 字段候选（signal refs/model/rule/parameter version/explanation/reason/有效期/INTERNAL_ONLY 边界） | ✅ |
| 4 | SimulationRun 字段候选（input snapshot/seed/version/start/end/status/metrics/failure reason/artifact hash/审计） | ✅ |
| 5 | Decision Matrix（状态/retention/供应商许可/writer/重试/幂等/预算连接/模型版本）；未签不建表 | ✅ |
| 6 | 合同 FROZEN 后建 DDL/Model/DAO/Service/command，serializer deny 内部字段 | ⏳（快照 2） |
| 7 | 负向测试（payload 不泄露/去重/stale source/simulation 可复现/无 Active Parameter fail-closed） | ⏳（快照 2） |

## 交接声明

```text
OPEN_OWNER_DECISION = YES（状态/retention/供应商许可/writer/模型版本等，见 decision_request.md）
CONTRACT_GAP = YES（Owner 未签前，AISignal/AIRecommendation/SimulationRun 不建表不建 Service）
C_ENDPOINT_INTERNAL_LEAK = FORBIDDEN（arbitrage signal/profit/position/payload 禁止 C 端）
V1X_ARBITRAGE_READONLY = YES（只读盘点，不删除、不迁移、不沿用 secret）
```

## 信息来源

- `01_PRODUCT_FUNCTIONAL_BASELINE.md` §51 P1（AI Signal 详情页，先留结构）
- `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md` §5.4 内部 AI 经济引擎、§11 Prediction 与 AI 隔离
- `06_PARAMETER_DICTIONARY.md` §4 AI/Robot（TBC）
- `07_DEVELOPMENT_AND_ACCEPTANCE.md` S01-P08、§1136
- V1.x 代码 `_existing_prod/gainode_api/library/{model,dao,service}/arbitrage/*` 与 `database.sql` 的 `arbitrage_*` 表
