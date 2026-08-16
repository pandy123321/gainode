# Design: S02-P06 · OTC / Power（状态机骨架 + fail-closed + 只读投影）

## Part A — 状态转移矩阵（MC2 §3.6 O1-O12）

| # | 从 → 到 | 触发者 | guard | Service 方法 | Event |
|---|---|---|---|---|---|
| O1 | draft → review | END_USER | review_required=1 | `submitReview` | OTC_ORDER_SUBMITTED_REVIEW |
| O2 | draft → matching | END_USER | review_required=0；资格通过 | `submitMatching` | OTC_ORDER_SUBMITTED_MATCHING |
| O3 | review → matching | KYC_REVIEWER / OPS_OPERATOR | 审核通过 | `approveReview` | OTC_ORDER_REVIEW_APPROVED |
| O4 | review → rejected | KYC_REVIEWER / OPS_OPERATOR | 审核驳回 | `reject` | OTC_ORDER_REJECTED |
| O5 | matching → partial | 系统 | 成交部分 | `partialFill` | OTC_ORDER_PARTIAL_FILLED |
| O6 | matching → completed | 系统 | 全部成交 | `completeFromMatching` | OTC_ORDER_COMPLETED |
| O7 | matching → cancelled | END_USER | 未成交可取消 | `cancel` | OTC_ORDER_CANCELLED |
| O8 | matching → expired | 系统 | 超有效期 | `expire` | OTC_ORDER_EXPIRED |
| O9 | partial → completed | 系统 | 剩余成交 | `completeFromPartial` | OTC_ORDER_COMPLETED |
| O10 | partial → cancelled / expired | END_USER / 系统 | 仅释放 remaining | `cancelRemaining` / `expireRemaining` | OTC_ORDER_CANCELLED / OTC_ORDER_EXPIRED |
| O11 | completed → disputed | END_USER / 系统 | 争议触发 | `dispute` | OTC_ORDER_DISPUTED |
| O12 | disputed → cancelled / completed | RISK_APPROVER | 裁决二选一 | `resolveDisputeCancel` / `resolveDisputeComplete` | OTC_ORDER_DISPUTE_RESOLVED |

## Part B — 实现策略（与 S02-P05 一致）

1. **纯状态转移完整实现**：每个 O1-O12 方法走 `TransactionBoundary` + `get` 状态守卫 + `appendAudit` + `object_version` CAS 原子 UPDATE，回写 `audit_event_id`。
   - 非法转移（当前状态不在 from 集合）抛 `OBJECT_VERSION_CONFLICT`（409）。
   - CAS 失败（object_version 不匹配）抛 `OBJECT_VERSION_CONFLICT`（409）。
2. **经济/依赖写 FAIL_CLOSED**：
   - `quote` / `createOrder` 依赖 06 OTC 参数（全部 TBC）→ `DEPENDENCY_UNAVAILABLE`（503）。
   - `recordTrade`（OtcTrade append）依赖撮合 + Ledger + Power（TBC）→ `DEPENDENCY_UNAVAILABLE`（503）。
3. **金额/Power 字段不触碰**：`filled_quantity_apt` / `remaining_quantity_apt` / `fee_apt` / `power_*` 的更新由成交/释放动作在参数冻结后附加；纯状态转移只改 `status`（对齐 S02-P05 Settlement 状态转移不计算金额）。
4. **OtcTrade append-only**：单态 `completed`；无 transition 方法；Model/Builder/DAO 三层防护已在 STAGE-01 落地（save()/delete()/update()/deleteAll()/updateAll()/updateOrCreate() 全部 RunException）。本包仅补 `recordTrade` fail-closed 入口 + 只读查询透传。

## Part C — 文件清单

| 文件 | 动作 | 说明 |
|---|---|---|
| `library/service/otc/OtcOrderService.php` | 重写 | 11 Event 常量 + 14 转移方法（O1-O12）+ quote/createOrder fail-closed + detail/listByUser |
| `library/service/otc/OtcTradeService.php` | 重写 | EVENT_TRADE_RECORDED + recordTrade fail-closed + detail/listByOrder + getByOrder/Buyer/Seller |
| `openapi/components/schemas/otc.yaml` | 新建 | OtcOrder/OtcTrade/OtcEligibility/OtcCapacity 4 schema |
| `openapi/paths/apt_otc.yaml` | 更新 | otc_quote/otc_order_create/otc_order_cancel 写 POST 补 503 |
| `openapi/gainode-v2.yaml` | 更新 | 注册 OtcOrder/OtcTrade/OtcEligibility/OtcCapacity |
| `tests/Contract/S02P06OtcContractTest.php` | 新建 | 状态常量/Event Catalog/fail-closed/错误码 HTTP 映射（26 断言） |
| `tests/Integration/S02P06OtcStateMachineTest.php` | 新建 | SQLite 内存库 O1-O12 合法非法转移/CAS/audit_event_id/只读投影/fail-closed（35 断言） |

## Part D — 验证矩阵（07 §S02-P06 验证项映射）

| 07 验证项 | 本包落地方式 |
|---|---|
| double create（幂等） | idempotency_key 唯一约束（DDL 已建）；createOrder fail-closed |
| fill/cancel race（并发） | object_version CAS 决胜 |
| expire/release | expire/expireRemaining 纯转移（释放 Power 副作用参数冻结后附加） |
| buy/sell 差异 | side=BUY/SELL 常量冻结；Buy 不套 Sell Power 规则（副作用层，参数冻结后） |
| trade append-only | OtcTradeModel/Builder/DAO 三层防护（STAGE-01）+ recordTrade fail-closed |
| dispute | O11/O12 状态机 + 冻结语义（账本/Power 调整走 Approval+reversal，参数冻结后） |
| 守恒 | Power freeze/consume/release 守恒由参数冻结后的副作用层保证（本包 fail-closed 不触碰） |

## 信息来源

- `MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md` §3.6/§5
- `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md` §8.3/§8.4/§9
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md` §3/§4/§8
- `07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P06
