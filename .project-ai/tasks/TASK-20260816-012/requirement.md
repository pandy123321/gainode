# Requirement: S02-P05 · Prediction P0（状态机骨架 + fail-closed + 只读投影）

## 状态

- **Owner Signoff：N/A（本 task 不产生 Owner Decision Matrix，全部按已冻结/候选合同 best-effort）**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE（状态转移矩阵未 FROZEN；涉财写路径 FAIL_CLOSED）**

## 背景

STAGE-02 第 5 包。Prediction（竞猜）是 P0 足球赛前 1X2 主玩法。对象 DDL 已在 MC1（`prediction_markets`/`prediction_orders`）+ 2B-1（`results`/`settlements`/`settlement_batches`/`refund_cases`/`correction_cases`）+ 2B-2（`consent_receipts`）落盘，Model/DAO/Service 骨架已建（S01-P03/S01-P05）。本包补齐领域状态机骨架、fail-closed 写路径与只读投影。

## 范围（7 对象 + 1 回执）

```text
PredictionMarket   prediction_markets   9 态（MC1 冻结）
PredictionOrder    prediction_orders    9 态（MC1 冻结）
Result             results              4 态（2B-1 补齐）
Settlement         settlements          6 态（2B-1 补齐）
SettlementBatch    settlement_batches   5 态（Owner 2B1-ENUM-01）
RefundCase         refund_cases         6 态（Owner 2B1-ENUM-02）
CorrectionCase     correction_cases     6 态（Owner 2B1-ENUM-03）
ConsentReceipt     consent_receipts     2 态（Owner 2B1-ENUM-06）
```

## 规则（约束）

1. 领域状态全部取自 05 §4 canonical + MC1/MC2 Freeze，禁止自创状态值。
2. 状态转移矩阵取自 MC2 Freeze §3.4（Market M1-M12）/§3.5（Order P1-P12）+ S01-P02 design（Result RS1-RS5 / Settlement ST1-ST7 / SettlementBatch / RefundCase / CorrectionCase / ConsentReceipt 状态合同）。
3. **纯状态转移**（只改状态 + 审计 + `object_version` CAS，不写账本、不依赖外部数据源）完整实现。
4. **经济写 / 外部依赖写**（依赖赛事源、赛果源、锁盘参数、结算参数、退款/修正协同、RiskCase/ApprovalRequest、账本写）一律 FAIL_CLOSED（`DEPENDENCY_UNAVAILABLE` 503）。
5. 每个转移：初态、合法转移、终态、Writer、幂等、并发（CAS）、审计（append `audit_events`）。
6. 触发者/Writer 仅用 05 §8 已冻结角色（OPS_OPERATOR / RISK_APPROVER / 系统 / END_USER），不自创角色。
7. SoD：Result confirmer ≠ Settlement approver；RISK_ANALYST ≠ RISK_APPROVER。
8. 金额 decimal string（`decimal(36,18)`），禁 float；非法转移一律 `OBJECT_VERSION_CONFLICT`（409）。

## fail-closed 边界（依赖未冻结，写操作 closed）

| 依赖 | 冻结状态 | 受影响写操作 |
|---|---|---|
| 赛事源 Fixture | TBC | Market.create |
| 锁盘参数 lock_at | 06 TBC | Order.submit |
| 赛果源（主备源一致） | TBC | Result.confirm（RS1） |
| RiskCase machine contract | 2B-2 未冻结 | Result.dispute（RS2） |
| 结算参数（odds/系数） | 06 TBC | Settlement.calculate（ST2） |
| 账本过账协同 | 未冻结 | Settlement.pay（ST5） |
| RefundCase 契约 | 2B-1 未 FROZEN | Order P5/P6/P10/P11/P12 退款、RefundCase.create/complete |
| CorrectionCase 契约 | 2B-1 未 FROZEN | Order P7/P8/P9 纠错、CorrectionCase.create/complete |
| 结算切片参数 | 06 TBC | SettlementBatch.create |

## 非目标（NON_GOALS）

- 不新增 DDL（8 对象表已建）。
- 不实现账本写路径（退款/修正/结算过账由后续经济包闭环）。
- 不接入赛事源/赛果源供应商。
- 不实现 Controller 层（OpenAPI 路径骨架沿用 S02-P01，业务 request/response 本包补 schema）。
- 不实现 corrected 重结算业务路径（MC2 方案 C deferred，保持 FAIL_CLOSED）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3/§4/§8）
- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（§S02-P05）
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（§3.4/§3.5/§5）
- `.project-ai/tasks/TASK-20260816-001/design.md`（Part A/B/C/D Result/Settlement/跨实体协同）
- `.project-ai/rules/coding.md`
