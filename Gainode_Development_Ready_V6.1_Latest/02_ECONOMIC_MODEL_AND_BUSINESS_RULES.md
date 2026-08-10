# 02 · Gainode 经济模型与核心业务规则

> 版本：V2.2 · Funding Reserve & Expired Order Closure
> 用途：产品、后端、账本、测试统一理解
> 说明：这里保留开发必须理解的经济逻辑；复杂治理历史不再要求开发阅读。

## 1. 先说人话

Gainode 里最容易混淆的是 **APT 数量、APT 参考价值、平台真实收入、用户 Reward**。这四个东西必须分开。

- APT 数量：系统账本里有多少 APT。
- APT 参考估值：拿某个参考价做展示，不等于现金收入。
- 平台真实收入：必须真的收到可验证对价才能算。
- Reward：按资格、预算、Robot 产能权重和当日系数生成的 APT 数量权益，不是固定收入。

## 2. 总体经济结构

```text
内部 AI/数据/执行能力
        ↓ 可审计结果
AI Reward Budget（受预算上限控制）
        ↓
Robot standard_capacity × daily_reward_coefficient
        ↓
Candidate / Pending APT Reward
        ↓ 资格、风控、领取、账本
Claimed APT-I
        ↓
APT Account / Power / OTC / Prediction use
```

Prediction 是独立生态：

```text
用户参与 → APT 冻结 → Market Lock → Result → Settlement / Refund / Correction
```

AI 与 Prediction 可以共享用户身份和 Global P，但**资金、奖励预算、结算和账本不能默认互相补贴**。

## 3. APT 总量与三种口径

### 3.1 总量上限

```text
APT_MAX_SUPPLY = 100,000,000,000 APT
```

数量不变量：

```text
期初数量 + 批准激活 + 外部回流 - 正式销毁 = 期末总量
期末总量 <= 100,000,000,000 APT
```

### 3.2 APT-I 与 APT-C

- `APT-I`：系统内部数量账，P0 使用。
- `APT-C`：链上形态，Future。
- APT-I → APT-C 的 1:1 只表示**数量映射**，不代表 1 APT = 1 USD，也不代表平台刚兑。

P0：Migration 入口关闭，只保留对象/API/状态预留。

## 4. 四账分离

| 账 | 记录什么 | 不是什么 |
|---|---|---|
| APT 数量账 | available/frozen/pending/held/payable/claimed/burned | 不是现金收入 |
| APT 参考估值账 | quantity × reference price | 不是官方兑付价格 |
| 功能货币收入账 | 实际收到并有证据的 USDT/USDC/法币等 | 不是 APT 数量 |
| Reward/预算账 | AI/Prediction 的预算、候选、负债、支付 | 不等于用户可用余额 |

**人话备注：** 页面上看到“10,000 APT”不代表平台已经赚了对应美元，也不代表用户能按某个固定价格卖掉。

### 4.1 资金、储备与运营预算隔离边界

> 说明：本节补充运营储备的隔离语义，不替代 §4 四账作为权威财务口径。

- **OTC 结算储备**：用于已批准撮合和结算安排的独立现金管理。不是固定回购池、保价池或无条件承接卖单的资金池。
- **运营与风险预算**：用于已批准的激励、系统运行、风控和应急支出。不得绕过预算、审批、账本和审计流程。
- 用户 APT 数量账、AI Reward Budget、Prediction 结算资金、OTC 结算储备和运营/风险预算**不得静默互相补贴**。
- 以上资金池不重新包装为用户端“四账”或“多账户”概念。用户端仍以 APT 数量账和 Reward 展示为主。
- 实际额度、比例和阈值参数进入 06。

## 5. Robot 与 AI Reward

### 5.1 56 级 Robot

Robot 共 56 级。页面为了好看可以分组，但等级本身保持 1–56。

建议展示分组：

```text
Lv.1–9
Lv.10–19
Lv.20–29
Lv.30–39
Lv.40–49
Lv.50–56
```

分组只用于 UI，不改变业务规则。

### 5.2 Reward 核心公式

```text
pending_apt = standard_capacity × daily_reward_coefficient
```

- `standard_capacity`：Robot 等级对应的分配权重。
- `daily_reward_coefficient`：当天服务端系数，**允许等于 0**。
- `pending_apt`：待领取数量，不是已到账。

**人话备注：** Robot 等级高，代表参与预算分配的能力/权重变化，不代表每天固定产出。

### 5.3 Reward 状态

```text
CANDIDATE
→ HELD / PENDING_CLAIM
→ CLAIMING
→ CLAIMED
或 EXPIRED_RETURNED
或 REVIEW
或 REVERSED
```

规则：
- Candidate 不进可用余额。
- Claim 成功必须先有账本 posting。
- Claim 超时或失败不得重复扣/发。
- 到期未领取可以按当时规则返原池。
- 冲正保留原记录，再追加反向记录，不能删除历史。

### 5.4 内部 AI 经济引擎

内部仍可保存“可审计执行结果 → Reward Budget”的经济逻辑，但 C 端不显示成“套利固定收益”。

开发需要支持：

```text
confirmed_profit
reference_profit
mapped_apt_budget
daily_ai_budget
```

参考计算结构：

```text
if confirmed_profit <= 0:
    reference_profit = 0
else:
    reference_profit = approved_smoothing(confirmed_profit, historical_reference)

mapped_apt_budget = reference_profit_USDT / apt_reference_price × mapping_multiplier

daily_ai_budget = min(
    mapped_apt_budget,
    stage_expected_budget,
    stage_hard_cap,
    cash_support_cap,
    human_approved_cap
)
```

所有比例/上限来自 Parameter Release，不从前端写死。

## 6. Robot 升级

升级前必须返回一个服务器报价/快照：

```text
current_level
target_level
apt_cost
capability_diff
standard_capacity_diff
power_limit_diff
cooldown
eligibility
quote_expires_at
rule_version
parameter_release_id
```

用户主动确认后才能提交。

升级结果允许：
- completed
- review
- cooling
- rejected/no_effect
- failed/no_effect

**禁止：** 前端先扣 APT 再等后端补状态。

## 7. Global P 与专项资格

三个字段必须分开：

```text
global_p_level
ai_reward_eligibility
prediction_eligibility / prediction_reward_eligibility
```

Global P 是身份/成长层，不自动打开 AI Reward 或 Prediction。

## 8. Power

Power 是 Gainode 中的**可消耗、可恢复操作资源**，不是手续费、不是 Reward，也不是“收益算力”。

### 8.1 Power 与 Robot 成长

Power 的总容量由当前 Robot 规则快照决定，并随着 Robot 成长获得更高的可用上限。

```text
power_cap = resolve_power_cap(robot_level, active_robot_rule_snapshot)
```

开发约束：

- `power_cap` 的具体数值和每级映射只能来自 Active Parameter / Robot Rule Snapshot。
- 前端不得按 Level 自己写死 Power Cap。
- Robot Upgrade Quote 已有 `power_limit_diff`，升级确认页必须展示升级前后 Power Cap 的变化。
- “随着 Robot 成长而增长”是产品规则；具体每级增加多少仍是参数，不在 UI 中猜值。

### 8.2 Power 核心状态

```text
available
frozen
consumed
released
recovering
cap
```

恢复遵守服务端规则：

```text
restored_power = min(
    power_cap,
    power_available + approved_restore_amount
)
```

恢复周期、恢复量、触发时点全部读取 Active Parameter；没有有效规则时，不得由前端模拟成正式值。

### 8.3 Power 使用场景

当前正式产品规则把 Power 用于以下高价值动作：

| 动作 | Power 规则 |
|---|---|
| OTC Sell | 提交时冻结；成交部分消耗；未成交部分继续冻结；取消/过期释放未成交部分 |
| Withdrawal | 会消耗 Power；具体门槛、数量和最终扣减时点由服务端 Action Preview + Active Parameter 决定 |
| Robot Start / Auto-execution Activation | 启动 Robot 的自动执行能力会消耗 Power；C 端仍使用“启动 Robot / 启动运行”表达，不使用 Crypto Arbitrage 叙事 |

特别说明：

- Buy APT 不使用 OTC Sell Power 规则。
- Prediction P0 不自动消耗 Power；除非未来 02/05/06 再正式增加 Prediction-Power 规则。
- Withdrawal 与 Robot Start 的**具体 Power 数值和扣减时点当前属于参数化规则**，不得在前端写默认值。
- 任何需要 Power 的写操作都必须先由服务端返回 `PowerImpactPreview`，再由用户确认。

### 8.4 OTC Sell 的锁定流程

```text
required_power = sell_quantity × approved_power_ratio
```

执行规则：

```text
Submit Sell
→ freeze required Power

Partial Fill
→ consume Power for filled portion
→ keep unfilled portion frozen

Cancel / Expire Remaining
→ release remaining frozen Power
```

dispute/review 状态继续保持对应 Power 冻结，直到明确结果。

**人话备注：** Power 可以理解成“做部分重要操作时会占用或消耗的操作额度”。Robot 等级越高，可以拥有更高的 Power 上限，但具体额度永远以服务器当前规则为准。


## 9. OTC

OTC 是用户间受控撮合，不是平台固定回购。

### 9.1 订单状态

Canonical 状态以 05 §4 统一状态机为准：

```text
draft / review / matching / partial / completed / cancelled / expired / rejected / disputed
```

关键语义：
- `completed`：完整成交完成。
- `cancelled`：用户或系统主动取消。
- `expired`：按订单有效期自然到期；不等同于用户主动 Cancelled。
- `partial + cancelled/expired`：只释放 remaining 部分，已成交部分不受影响。
- `disputed`：保持相关冻结，直到明确处置结果。

### 9.2 关键规则

- Order submitted ≠ Trade completed。
- 参考价 ≠ 官方兑付价。
- 流动性不保证。
- Sell 下单时服务端计算可售数量、Power、费用和冻结影响。
- Completed 只能在订单状态 + 交易记录 + 账本效果一致后显示。
- 部分成交必须显示：原数量、已成交、剩余、已消耗 Power、仍冻结 Power。

## 10. Prediction P0

### 10.1 首发玩法

```text
Football Pre-match 1X2
Home Win / Draw / Away Win
```

赛果口径：正式比赛 **90 分钟 + 伤停补时**，不含加时赛和点球大战。

### 10.2 提交规则

提交前：
- Market 必须 OPEN。
- 用户准入允许。
- 参数 Release 有效。
- 余额/限额有效。
- 必须展示 Disclosure 并主动 Consent。

提交后：
- 不可撤销；
- 不可减少；
- 不可换方向；
- 锁定前可以在**原方向**追加；
- 锁定后不能继续提交。

### 10.3 Market 无效/异常

可能原因：
- 低流动性；
- 方向结构不满足；
- 赛事取消/延期/身份错误；
- 结果源冲突；
- 无有效赢家；
- 风控复核。

系统必须区分：

```text
market_status
result_status
settlement_status
principal_status
reward_status
```

不能用一个 `status=failed` 把所有事混在一起。

### 10.4 Refund

如果 Market 被认定作废/退款：
- 合法本金退回；
- 服务费按对应无效规则处理；P0 基线为作废时 `F=0`；
- 原冻结和退款必须有账本记录；
- 用户能看到原因和时间线。

### 10.5 Correction

赛果更正：
- 保留原 result snapshot；
- 新建 correction version；
- 旧结算用 reversal；
- 新结果重新计算并 posting；
- 不直接覆盖历史。

## 11. Prediction 与 AI 隔离

默认：

```text
AI budget → Prediction = FORBIDDEN
Prediction funds → AI budget = FORBIDDEN
```

以后要跨生态必须做新模型、审批、账本和回滚，不是普通参数开关。

## 12. Team / Referral

P1，不阻塞首发。

保留原则：
- 邀请关系可以共用；
- AI 与 Prediction 奖励基础分开；
- 不允许用用户本金/退款资金付增长奖励；
- Candidate/HELD 不可当成已支付。

## 13. 收入确认

平台只有在“真实收到、能匹配、证据完整、汇率来源有效、对账为 0”时才确认功能货币收入。

APT 服务费数量本身不自动等于收入。

## 14. 所有经济写操作的共同要求

必须有：

```text
object_id
request_id
idempotency_key
rule_version
parameter_release_id
policy_version
snapshot_id
ledger/journal reference
created_at
final/current status
```

高风险更正还必须有：

```text
case_id
approval_id
evidence_id
reversal_reference
```
