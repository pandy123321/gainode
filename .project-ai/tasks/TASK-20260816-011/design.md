# S02-P04 · Robot / Reward / Upgrade 基础 — 设计

> 承接 `requirement.md`。实现顺序固定：Contract → 56 级读取器 → 状态机骨架 → 只读投影 → OpenAPI → Tests。

## 1. 架构总览

```
RobotRuleReader（56 级规则读取器，parameter_snapshots 只读聚合）
   │  └─ 依赖 ParameterReleaseDao::getActive()（status='active' 最新 release → snapshot_id）
   │       └─ ParameterSnapshotDao::getByRelease()（append-only，parameter_values JSON）
   │
RobotService（robots Authoritative Writer + 只读投影）
   │  ├─ 状态机骨架：enterCooling/exitCooling/lockReview/clearReview/restrict/
   │  │   liftRestriction/pause/resume/disable（审计 + object_version CAS）
   │  ├─ fail-closed：start（R1）/ stop（R3）
   │  └─ 只读投影：summary / detail / allowedActions（调 RobotRuleReader，无 Release → UNAVAILABLE）
   │
RobotRewardService（robot_rewards Authoritative Writer）
   │  ├─ 状态机骨架：openClaimWindow/startClaim/lockReview/clearReview
   │  └─ fail-closed：hold（W1）/ completeClaim（W4）/ expire（W5）/ reverse（W9,W10）
   │
RobotUpgradeOrderService（robot_upgrade_orders Authoritative Writer）
      ├─ fail-closed：quote / submit（升级成本/资金去向 TBC）
      └─ 状态机骨架：process / complete / fail / cancel（审计 + CAS）
```

## 2. 56 级规则读取器（RobotRuleReader）

**数据流**：`parameter_releases(status='active')` → `snapshot_id` → `parameter_snapshots.parameter_values`（JSON 键值对）→ 解析 `AI.*`。

**参数键（06 §4）**：`AI.standard_capacity_rule_version`、`AI.daily_yield_coefficient_source`、`AI.daily_yield_coefficient_precision`、`AI.ai_reward_budget_cap`、`AI.ai_reward_period_cap`、`AI.ai_reward_hold_period`、`AI.ai_reward_expiry_period`、`AI.ai_reward_claim_enabled`、`AI.power_cap_by_robot_level`、`AI.upgrade_apt_requirement`。

**判定规则（fail-closed）**：
- 无 Active Release / 无 snapshot / `AI.standard_capacity_rule_version` 缺失 → `source_status=UNAVAILABLE`。
- 其它 `AI.*` 单项缺失不整体关闭，但**该能力不可用**（如 `ai_reward_claim_enabled` 缺省 false，`power_cap_by_robot_level` 缺省空 → `getPowerCap()` 返回 unavailable）。

**返回值（投影数组，非 DTO 类）**：

```php
[
  'source_status'            => 'UNAVAILABLE' | 'AVAILABLE',
  'parameter_release_id'     => string,   // 无则 '0'
  'snapshot_id'              => string,   // 无则 '0'
  'rule_version'             => string,   // = AI.standard_capacity_rule_version
  'power_cap_by_level'       => array,    // 缺失则 []
  'upgrade_apt_requirement'  => array,    // 缺失则 []
  'ai_reward_budget_cap'     => string,   // 缺失则 ''
  'ai_reward_period_cap'     => string,   // 缺失则 ''
  'ai_reward_hold_period'    => int,      // 缺失则 0
  'ai_reward_expiry_period'  => int,      // 缺失则 0
  'ai_reward_claim_enabled'  => bool,     // 缺失则 false
  'daily_yield_coefficient_source' => string, // 缺失则 ''
  'daily_yield_coefficient'  => string,   // 缺失则 '0'（0 是合法值）
  'reason_code'              => string,   // UNAVAILABLE 时给 I18N 安全 reason
]
```

**安全约束**：所有数值 `AI.*` 一律按字符串读取（`parameter_values` JSON 解码后逐项 `(string)`），Power Cap 映射逐项 `(int)` 键 / `(string)` 值；`daily_yield_coefficient` 允许 `'0'`，不得用 `empty()` 判可用。

## 3. 状态机骨架（复用既有 transition 模板）

| 实体 | 方法 | 转移 | 状态 | 经济依赖 |
|---|---|---|---|---|
| Robot | `enterCooling` | R2 active→cooling | 纯转移 | 无 |
| Robot | `exitCooling` | R6 cooling→active | 纯转移 | 无 |
| Robot | `lockReview` / `clearReview` | R4 active→review / R5 review→active | 纯转移 | 无 |
| Robot | `restrict` / `liftRestriction` | R7 active→restricted / R8 restricted→active | 纯转移 | 无 |
| Robot | `pause` / `resume` | R9/R12→paused / R10 paused→active | 纯转移 | 无 |
| Robot | `disable` | R11 review→inactive | 纯转移 | 无 |
| Robot | `start` / `stop` | R1 inactive→active / R3 active→inactive | FAIL_CLOSED | Power 消耗/释放（TBC） |
| Reward | `openClaimWindow` | W2 held→pending_claim | 纯转移 + expires_at | 无 |
| Reward | `startClaim` | W3 pending_claim→claiming | 纯转移 | 无 |
| Reward | `lockReview` / `clearReview` | W7 candidate→review / W8 review→held | 纯转移 | 无 |
| Reward | `hold` | W1 candidate→held | FAIL_CLOSED | 预算/资格快照（TBC） |
| Reward | `completeClaim` | W4 claiming→claimed | FAIL_CLOSED | 领取记账（TBC） |
| Reward | `expire` | W5 pending_claim→expired_returned | FAIL_CLOSED | 预算退回（TBC） |
| Reward | `reverse` | W9/W10→reversed | FAIL_CLOSED | 冲正分录（TBC） |
| Upgrade | `process` | pending→processing | 纯转移 | 无 |
| Upgrade | `complete` | processing→completed | 纯转移 | 无 |
| Upgrade | `fail` | processing→failed | 纯转移（可重试） | 无 |
| Upgrade | `cancel` | pending→cancelled | 纯转移 | 无 |
| Upgrade | `quote` / `submit` | — | FAIL_CLOSED | 升级成本/资金去向（TBC） |

**transition 模板**（与 S02-P03 Robot/Reward 一致）：事务内 `get()` → 校验 `current ∈ fromStates` → `appendAudit()` → `Db::table(...)->where(state,current)->where(object_version,current)->update([state,object_version+1,updated_time,audit_event_id])` → `affected===1` 否则 `OBJECT_VERSION_CONFLICT`。

## 4. 只读投影（RobotService）

- `summary(userId)`：遍历该用户 robots，返回 `{robots:[{robot_id,level,status,standard_capacity,rule_version,source_status}], rule:{...RobotRuleReader 投影}}`。
- `detail(robotId)`：单 Robot + 规则投影 + `allowed_actions`（`status` → 允许动作枚举 + `source_status`）。
- `allowedActions(robotId)`：返回 `{allowed_actions:[], source_status, reason_code}`；无 Active Release → `allowed_actions=[]` + `source_status=UNAVAILABLE` + `reason_code`。

**allowed_actions 推导规则**（只读、服务端下发，前端不推导）：
- `source_status=UNAVAILABLE` → `[]`（真实 start/upgrade/reward 写路径 closed）。
- 仅当规则可用时按 `status` 返回对应动作（如 `inactive→['start']`、`active→['stop','upgrade']`）。

## 5. 不变式与安全

- decimal string + bcmath：`standard_capacity`、`quantity_apt`、`apt_cost`、`power_cap`、`daily_yield_coefficient` 全字符串，禁 float。
- 三状态轴（Robot/Upgrade/Reward）分离，状态互不合并。
- 写操作无超级管理员旁路：start/upgrade/reward 写路径统一 fail-closed 或经状态机骨架。
- append-only：`parameter_snapshots` 只读，RuleReader 不写；审计事件 append-only。
- 无前端计算：coefficient/cap/cost 不从前端接收，服务端投影只读下发。

## 6. OpenAPI

- `components/schemas/robot.yaml`：`RobotSummary`、`RobotDetail`、`RobotRuleSnapshot`、`AIReward`、`RobotUpgradeOrder`（对齐 05 §3 字段，decimal 字段 string）。
- `paths/robot.yaml`：只读 GET 补 `DependencyUnavailable`（503）响应；写 POST（upgrade-orders/reward-claims/actions）补 503，表示 fail-closed。
- `gainode-v2.yaml`：注册新 schemas。

## 7. 测试

- `tests/Contract/S02P04RobotRuleContractTest.php`（不触 DB）：状态常量冻结、`RobotRuleReader` 常量/键名、`ErrorDict` 映射、`DomainException` 语义。
- `tests/Integration/S02P04RobotStateMachineTest.php`（SQLite in-memory）：建 robots/robot_rewards/robot_upgrade_orders/audit_events/parameter_releases/parameter_snapshots；测状态机合法/非法转移、CAS 冲突、fail-closed（start/stop/hold/completeClaim/expire/reverse/quote/submit）、无 Active Release 时只读投影返回 UNAVAILABLE、有 Active Release 时 RobotRuleReader 正确解析。
