# Machine Contract 第一批 — Canonical State Freeze

> 状态：**CANDIDATE（已交付，待 Independent Review）**
> 说明：本文件为 Freeze 候选，**未经 Independent Review 通过前不得作为 approved frozen baseline 使用**。审核通过后由治理流程置为正式 `FROZEN`。
> 交付日期：2026-08-13
> 关联 DDL：`0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql`
> 权威契约：`Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3 对象最低字段 + §4 统一状态机）
> 治理依据：`manifest.yaml` decisionSources `p1_003_two_phase_freeze`（OWNER_DIRECTIVE 2026-08-12「同意分两批」）

## 1. 冻结范围

本批冻结 STAGE-01 前置的 **8 个核心实体** 的数据库结构（DDL）与其领域状态枚举。冻结后，以下实体的状态枚举与 05 §4 canonical 严格一致，修改需走 §6 变更流程。

| # | 表名 | 实体 | 状态列 | 是否有状态机 |
|---|---|---|---|---|
| 1 | `apt_accounts` | APT 数量账主账号 | — | 无（余额 scalar） |
| 2 | `apt_ledger_entries` | APT 账本分录 | `state` | 有 |
| 3 | `robots` | Robot | `status` | 有 |
| 4 | `robot_rewards` | AI Reward | `state` | 有 |
| 5 | `prediction_markets` | 预测市场 | `market_status` | 有 |
| 6 | `prediction_orders` | 预测订单 | `order_status` | 有 |
| 7 | `otc_orders` | OTC 订单 | `status` | 有 |
| 8 | `power_positions` | Power 持仓 | — | 无（scalar fields） |

## 2. Canonical State 追溯表（实体 ↔ 05 §4 ↔ DDL）

> 规则：**Domain State 全部来自 05 §4 canonical enum，无自创状态。**

| 实体 | 状态列 | Canonical Enum（冻结） | 05 §4 出处 | DDL 类型 |
|---|---|---|---|---|
| Robot | `robots.status` | `inactive / active / cooling / review / restricted / paused` | 05:740 | `ENUM(...)` DEFAULT `inactive` |
| AI Reward | `robot_rewards.state` | `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed` | 05:743 | `ENUM(...)` DEFAULT `candidate` |
| Ledger Entry | `apt_ledger_entries.state` | `pending / posted / reversed / disputed` | 05:746 | `ENUM(...)` DEFAULT `pending` |
| Market | `prediction_markets.market_status` | `draft / open / closing / locked / awaiting_result / settlement / settled / void / exception` | 05:749 | `ENUM(...)` DEFAULT `draft` |
| Prediction Order | `prediction_orders.order_status` | `submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected` | 05:758 | `ENUM(...)` DEFAULT `submitted` |
| OTC Order | `otc_orders.status` | `draft / review / matching / partial / completed / cancelled / expired / rejected / disputed` | 05:761 | `ENUM(...)` DEFAULT `draft` |
| Power | `power_positions.*` | **无状态机** — 使用 scalar fields（available/frozen/consumed_period/released_period/recovering/limit） | 05:151-166 | `decimal(18,4)` |

### 状态语义要点（05 约束）

- **Robot**：`cooling` = 连续运行后冷却期，禁止立即重启；`review` = 风控/异常审计锁定；`restricted` = 策略受限运行；`paused` = 管理员手动暂停。
- **AI Reward**：`candidate`（预算内待确认）→ `held`（已记账不可提）→ `pending_claim` → `claiming`（防重）→ `claimed`；`expired_returned`（退回预算池）；`review`（风控冻结）；`reversed`（财务冲正，生成 reversal entry）。
- **Ledger**：`pending / posted / reversed / disputed`。append-only，`reversed` 通过 `reversal_of` 追加反向分录，不覆盖原文；`state` 是唯一可变列（详见 §3.6）。
- **Market**：`settlement`（结算处理中）≠ `settled`（已结算）；`void`（作废，赛事取消是原因之一）；`exception`（异常）。
- **Prediction Order**：`RESULT_UNKNOWN` 不得混入订单状态；`correcting / corrected` 仅在 settlement error 时触发。
- **OTC**：`completed` = 完整成交；`cancelled` = 用户/系统主动取消；`expired` = 有效期自然到期（非取消）；`partial + cancelled/expired` 仅释放 remaining；`disputed` 保持冻结直到处置。不删除/覆盖历史 Trade、APT Ledger、Power Ledger。

## 3. 设计决策（本批冻结的工程约定）

### 3.1 主键：Snowflake ID（bigint unsigned），禁用 AUTO_INCREMENT
依据 `rules/coding.md` 数据库规则第 7 条。8 张表主键均为业务语义 ID（`account_id / ledger_entry_id / robot_id / reward_id / market_id / order_id / otc_order_id / user_id`），由应用层 Snowflake（`godruoyi/php-snowflake`）生成。

### 3.2 user_id 类型：bigint unsigned
`member_user.id` 当前为 `int`（V1.x 自增），V2.0 新表 `user_id` 统一 `bigint unsigned`，为未来 `member_user.id` 加宽/换 Snowflake 预留兼容（bigint 可容纳 int 值）。加宽动作属 V1.x 数据迁移范畴，不在本批执行。

### 3.3 领域状态用 ENUM，不用 V1.x 的 `status tinyint` 软删
V2.0 核心实体以 **ENUM 冻结领域状态**，取代 V1.x 的 `status tinyint(1=正常,-1=删除)` 软删模式。作废/删除语义由领域状态表达（`void / cancelled / reversed / expired` 等）；历史对象不可物理删除。

### 3.4 金额精度：string decimal
- APT 数量类字段（balance / quantity / amount / capacity / fee）：`decimal(36,18)`（18 位小数、18 位整数，整数上限约 1e18；APT_MAX_SUPPLY = 1e11 有约 7 个数量级余量）。
- 系数/比率（`daily_reward_coefficient`、`price`）：`decimal(18,8)`。
- Power 数量：`decimal(18,4)`。
- 以上精度为阶段一默认，**待生产参数批准后可收窄**（见 §5 TBC）。

### 3.5 时间戳：created_time / updated_time（int unsigned，Unix 秒）
沿用 V1.x 约定与 `support/extend/Model` 基类（`CREATED_AT='created_time'`、`UPDATED_AT='updated_time'`、`dateFormat='U'`）。05 §3 对象字段中的 `created_at / updated_at` 为语义标签，映射到本项目的 `created_time / updated_time`。

### 3.6 账本 append-only 与状态流转（闭合账本 mutation 语义）
`apt_ledger_entries` 采用 **append-only + 受控状态流转**，字段分两类：

- **不可变字段（禁止 UPDATE/DELETE）**：`ledger_entry_id / account_id / asset / quantity / entry_direction / entry_type / source_object_type / source_object_id / journal_batch_id / reversal_of / idempotency_key / rule_version / snapshot_id / created_time`。这些是经济事实，一经写入永不覆盖，物理删除被禁止。
- **可变字段（仅随 state 流转成对更新）**：`state`（`pending / posted / reversed / disputed`）与 `audit_event_id`（指向本次流转最新审计证据的游标）。二者仅由 Ledger 模块唯一 Authoritative Writer（`LedgerService`）在同一事务内流转；`audit_event_id` 不承载历史，只是「最新审计证据」的指针，**完整历史由 append-only 审计事件表保留（见下）**。
- **每次 state 流转的审计证据（闭合契约）**：状态流转 `pending→posted`、`pending→disputed`、`*→reversed` 的每一笔，必须同时满足：**(a)** 向 append-only 审计事件表**追加一条不可变新记录**（以 `ledger_entry_id` 关联、顺序可重建），并把 `audit_event_id` 更新为该新事件指针——**不得原地覆盖/删除旧审计事件**；**(b)** `state`/`audit_event_id` 更新与审计事件追加在同一 DB 事务内原子完成；**(c)** 审计事件记录触发者、生效规则 `rule_version`、参数快照 `snapshot_id`；**(d)** 除 `state`/`audit_event_id` 外不改动任何经济字段。因此可完整重建时间线：`entry created → state change #1 → state change #2 → … → current state`，不会退化为「只剩最后一次 audit_event_id」。任何缺少审计证据的流转 = 违约，`LedgerService` MUST FAIL_CLOSED（拒绝写入）。审计事件表 schema 待 Event Catalog / Ledger Mutation Contract 阶段正式冻结（当前以 append-only 审计事件 + `ledger_entry_id` 关联为硬约束，不落具体表 DDL）。
- 本表**无 `updated_time` 列**；`state` 流转的时序证据由 `audit_event_id` 指向的审计事件承载，不依赖行内时间戳。
- **冲正（reversal）**：一律创建新分录，`reversal_of` 指向原分录；原分录不删除、不覆盖。
- **CONTRACT GAP（待冻结）**：05 §4 仅定义 Ledger canonical enum，未定义精确状态转移矩阵（`pending→posted/disputed` 触发条件、dispute 仲裁、reversal 触发条件）。在 Event Catalog / Ledger Mutation Contract 冻结前，Ledger 状态流转保持 **FAIL_CLOSED**；Service 层不得自行发明转移规则。

Model 层须设 `$timestamps=false`（或等价配置）以杜绝误写 `updated_time`。

### 3.7 并发控制与幂等
- 每张表含 `object_version int unsigned`（乐观锁，对应 05 的 If-Match）。
- 每张表含 `idempotency_key varchar(64)`（UNIQUE，可空），对应 05 写操作幂等。
- 敏感写操作表（ledger/orders/rewards）含 `audit_event_id`。

### 3.8 数据库归属
8 张表建在默认主库 `webman`（`config/database.php` 的 `mysql` 连接）。`gainode` 库为「未来账本独立」预留——`apt_accounts` / `apt_ledger_entries` 后续若要迁入 `gainode` 库，需单独迁移决策，不在本批。

### 3.9 文件组织
8 实体以**单文件**交付（`20260813_machine_contract_batch1_8_core_entities.sql`），原因：同一「Machine Contract 第一批」为原子冻结单元，单文件便于一次性事务迁移与整包独立审核。STAGE-01 各模块（10 模块）的增量表仍按「每表一个 `YYYYMMDD_description.sql`」执行。

### 3.10 migration 路径与执行方式
- 本批 DDL 位于 bootstrap 冻结的 migration 路径：`0.5代码/gainode后端/gainode/sql/`（`MIGRATION_PATH = sql/YYYYMMDD_description.sql` 相对后端根目录）。
- **forward-only**：文件不含任何 `DROP TABLE`；首次执行创建 8 表，目标表已存在则 fail-fast，绝不删除已有数据；重跑判定走 `information_schema` / migration version。
- 资产归属：本批 `apt_ledger_entries.asset` 仅 `APT-I`；`APT-C` 为 Future/OUT_OF_SCOPE，须经正式 Product/Contract Change 方可扩展（`apt_accounts` 的 `balance_apt_c/frozen_apt_c` 仅为 Future 余额结构预留，不代表开通 APT-C 记账能力）。

## 4. 未冻结 / 延后项（不阻塞本批，明确 FAIL_CLOSED）

| 字段 | 表 | 处理 |
|---|---|---|
| `asset_status` / `risk_status` | `prediction_orders` | 05 §4 未定义枚举值，列为 `varchar(32) NULL`，TBC。业务上 FAIL_CLOSED，待 Contract Freeze 后改为 ENUM |
| `result_status` | `prediction_markets` | Result 是独立对象（05 §4 Result: provisional/official/disputed/corrected）。本表仅作投影列，独立 `results` 表在 Prediction 模块阶段建立 |
| `entry_type` / `entry_direction` | `apt_ledger_entries` | 05 未定义取值，暂以 `varchar(64)` + `tinyint` 表达，与 Event Catalog 对齐后冻结 |
| `selections` / `liquidity_summary` / `capabilities` / `allowed_actions` | 多表 | 用 `json` 承载，结构由服务端下发，不在此处冻结具体 JSON schema |
| Power 精确消耗/恢复规则 | `power_positions` | 由 Active Rule/Parameter 决定，生产参数未批准 |
| Ledger 状态转移矩阵（dispute/reversal 触发） | `apt_ledger_entries.state` | CONTRACT GAP，待 Event Catalog / Ledger Mutation Contract 冻结 |

## 5. TBC（生产参数，非缺陷）

- 各 `decimal` 精度（36,18 / 18,8 / 18,4）为阶段一默认，生产参数批准后收窄。
- `template_id` 仅冻结 `FOOTBALL_PREMATCH_1X2`（P0）；P1 YES/NO/XP 模板后续新增。
- Snowflake worker_id/datacenter_id 分配方案未冻结。

## 6. 变更控制

本批冻结后修改任何实体的**状态枚举**或**字段语义**，必须：
1. 走 05 契约变更流程（先改 05 §3/§4，再改 DDL）；
2. 更新本 Freeze 文档版本号；
3. 变更 DDL 以**新增日期文件**形式提交（不改历史 dated SQL，保留审计轨迹，`rules/coding.md` 数据库规则第 6 条）；
4. 重新触发 Independent Review（State Machine gate）。

## 7. 验收对照（TASK-20260812-002 acceptance）

- [x] DB DDL（8 核心实体）已创建（forward-only，无 DROP）
- [ ] Canonical State Freeze 正式 FROZEN（本文件为 CANDIDATE，待 Independent Review 通过后由治理流程置 FROZEN）
- [x] 8 实体状态枚举与 05 canonical 一致（6 组状态机 + 2 个 scalar）
- [x] DDL 文件遵循日期命名约定（`20260813_machine_contract_batch1_8_core_entities.sql`）
- [x] DDL 文件顶部有变更原因和影响范围注释
