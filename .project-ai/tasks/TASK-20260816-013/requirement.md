# Requirement: S02-P06 · OTC / Power（状态机骨架 + fail-closed + 只读投影）

## 状态

- **Owner Signoff：N/A（本 task 不产生 Owner Decision Matrix，全部按已冻结/候选合同 best-effort）**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE（状态转移矩阵未 FROZEN；涉财写路径 FAIL_CLOSED）**

## 背景

STAGE-02 第 6 包。OTC 是用户间受控撮合（非平台固定回购）。对象 DDL 已在 MC1（`otc_orders` 9 态）+ 2B-1（`otc_trades` append-only 单态）落盘，Model/DAO/Service 骨架已建（S01-P03/S01-P05）。`OtcEligibility`/`OtcCapacity` 非持久投影已在 S01-P06 落地，Power 持仓/影响预览已在 S02-P03 落地（fail-closed）。本包补齐 OtcOrder/OtcTrade 领域状态机骨架、fail-closed 写路径与只读投影。

## 范围（2 对象）

```text
OtcOrder   otc_orders   9 态（MC1 冻结，转移矩阵 MC2 §3.6 O1-O12）
OtcTrade   otc_trades   1 态 completed（2B-1 append-only，Owner 2B1-ENUM-04）
```

## 规则（约束）

1. 领域状态全部取自 05 §4 canonical + MC1 Freeze，禁止自创状态值。
2. 状态转移矩阵取自 MC2 Freeze §3.6（OTC Order O1-O12）+ Event Catalog §5（OTC_ORDER_* 事件码）。
3. **纯状态转移**（只改状态 + 审计 + `object_version` CAS，不写账本、不依赖外部数据源）完整实现。
4. **经济写 / 外部依赖写**（依赖 06 OTC 参数 min/max/fee/库存、撮合规则、Ledger 过账、Power 冻结/消耗/释放规则）一律 FAIL_CLOSED（`DEPENDENCY_UNAVAILABLE` 503）。
5. 每个转移：初态、合法转移、终态、Writer、幂等、并发（CAS）、审计（append `audit_events` + `audit_event_id` 回写）。
6. 触发者/Writer 仅用 05 §8 已冻结角色（END_USER / KYC_REVIEWER / OPS_OPERATOR / RISK_APPROVER / 系统），不自创角色。
7. OtcTrade append-only：一经写入永不覆盖、物理删除禁止；争议/冲正走 RiskCase + ledger reversal，不覆盖 Trade。
8. 金额 decimal string（`decimal(36,18)` 数量 / `decimal(18,8)` 价格 / `decimal(18,4)` Power），禁 float；非法转移一律 `OBJECT_VERSION_CONFLICT`（409）。

## 状态分类（MC2 §3.6 IR 629 P2-1）

- TRUE_TERMINAL：`cancelled` / `expired` / `rejected`
- STABLE_WITH_EXCEPTION_TRANSITIONS：`completed`（可经 O11 争议）
- `disputed` = 中间态（RISK_APPROVER 裁决 cancelled 或 completed 二选一，不回 partial）

## fail-closed 边界（依赖未冻结，写操作 closed）

| 依赖 | 冻结状态 | 受影响写操作 |
|---|---|---|
| OTC fee/limit/库存参数 | 06 TBC | quote |
| order_min/max_amount + fee_rate + inventory_limit | 06 TBC | createOrder |
| Power freeze/consume/release 规则 | 06 TBC | createOrder / partialFill / cancel / expire 的 Power 副作用 |
| 撮合规则 + Ledger 过账 | 未冻结 | recordTrade（OtcTrade append） |
| 储备/dispute 规则 | 未冻结 | dispute 处置的账本/Power 调整 |

## 非目标（NON_GOALS）

- 不新增 DDL（otc_orders/otc_trades 已建）。
- 不实现撮合引擎与真实成交。
- 不实现账本写路径（成交 Ledger 过账由后续经济包闭环）。
- 不实现 Controller 层（OpenAPI 路径骨架沿用 S02-P01，业务 request/response 本包补 schema）。
- 不重写 OtcEligibility/OtcCapacity/PowerPosition/PowerImpactPreview（已在 S01-P06/S02-P03 落地）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`（§8.3/§8.4/§9 OTC）
- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3/§4/§8）
- `Gainode_Development_Ready_V6.1_Latest/06_PARAMETER_DICTIONARY.md`（OTC.* / otc.* / AI.power_* 全部 TBC）
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（§S02-P06）
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（§3.6 O1-O12 + §5 Event Catalog）
- `.project-ai/rules/coding.md`
