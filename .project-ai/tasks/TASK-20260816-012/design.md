# Design: S02-P05 · Prediction P0（状态机骨架 + fail-closed + 只读投影）

## 状态

- **冻结状态：CANDIDATE（状态转移矩阵未 FROZEN；涉财写路径 FAIL_CLOSED）**

## 架构总览

```text
library/service/prediction/PredictionMarketService.php     prediction_markets 唯一 Authoritative Writer
library/service/prediction/PredictionOrderService.php      prediction_orders  唯一 Authoritative Writer
library/service/prediction/ResultService.php               results            唯一 Authoritative Writer
library/service/prediction/SettlementService.php           settlements        唯一 Authoritative Writer
library/service/prediction/SettlementBatchService.php      settlement_batches 唯一 Authoritative Writer
library/service/prediction/RefundCaseService.php           refund_cases       唯一 Authoritative Writer
library/service/prediction/CorrectionCaseService.php       correction_cases   唯一 Authoritative Writer
library/service/policy/ConsentReceiptService.php           consent_receipts   唯一 Authoritative Writer
```

每个 Service 遵循统一模式（对齐 S02-P03/P04）：
- `transition()` 私有辅助：`TransactionBoundary` 事务 + 状态合法性校验（非法转移 `OBJECT_VERSION_CONFLICT`）+ append 审计 + `object_version` CAS update。
- `appendAudit()` 私有辅助：追加 append-only `audit_events`，回写 `audit_event_id`（无该列的表如 `prediction_markets` 经 `target_object_type` 单向关联）。
- fail-closed 方法：`throw DomainException(DEPENDENCY_UNAVAILABLE)`。

## 对象实现矩阵

### 1. PredictionMarket（9 态，`prediction_markets` 无 `audit_event_id` 列）

| ID | 方法 | 转移 | 触发者 | 实现 |
|---|---|---|---|---|
| M1 | publish | draft → open | OPS_OPERATOR | 纯转移 |
| M2 | startClosing | open → closing | 系统 | 纯转移 |
| M3/M4 | lock | closing/open → locked | 系统/OPS | 纯转移 |
| M5 | awaitResult | locked → awaiting_result | 系统 | 纯转移 |
| M6 | startSettlement | awaiting_result → settlement | 系统 | 纯转移 |
| M7 | completeSettlement | settlement → settled | 系统 | 纯转移 |
| M8 | voidMarket | draft/open/closing/locked/awaiting_result → void | OPS/系统 | 纯转移 |
| M9 | failSettlement | settlement → exception | 系统 | 纯转移 |
| M10 | retrySettlement | exception → settlement | OPS/系统 | 纯转移 |
| M11 | completeSettlementManual | exception → settled | OPS+RISK | 纯转移 |
| M12 | reopenSettlement | settled → settlement | 系统 | 纯转移 |
| — | create | — | — | FAIL_CLOSED（赛事源 TBC） |

### 2. PredictionOrder（9 态，有 `audit_event_id`）

| ID | 方法 | 转移 | 实现 |
|---|---|---|---|
| P1 | lock | submitted → locked | 纯转移 |
| P2 | awaitResult | locked → awaiting_result | 纯转移 |
| P3 | startSettling | awaiting_result → settling | 纯转移 |
| P4 | settle | settling → settled | 纯转移 |
| — | submit | — | FAIL_CLOSED（锁盘/资格/stake TBC） |
| P5/P10/P11/P12 | startRefund | settling/submitted/locked/awaiting_result → refunding | FAIL_CLOSED（RefundCase 未冻结） |
| P6 | completeRefund | refunding → refunded | FAIL_CLOSED（退款账本） |
| P7/P9 | startCorrect | settled/settling → correcting | FAIL_CLOSED（CorrectionCase 未冻结） |
| P8 | completeCorrect | correcting → corrected | FAIL_CLOSED（修正账本） |

### 3. Result（4 态，有 `audit_event_id`）

| ID | 方法 | 转移 | 实现 |
|---|---|---|---|
| RS1 | confirm | provisional → official | FAIL_CLOSED（赛果源 TBC） |
| RS2 | dispute | official → disputed | FAIL_CLOSED（RiskCase 未冻结） |
| RS3 | uphold | disputed → official | 纯转移（RISK_APPROVER 裁决恢复） |
| RS4 | correctFromDisputed | disputed → corrected | 纯转移（RISK_APPROVER 裁决纠错） |
| RS5 | correctFromOfficial | official → corrected | 纯转移（含 correction_version 仅一次守卫） |

非法：`corrected → *`（终态）；`official → provisional`；`disputed → provisional`；越级 `provisional → corrected`。

### 4. Settlement（6 态，有 `audit_event_id`）

| ID | 方法 | 转移 | 实现 |
|---|---|---|---|
| ST1 | start | queued → calculating | 纯转移 |
| ST2 | calculate | calculating → payable | FAIL_CLOSED（结算参数 TBC） |
| ST3 | reviewRequired | calculating → review | 纯转移 |
| ST4 | approveReview | review → payable | 纯转移（RISK_APPROVER） |
| ST5 | pay | payable → paid | FAIL_CLOSED（账本过账） |
| ST6 | fail | queued/calculating/review/payable → failed | 纯转移 |
| ST7 | retry | failed → queued | 纯转移（OPS） |

非法：`paid → *`（终态）；`failed → payable/paid`；`review → paid`；`calculating → queued`。

### 5. SettlementBatch（5 态，有 `audit_event_id`）

| 方法 | 转移 | 实现 |
|---|---|---|
| process | created → processing | 纯转移 |
| complete | processing → completed | 纯转移 |
| partiallyFail | processing → partially_failed | 纯转移 |
| retry | partially_failed → processing | 纯转移 |
| fail | processing/partially_failed → failed | 纯转移 |
| create | — | FAIL_CLOSED（结算切片参数 TBC） |

### 6. RefundCase / 7. CorrectionCase（6 态，有 `audit_event_id`）

| 方法 | 转移 | 实现 |
|---|---|---|
| approve | pending → approved | 纯转移（RISK_APPROVER） |
| reject | pending → rejected | 纯转移 |
| execute | approved → executing | 纯转移 |
| fail | executing → failed | 纯转移 |
| retry | failed → executing | 纯转移 |
| create | — | FAIL_CLOSED（退款/修正协同未冻结） |
| complete | executing → completed | FAIL_CLOSED（退款/修正账本写） |

### 8. ConsentReceipt（2 态，有 `audit_event_id`）

| 方法 | 转移 | 实现 |
|---|---|---|
| create | 记录同意（content_hash/consent_version 由调用方传入） | 完整实现（幂等去重） |
| expire | active → expired | 纯转移 |

## 只读投影（返回 source_status，缺依赖时 UNAVAILABLE）

- PredictionMarket：`list`（按赛事/状态）、`detail`、`allowedActions`（`Market=open` → `place_bet`，否则空 + blocked 候选）。
- PredictionOrder：`listByUser`、`detail`。
- Result：`detail`。
- Settlement / SettlementBatch / RefundCase / CorrectionCase / ConsentReceipt：`detail`。

## 不变量与安全约束

1. 三状态轴不合并：Market `settled` ≠ Order `settled` ≠ Settlement `paid`（05 §4 预测聚合映射）。
2. Result `official` ≠ Settlement `paid`；Result confirmer ≠ Settlement approver。
3. 金额一律 decimal string，禁 float；`standard_capacity`/`amount_apt`/`principal_total_apt` 等 string。
4. `object_version` CAS 乐观锁；`affected_rows ≠ 1` → `OBJECT_VERSION_CONFLICT`。
5. 审计 append-only，不覆盖历史。
6. 写操作（含 fail-closed 方法）签名统一 `(string $id, string $actorId, string $actorRole)`。

## 测试策略

- Contract：领域状态常量冻结、Event Catalog 常量、fail-closed 写路径抛 `DEPENDENCY_UNAVAILABLE`、V2 错误码 HTTP 映射。
- Integration（SQLite 内存库）：合法/非法转移、CAS 冲突、Result `correction_version` 仅一次守卫、ConsentReceipt 幂等去重、只读投影、无依赖时 fail-closed。

## 信息来源

同 `requirement.md`。
