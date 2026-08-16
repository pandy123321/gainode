# S02-P04 · Robot / Reward / Upgrade 基础 — 需求

> 项目：Gainode　工作区：`E:\github\sports`　阶段：STAGE-02　包：`S02-P04`
> 权威执行计划：`Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P04（V3.3 FROZEN_FOR_EXECUTION）
> 权威契约：`05_DATA_STATE_PERMISSION_API_CONTRACT.md` §3/§4；`sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（MC2 §3.2/§3.3，Robot R1–R12 / AI Reward W1–W10）；`06_PARAMETER_DICTIONARY.md` §4（AI/Robot）

## 1. 目标

落地 **Robot / AI Reward / Robot Upgrade 的只读投影 + 56 级规则读取器 + 状态机骨架**。56 级能力表（`standard_capacity`）、`daily_reward_coefficient`、`power_cap_by_robot_level`、升级成本（`upgrade_apt_requirement`）、Reward 预算/窗口等**全部由 Active Parameter Release / Snapshot 供给**（06 §4 全部 TBC）；无有效 Active Release 时，真实写操作（start / upgrade / reward posting / claim）一律 FAIL_CLOSED（`DEPENDENCY_UNAVAILABLE`），只读投影返回 `unavailable reason`，**不 mock、不写死前端、不用旧 Mining 值补洞**。

实现顺序固定：**Contract（状态机/冻结范围）→ 56 级规则读取器 → 状态机骨架（Service）→ 只读投影 → OpenAPI → Tests**。

## 2. 冻结范围与 FAIL_CLOSED 边界

### 2.1 可落地（FROZEN 只读 / 骨架 / Owner 裁决 + 07 §S02-P04 步骤）

| 项 | 内容 |
|---|---|
| 只读投影 | Robot `summary/detail/allowed_actions`（读 `robots` 表已建表字段 + 56 级规则读取器），无 Active Release 返回 `unavailable reason` |
| 56 级读取器 | `RobotRuleReader`：从 `parameter_snapshots` 读 `AI.standard_capacity_rule_version` / `AI.power_cap_by_robot_level` / `AI.upgrade_apt_requirement` / `AI.ai_reward_*` / `AI.daily_yield_coefficient_source`，无有效 Release → `source_status=UNAVAILABLE` |
| 状态机骨架 | Robot（R1–R12）、AI Reward（W1–W10）、Upgrade（pending→processing→completed/failed/cancelled）合法转移定义 + transition guard + 非法转移 FAIL_CLOSED |
| L1 集成 | Reward W1（candidate→held）若预算/资格快照可用则复用 S02-P03 经济事务模板（LedgerService.append/post + AptAccountService.applyEntryEffect）；否则 FAIL_CLOSED |

### 2.2 FAIL_CLOSED（未冻结依赖，06 §4 全 TBC）

| 项 | 原因（06 参数） | 行为 |
|---|---|---|
| Robot start（R1 `inactive→active`） | `AI.power_robot_start_consumption_rule` + `AI.power_cap_by_robot_level` TBC | `DEPENDENCY_UNAVAILABLE(503)` |
| Robot cooling（R2）阈值 | `AI.*` 冷却阈值 TBC | `DEPENDENCY_UNAVAILABLE(503)` |
| Robot review/restricted/paused（R4/R7/R9） | 风控引擎/受限范围 TBC | `DEPENDENCY_UNAVAILABLE(503)` |
| Reward posting（W1 `candidate→held`） | `AI.ai_reward_budget_cap` / `period_cap` + 资格快照 TBC | `DEPENDENCY_UNAVAILABLE(503)` |
| Reward claim（W3/W4） | `AI.ai_reward_claim_enabled`（默认 false）TBC | `DEPENDENCY_UNAVAILABLE(503)` |
| Reward expiry（W5）/ review（W7）/ reverse（W9/W10） | 窗口时长 / 风控 / 冲正审批（ApprovalRequest 未冻结）TBC | `DEPENDENCY_UNAVAILABLE(503)` |
| Upgrade（提交/处理） | `AI.upgrade_apt_requirement` + `AI.upgrade_allocation_profile` TBC | `DEPENDENCY_UNAVAILABLE(503)` |

## 3. 56 级规则读取器（07 §S02-P04 步骤 1）

`RobotRuleReader` 从 `parameter_snapshots`（append-only 只读聚合）解析 `AI.*` 参数：

- `standard_capacity_rule_version`：56 级能力表版本；无有效 Release → 规则不可用。
- `power_cap_by_robot_level`：`{level: power_cap}` 映射；前端不得写死。
- `upgrade_apt_requirement`：各等级升级成本/要求。
- `ai_reward_budget_cap` / `ai_reward_period_cap` / `ai_reward_hold_period` / `ai_reward_expiry_period` / `ai_reward_claim_enabled`。
- `daily_yield_coefficient_source` / `daily_yield_coefficient_precision`：当日系数来源与精度（0 是合法值）。

读取失败/无 Active Release 时：只读投影 `source_status=UNAVAILABLE` + `refresh_hint`；写操作 `DEPENDENCY_UNAVAILABLE`。

## 4. 状态机骨架（MC2 §3.2 / §3.3，转移矩阵已 Owner 裁决）

### 4.1 Robot — `inactive / active / cooling / review / restricted / paused`

- 合法转移 R1–R12（见 MC2 §3.2），无 TRUE_TERMINAL；`review/cooling/restricted` 为中间锁定态。
- `inactive→paused` 不合法（Owner 裁决）。
- **本包只落地骨架 + guard 定义**；R1/R2/R4/R7/R9 因规则 TBC → FAIL_CLOSED；R3（stop）、R6/R10（恢复）若依赖规则亦 FAIL_CLOSED。

### 4.2 AI Reward — `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed`

- 合法转移 W1–W10（见 MC2 §3.3）；`expired_returned`/`reversed` = TRUE_TERMINAL；`claimed` = STABLE_WITH_EXCEPTION（仅 W10）。
- `held→expired_returned` 直接路径不合法（Owner 裁决）。
- **本包只落地骨架 + guard 定义**；W1（posting）依赖预算/资格快照 → FAIL_CLOSED；W3/W4（claim）依赖 `claim_enabled` → FAIL_CLOSED。

### 4.3 RobotUpgradeOrder — `pending / processing / completed / failed / cancelled`

- 合法转移：pending→processing→completed；pending→processing→failed（可重试回 processing）；pending→cancelled。
- 大额人工确认 = OPS_OPERATOR + RISK_APPROVER（MC2 Owner 裁决 #13）。
- **本包只落地骨架 + guard 定义**；升级成本/资金去向 TBC → FAIL_CLOSED。

## 5. 关键约束与不变式

- **无固定收益 / 无前端计算**：`standard_capacity`、`daily_reward_coefficient`、`power_cap`、升级成本均来自 Active Release/Snapshot，前端只读展示。
- **三状态轴分离**：Robot、Upgrade、Reward 三轴独立，状态互不合并。
- **每次经济变化有 snapshot/ledger/audit**：Reward W1（held）若落地则复用 S02-P03 经济事务模板，同事务追加 ledger + audit。
- **超级管理员无旁路**：所有写路径复用状态机骨架 + 规则读取器。
- **decimal string + bcmath**：`standard_capacity` / `quantity_apt` / `apt_cost` / `power_cap` 一律字符串 + bcmath，禁 float。

## 6. 非目标（NON_GOALS）

- 不落地 56 级能力表的具体数值（`AI.standard_capacity_rule_version` TBC，不 mock 数值）。
- 不落地 Robot start 的 Power 消耗（`AI.power_robot_start_consumption_rule` TBC）。
- 不落地 Reward 的预算/资格/观察期/过期/冲正具体规则（`AI.ai_reward_*` TBC）。
- 不落地 Upgrade 的升级成本/资金去向（`AI.upgrade_apt_requirement` / `allocation_profile` TBC）。
- 不落地风控引擎标记、大额审批、RiskCase 协同（未冻结）。
- 不改 MC1/2B-1 已冻结 DDL（`robots`/`robot_rewards`/`robot_upgrade_orders` 表结构不变）。
- 不 push、不提审（Development Agent 职责边界）。

## 7. 交付物清单

- `library/service/robot/RobotRuleReader.php`（56 级规则读取器，无 Active Release fail-closed）。
- `library/service/robot/RobotService.php`（补 summary/detail/allowed_actions 投影 + start/stop/状态机骨架）。
- `library/service/robot/RobotRewardService.php`（补 hold/enterClaimWindow/claim/expire/review/reverse 状态机骨架）。
- `library/service/robot/RobotUpgradeOrderService.php`（补 quote/submit/process/complete/fail/cancel 状态机骨架）。
- `library/response/robot/`（RobotSummaryResponse / RobotDetailResponse / RobotRuleResponse）。
- `openapi/components/schemas/robot.yaml` + `openapi/paths/robot.yaml` + `openapi/gainode-v2.yaml` 注册。
- `tests/Contract/` + `tests/Integration/` 测试（状态机合法/非法转移、fail-closed、只读投影、56 级读取器）。
