# Requirement: S02-P08 · 内部 AI 经济引擎（计算流水线 + fail-closed）

## 状态

- **Owner Signoff：N/A（本 task 不产生 Owner Decision Matrix；所有比例/上限来源 06 参数全部 TBC → 引擎保持 closed，不写默认值）**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE（预算持久对象未冻结；smoothing/price/multiplier/cap/rounding 未由 Active Release 定义）**

## 背景

STAGE-02 第 8 包。内部 AI 经济引擎是「可审计执行结果 → Reward Budget」的纯计算流水线。它不直接给用户发 Reward，只产出 `confirmed_profit / reference_profit / mapped_apt_budget / daily_ai_budget` 四字段及其版本/审计。C 端不显示「套利固定收益」（02 §5.4）；AI budget 与 Prediction 预算双向隔离（02 §11）。

S01-P08 已盘点 AISignal / AIRecommendation / SimulationRun 三对象（Owner 已签 enum，但 DDL/Service 属快照 2 未建、转移矩阵未 FROZEN），并明确「预算连接（02 §5.4 mapped_apt_budget）P1 不启用（D8）」。因此本包为纯计算内核 + fail-closed 骨架，不依赖三对象持久化。

## 范围（计算流水线四服务 + DTO + adapter）

```text
ConfirmedProfitAdapter   已确认/可追溯/去重 内部结果输入适配
ReferenceProfitService   confirmed_profit<=0 短路 + smoothing（fail-closed）
AptBudgetMappingService  reference_profit → mapped_apt_budget（price/multiplier fail-closed）
DailyAIBudgetService     五 cap 取最小 → daily_ai_budget（cap fail-closed）
AiBudgetParameterReader  Parameter/Snapshot adapter（读 AI.* 参数，06 全 TBC）
BudgetDecision           内部 DTO（四字段 + 版本/审计元数据 + 脱敏 serializer）
AiBudgetEngine           流水线编排（持久化/Outbox/Audit fail-closed）
```

## 规则（07 §S02-P08 固定步骤）

1. ConfirmedProfitAdapter 只接受已确认、可追溯、去重的内部结果，记录 source object/hash/currency/confirmed_at；未确认或重复输入拒绝。
2. 若 `confirmed_profit <= 0`，确定性输出 `reference_profit = 0`，不得调用 smoothing 产生正值。
3. 若大于 0，从 Active Release 读取 approved smoothing 规则和 historical_reference snapshot，计算并记录 algorithm/rule/version/input hash。
4. 读取同一时点可用的 APT reference price snapshot 与 mapping multiplier；缺失、过期、<=0 时 fail-closed，不除零、不回退 mock。
5. 计算 `mapped_apt_budget = reference_profit_USDT / apt_reference_price * mapping_multiplier`，全程 decimal/string/指定精度，禁止 float。
6. 从 Active Release/Snapshot 读取 stage_expected_budget、stage_hard_cap、cash_support_cap、human_approved_cap，取最小值得 daily_ai_budget；任一 required cap 缺失则 closed。
7. 以 source+parameter release+snapshot+business date 建 idempotency key，持久化/追加预算决定和 AuditEvent，Outbox 只通知内部预算消费者。
8. 对外 API/日志/通知 serializer 负向扫描：不得泄露供应商、arbitrage signal、profit detail、position、cap 明细或内部模型参数。

## 金额与精度

- 金额一律 decimal string + bcmath（scale=18），禁止 float。
- 除零保护：`apt_reference_price` 缺失/过期/<=0 一律 fail-closed，不除零、不回退 mock。
- 精度/rounding 参数（06 未定义）缺失 → 保持引擎 closed，不自行猜精度。

## fail-closed 边界（依赖未冻结，计算/持久化 closed）

| 依赖 | 冻结状态 | 受影响步骤 |
|---|---|---|
| approved smoothing 规则 + historical_reference snapshot | 06 TBC / snapshot 对象未冻结 | 步骤 3（positive smoothing） |
| APT reference price snapshot | 快照对象未冻结 | 步骤 4/5 |
| mapping_multiplier | 06 TBC | 步骤 4/5 |
| stage_expected_budget / stage_hard_cap / cash_support_cap / human_approved_cap | 06 TBC | 步骤 6 |
| rounding/precision | 06 TBC | 步骤 5/6 |
| 预算持久对象（Budget Decision 表） | 未冻结（无 DDL） | 步骤 7（持久化/Audit/Outbox） |

## 停止条件（已确认满足 → 引擎保持 closed）

- smoothing、price source、mapping multiplier、任何 cap、rounding 均未由 Active Release 定义（06 §4 无对应键）。
- 预算持久对象未冻结（无 DDL）。
- → 保持引擎 closed，生成 Parameter/Contract Decision，不写默认值。

## 非目标（NON_GOALS）

- 不新增 DDL（预算持久对象未冻结）。
- 不实现 smoothing 具体算法 / 价格源 / 汇率源真实调用。
- 不实现 Controller / C 端 serializer / route。
- 不直接修改用户 APT（不触碰 apt_accounts 余额）。
- 不实现 AISignal/AIRecommendation/SimulationRun 快照 2（属 S01-P08 后续）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`（§5.4/§11）
- `Gainode_Development_Ready_V6.1_Latest/06_PARAMETER_DICTIONARY.md`（§4，AI.* 全 TBC）
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（§S02-P08）
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_AI_OPERATIONS_P1_FREEZE.md`（§5 D8 预算连接 P1 不启用）
- `0.5代码/gainode后端/gainode/library/service/robot/RobotRuleReader.php`（Parameter/Snapshot 只读 adapter 先例）
- `.project-ai/tasks/TASK-20260816-014/requirement.md`（S02-P07 fail-closed 先例）
