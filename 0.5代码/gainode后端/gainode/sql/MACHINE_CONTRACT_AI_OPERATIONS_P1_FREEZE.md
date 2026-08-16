# Machine Contract — AI Operations P1 合同 Freeze（候选）

> 状态：**CANDIDATE（未 FROZEN）** — Owner Signoff 未签（9 项决策 D1~D9 待裁决）；Independent Review 未开始。
> 说明：本文件为 AI Operations P1（AISignal / AIRecommendation / SimulationRun 三对象）的合同冻结候选。Owner 未签前，三对象 **CONTRACT_GAP/FAIL_CLOSED，不建表、不建 Service**；C 端泄露边界 FORBIDDEN；不继承 V1.x 矿机套利语义、不沿用硬编码 secret。
> 起草日期：2026-08-16
> 关联 DDL：无（本快照为合同盘点，不生成 DDL）
> 权威契约：`Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3/§4/§8/§11）、`02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`（§5.4/§11）、`06_PARAMETER_DICTIONARY.md`（§4）
> 前置冻结：MC1、MC2、2B-1、2B-2
> 任务：`.project-ai/tasks/TASK-20260816-007/`

## 1. 冻结范围

本批冻结 **AI Operations P1 三对象**的合同候选（字段 + 状态候选）。Owner 签后，三对象的状态流转由本文件授权，非法流转 FAIL_CLOSED。

| 对象 | 类型 | 说明 | 状态 |
|---|---|---|---|
| AISignal | 内部领域实体（AI 信号） | 供应商行情归一化后内部信号 | 候选（enum/字段待 Owner 签） |
| AIRecommendation | 内部领域实体（AI 推荐） | 基于 signal 的内部推荐/解释 | 候选（enum/字段待 Owner 签） |
| SimulationRun | 内部领域实体（模拟运行） | 确定性回放/验证内部引擎 | 候选（enum/字段待 Owner 签） |

**不包含**（拆出本快照，另行交付）：
- 三对象的 DDL / Model / DAO / Service / command（属快照 2，Owner 签后）。
- AI 信号采集/推荐/模拟执行写流程（属 STAGE-02）。
- V1.x `arbitrage_*`（只读盘点对象，不删除、不修改、不迁移矿机语义、不沿用 secret）。

## 2. V1.x arbitrage 盘点结论（KEEP_INTERNAL/ADAPT/RETIRE/FORBIDDEN_TO_EXPOSE）

| V1.x 表 | 结论 |
|---|---|
| `arbitrage_signal` | ADAPT（AISignal 字段候选来源，不继承矿机语义） |
| `arbitrage_signal_raw` | ADAPT（供应商原始 payload 冷存，不迁移） |
| `arbitrage_fixture` | KEEP_INTERNAL |
| `arbitrage_attempt` | KEEP_INTERNAL（SimulationRun 概念来源） |
| `arbitrage_day_plan` | KEEP_INTERNAL |
| `arbitrage_position` | KEEP_INTERNAL（FORBIDDEN_TO_EXPOSE） |
| `arbitrage_project` | RETIRE（矿机模式废弃） |
| `arbitrage_project_order*` | RETIRE（矿机订单/分销废弃） |

## 3. 字段合同（候选）

标记：`SOURCE_CONFIRMED` / `OWNER_DECISION_REQUIRED`。

### 3.1 AISignal

| 字段 | 类型 | 标记 |
|---|---|---|
| signal_id | Snowflake bigint unsigned（主键） | SOURCE_CONFIRMED |
| source_ref | varchar（数据来源引用） | SOURCE_CONFIRMED |
| received_at / observed_at | int unsigned（Unix 秒） | SOURCE_CONFIRMED |
| normalized_payload_hash | varchar(64) | SOURCE_CONFIRMED |
| source_status | enum（待 D1 裁决） | OWNER_DECISION_REQUIRED |
| dedupe_key | varchar(64) UNIQUE | SOURCE_CONFIRMED |
| retention_until | int unsigned（待 D4 裁决） | OWNER_DECISION_REQUIRED |
| pii_secret_classification | enum（待 Owner 裁决） | OWNER_DECISION_REQUIRED |
| status | enum（待 D1 裁决） | OWNER_DECISION_REQUIRED |
| rule_version | varchar | SOURCE_CONFIRMED |
| object_version | int unsigned（乐观锁） | SOURCE_CONFIRMED |
| created_time / updated_time | int unsigned（Unix 秒） | SOURCE_CONFIRMED |

### 3.2 AIRecommendation

| 字段 | 类型 | 标记 |
|---|---|---|
| recommendation_id | Snowflake bigint unsigned（主键） | SOURCE_CONFIRMED |
| signal_refs | varchar/text | SOURCE_CONFIRMED |
| model_version | varchar（待 D9 裁决） | OWNER_DECISION_REQUIRED |
| explanation | text | SOURCE_CONFIRMED |
| safe_reason | varchar（对外脱敏） | SOURCE_CONFIRMED |
| valid_until | int unsigned | SOURCE_CONFIRMED |
| output_boundary | enum（INTERNAL_ONLY，锁定） | SOURCE_CONFIRMED |
| status | enum（待 D2 裁决） | OWNER_DECISION_REQUIRED |
| rule_version | varchar | SOURCE_CONFIRMED |
| object_version | int unsigned | SOURCE_CONFIRMED |
| created_time / updated_time | int unsigned | SOURCE_CONFIRMED |

### 3.3 SimulationRun

| 字段 | 类型 | 标记 |
|---|---|---|
| run_id | Snowflake bigint unsigned（主键） | SOURCE_CONFIRMED |
| input_snapshot | text/json | SOURCE_CONFIRMED |
| seed | varchar | SOURCE_CONFIRMED |
| started_at / ended_at | int unsigned | SOURCE_CONFIRMED |
| status | enum（待 D3 裁决） | OWNER_DECISION_REQUIRED |
| metrics | text/json | SOURCE_CONFIRMED |
| failure_reason | varchar | SOURCE_CONFIRMED |
| artifact_hash | varchar(64) | SOURCE_CONFIRMED |
| audit_ref | varchar | SOURCE_CONFIRMED |
| rule_version | varchar | SOURCE_CONFIRMED |
| object_version | int unsigned | SOURCE_CONFIRMED |
| created_time / updated_time | int unsigned | SOURCE_CONFIRMED |

## 4. 状态合同（候选，未 FROZEN）

> Owner 未签 → 三对象 status enum 未冻结。以下为候选 enum（对应 decision_request D1/D2/D3 RECOMMENDED_OPTION），**正式 FROZEN 前不生效，状态机全部 FAIL_CLOSED**。

### 4.1 AISignal（候选 enum：`active / expired / consumed / closed / invalid`，D1 OPTION_A）

```text
初态 = active
候选合法转移 = active→expired（过期）/ active→consumed（已用尽）/ active|expired|consumed→closed（管理关闭）/ active→invalid（数学校验失败）
终态 = closed / invalid / consumed
触发者 = 系统（采集/校验/过期）
Writer = AISignalService（Owner 签后）
失败态 = invalid
重试 = 无（新信号覆盖，dedupe_key upsert）
幂等 = dedupe_key 唯一（upsert）
审计 = append audit_events
账本副作用 = 无
```

### 4.2 AIRecommendation（候选 enum：`draft / active / expired / superseded`，D2 OPTION_A）

```text
初态 = draft
候选合法转移 = draft→active / active→expired（过期）/ active→superseded（被新版本取代）
终态 = expired / superseded
触发者 = 系统（生成/过期）
Writer = AIRecommendationService（Owner 签后）
失败态 = 无
重试 = 无
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = 无（正式经济计算属 STAGE-02）
```

### 4.3 SimulationRun（候选 enum：`pending / running / completed / failed / cancelled`，D3 OPTION_A）

```text
初态 = pending
候选合法转移 = pending→running / running→completed / running→failed / pending|running→cancelled
终态 = completed / failed / cancelled
触发者 = 系统（调度/执行）
Writer = SimulationRunService（Owner 签后）
失败态 = failed
重试 = 无自动重试（显式重跑，D7）
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = 无（预算连接 D8 未签 → 关闭）
```

**FAIL_CLOSED（Owner 未签）**：三对象无合法转移；`status` 未定义前 `varchar(32) NULL` + 不建表、不写业务。

## 5. 跨对象协同与内部边界（候选，未 FROZEN）

| 规则 | 依据 |
|---|---|
| C 端不得返回 arbitrage signal、profit、position 或供应商原始 payload | 07 §S01-P08（LOCKED，D10） |
| C 端不显示「套利固定收益」 | 02 §5.4 |
| AI budget → Prediction = FORBIDDEN；Prediction funds → AI budget = FORBIDDEN | 02 §11 |
| BetBurger/API-Football 仅内部输入 | 07 §S01-P08（D5 候选） |
| 三对象 writer 为系统内部进程，禁 END_USER 写 | D6 候选 |
| 预算连接（02 §5.4 mapped_apt_budget）P1 不启用 | D8 候选 |
| model_version 复用 06 参数生命周期 | D9 候选 |

## 6. 通用工程约束（快照 2 建 DDL/骨架时落实）

| 维度 | 约束 |
|---|---|
| 主键 | Snowflake bigint unsigned（`$incrementing=false`，`$keyType='string'`） |
| 并发 | 每表 `object_version int unsigned` CAS 乐观锁 |
| 幂等 | 每表 `idempotency_key varchar(64) UNIQUE`；AISignal 额外 `dedupe_key` 唯一 |
| 审计 | 敏感写表 `audit_event_id` 指针 + append `audit_events` |
| 金额 | 内部金额用 decimal string（禁 float） |
| 时间 | `created_time/updated_time` int unsigned（Unix 秒） |
| 状态列 | 领域状态用 ENUM（冻结 enum 后），05 未定义前 `varchar(32) NULL` + FAIL_CLOSED |
| 内部边界 | 所有对外 serializer 明确 deny 内部字段（signal/profit/position/payload） |
| 失败安全 | 转移矩阵未冻结一律 FAIL_CLOSED，不建表、不写业务 |

## 7. 冻结状态与 gate

```text
OWNER_SIGNOFF = PENDING（D1~D9 未签）
INDEPENDENT_REVIEW = PENDING
FROZEN_STATUS = CANDIDATE
AISIGNAL_ENUM = active/expired/consumed/closed/invalid（候选，D1）
AI_RECOMMENDATION_ENUM = draft/active/expired/superseded（候选，D2）
SIMULATION_RUN_ENUM = pending/running/completed/failed/cancelled（候选，D3）
OWNER_DECISION_MATRIX_COUNT = 9（D1~D9）
LOCKED_COUNT = 1（D10 C 端边界）
NO_SELF_INVENTED_STATE = YES（全部候选，未冻结）
NO_SELF_INVENTED_ROLE = YES（复用 05 §8）
CONTRACT_GAP = YES（Owner 未签前不建表不建 Service）
C_ENDPOINT_INTERNAL_LEAK = FORBIDDEN
NO_LEGACY_MINER_INHERITANCE = YES（arbitrage_project* 矿机/分销不迁移）
V1X_ARBITRAGE_UNTOUCHED = YES（只读盘点，不沿用 secret）
AI_PREDICTION_BUDGET_ISOLATION = YES
```

正式 FROZEN 前须：Owner 签 D1~D9 → 更新 05 / 06 → Freeze Candidate → Independent Review 通过 → FROZEN → 快照 2 建 DDL/Model/DAO/Service/command。

## 信息来源

- 01 §51 P1、02 §5.4/§11、06 §4、07 §S01-P08/§1136
- 05 §3/§4/§8/§11
- `.project-ai/tasks/TASK-20260816-007/{requirement,design,acceptance,decision_request}.md`
- V1.x `_existing_prod/gainode_api/library/{model,dao,service}/arbitrage/*` 与 `database.sql` `arbitrage_*` 表（只读盘点）
