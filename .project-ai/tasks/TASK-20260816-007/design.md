# Design: S01-P08 AI Operations P1 合同盘点（快照 1）

## 状态

- **本快照**：合同盘点（不建表）。Owner 未签 → 三对象 CONTRACT_GAP/FAIL_CLOSED。
- **冻结状态**：三对象合同均未冻结；V1.x `arbitrage_*` 为只读盘点对象，不继承矿机套利语义、不沿用硬编码 secret。

## 1. V1.x arbitrage 盘点（步骤 1）

结论：KEEP_INTERNAL / ADAPT / RETIRE / FORBIDDEN_TO_EXPOSE。

| V1.x 表 | 结论 | 说明 |
|---|---|---|
| `arbitrage_signal` | ADAPT | AISignal 字段候选来源（event_id/arb_hash 幂等/leg1-leg2/profit_rate/status）；不继承矿机语义 |
| `arbitrage_signal_raw` | ADAPT | 供应商原始 payload 冷存（AISignal 的 raw payload 来源）；不迁移，仅结构参考 |
| `arbitrage_fixture` | KEEP_INTERNAL | 比赛主数据（source/source_id/kickoff/status_short），内部行情层 |
| `arbitrage_attempt` | KEEP_INTERNAL | 引擎尝试日志（exec_status/stake/profit_rate/detail），SimulationRun 概念来源 |
| `arbitrage_day_plan` | KEEP_INTERNAL | 内部调度计划，禁止 C 端 |
| `arbitrage_position` | KEEP_INTERNAL | 内部仓位，禁止 C 端（FORBIDDEN_TO_EXPOSE） |
| `arbitrage_project` | RETIRE | V1.x 矿机项目模式，V6.1 废弃，不迁移 |
| `arbitrage_project_order*` | RETIRE | V1.x 矿机订单/分销，V6.1 废弃，不迁移 |

**secret 处理**：V1.x 中硬编码的 BetBurger/API-Football secret 不沿用；V6.1 迁移到 `.env`，缺失 fail-closed。

## 2. 字段候选表（步骤 2/3/4）

标记：`SOURCE_CONFIRMED` / `OWNER_DECISION_REQUIRED`。

### 2.1 AISignal（AI 信号）

| 字段 | 类型 | 来源 | 标记 |
|---|---|---|---|
| signal_id | Snowflake bigint（主键） | 工程约束 | SOURCE_CONFIRMED |
| source_ref | varchar（数据来源引用，BetBurger/API-Football） | 07 §S01-P08 | SOURCE_CONFIRMED |
| received_at / observed_at | int（接收/观测时间戳） | 07 §S01-P08 | SOURCE_CONFIRMED |
| normalized_payload_hash | varchar(64)（归一化 payload 哈希） | 07 §S01-P08 | SOURCE_CONFIRMED |
| source_status | enum（quality/source 状态） | 07 §S01-P08 | OWNER_DECISION_REQUIRED |
| dedupe_key | varchar(64) UNIQUE（去重） | 07 §S01-P08 + V1.x arb_hash | SOURCE_CONFIRMED |
| retention_until | int（保留期限） | 07 §S01-P08 | OWNER_DECISION_REQUIRED |
| pii_secret_classification | enum（PII/secret 分类） | 07 §S01-P08 | OWNER_DECISION_REQUIRED |
| status | enum | 07 §S01-P08 | OWNER_DECISION_REQUIRED |
| rule_version | varchar | 06 §4 | SOURCE_CONFIRMED |
| object_version | int unsigned（乐观锁） | 工程约束 | SOURCE_CONFIRMED |
| created_time / updated_time | int（Unix 秒） | 02 §14 | SOURCE_CONFIRMED |

### 2.2 AIRecommendation（AI 推荐）

| 字段 | 类型 | 来源 | 标记 |
|---|---|---|---|
| recommendation_id | Snowflake bigint（主键） | 工程约束 | SOURCE_CONFIRMED |
| signal_refs | varchar/text（输入 signal 引用） | 07 §S01-P08 | SOURCE_CONFIRMED |
| model_version | varchar（模型/规则/参数版本） | 07 §S01-P08 | OWNER_DECISION_REQUIRED |
| explanation | text（解释） | 07 §S01-P08 | SOURCE_CONFIRMED |
| safe_reason | varchar（安全 reason，对外脱敏） | 07 §S01-P08 | SOURCE_CONFIRMED |
| valid_until | int（有效期） | 07 §S01-P08 | SOURCE_CONFIRMED |
| output_boundary | enum（INTERNAL_ONLY） | 07 §S01-P08 | SOURCE_CONFIRMED |
| status | enum | 07 §S01-P08 | OWNER_DECISION_REQUIRED |
| rule_version | varchar | 06 §4 | SOURCE_CONFIRMED |
| object_version | int unsigned | 工程约束 | SOURCE_CONFIRMED |
| created_time / updated_time | int | 02 §14 | SOURCE_CONFIRMED |

### 2.3 SimulationRun（模拟运行）

| 字段 | 类型 | 来源 | 标记 |
|---|---|---|---|
| run_id | Snowflake bigint（主键） | 工程约束 | SOURCE_CONFIRMED |
| input_snapshot | text/json（deterministic input snapshot） | 07 §S01-P08 | SOURCE_CONFIRMED |
| seed | varchar（seed/version） | 07 §S01-P08 | SOURCE_CONFIRMED |
| started_at / ended_at | int（start/end 时间戳） | 07 §S01-P08 | SOURCE_CONFIRMED |
| status | enum | 07 §S01-P08 | OWNER_DECISION_REQUIRED |
| metrics | text/json（metrics） | 07 §S01-P08 | SOURCE_CONFIRMED |
| failure_reason | varchar（失败原因） | 07 §S01-P08 | SOURCE_CONFIRMED |
| artifact_hash | varchar(64)（artifact hash） | 07 §S01-P08 | SOURCE_CONFIRMED |
| audit_ref | varchar（审计引用） | 07 §S01-P08 + 02 §14 | SOURCE_CONFIRMED |
| rule_version | varchar | 06 §4 | SOURCE_CONFIRMED |
| object_version | int unsigned | 工程约束 | SOURCE_CONFIRMED |
| created_time / updated_time | int | 02 §14 | SOURCE_CONFIRMED |

## 3. Decision Matrix（步骤 5，全部 OWNER_DECISION_REQUIRED）

| # | 决策点 | 候选/依据 | 默认（fail-closed） | 状态 |
|---|---|---|---|---|
| D1 | AISignal 状态枚举 | 未冻结；候选 active/expired/consumed/closed/invalid（对齐 V1.x status 1/2/3/4/5） | 未签 → 不建状态机 | OWNER_DECISION_REQUIRED |
| D2 | AIRecommendation 状态枚举 | 未冻结；候选 draft/active/expired/superseded | 未签 → 不建状态机 | OWNER_DECISION_REQUIRED |
| D3 | SimulationRun 状态枚举 | 未冻结；候选 pending/running/completed/failed/cancelled | 未签 → 不建状态机 | OWNER_DECISION_REQUIRED |
| D4 | retention 期限 | 未定义；供应商信号/raw payload 保留多久 | 未签 → retention_until NULL（无限保留禁用） | OWNER_DECISION_REQUIRED |
| D5 | 供应商许可 | BetBurger/API-Football 许可范围/再分发限制 | 未签 → 供应商输入 INTERNAL_ONLY | OWNER_DECISION_REQUIRED |
| D6 | Authoritative Writer | AISignal/AIRecommendation/SimulationRun 各自 writer 角色 | 未签 → 无 writer，禁写 | OWNER_DECISION_REQUIRED |
| D7 | 重试/幂等 | 信号去重、模拟重试语义 | 未签 → dedupe_key 强制唯一，无自动重试 | OWNER_DECISION_REQUIRED |
| D8 | 预算连接 | AI Reward Budget 与 internal 经济引擎连接（02 §5.4） | 未签 → 预算连接关闭，禁映射 | OWNER_DECISION_REQUIRED |
| D9 | 模型版本 | model/rule/parameter version 管理 | 未签 → model_version NULL，禁推理 | OWNER_DECISION_REQUIRED |
| D10 | C 端输出边界 | 内部字段 deny 策略 | 固定 FORBIDDEN（非 Owner 决策，直接锁定） | LOCKED |

## 4. 关键不变量（快照 1）

```text
NOT_PERSISTED_YET = YES（三对象 Owner 未签，不建表不建 Service）
C_ENDPOINT_INTERNAL_LEAK = FORBIDDEN（signal/profit/position/payload 禁止 C 端）
V1X_ARBITRAGE_READONLY = YES（只读盘点，不删除、不迁移、不沿用 secret）
V1X_MINER_MODE_RETIRED = YES（arbitrage_project* 矿机/分销不迁移）
OWNER_DECISION_COUNT = 9（D1~D9）
AI_PREDICTION_BUDGET_ISOLATION = YES（02 §11，双向 FORBIDDEN）
```

## 5. 快照 2 预告（合同 FROZEN 后，本快照不执行）

```text
顺序：AISignal → AIRecommendation → SimulationRun
所有对外 serializer 明确 deny 内部字段
负向测试：payload 不泄露/重复 signal 去重/stale source/simulation 可复现/无 Active Parameter fail-closed
```
