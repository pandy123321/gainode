# S02-P03 已知限制（Known Limitations）

## 1. dispute L4-L7 未冻结（核心限制）

`RiskCase` machine contract = CONTRACT_GAP（2B-2 未冻结）。因此 `LedgerService::dispute()` / `resolveDispute()` 一律抛 `DEPENDENCY_UNAVAILABLE(503)`，`AptAccountService::getAggregateDisputeHold()` 恒返回 `0`。Dispute Hold Matrix（origin × entry_direction 四格）与 DISPUTE_SHORTFALL_POLICY（PRE_HOLD_MUTATION_GUARD、shortfall 计算）待 RiskCase 合同冻结后落地。属 MC2 已冻结但依赖实体未冻结的边界，非本包缺陷。

## 2. Power 消耗/恢复规则未冻结

Power 精确消耗/恢复由 Active Rule/Parameter 决定（TBC），`PowerPositionService::consume()` / `recover()` / `previewImpact()` 一律抛 `DEPENDENCY_UNAVAILABLE(503)`，不 mock、不返回假成功，直至 Power 规则合同冻结。

## 3. idempotency/outbox 持久化未冻结

沿用 S02-P01 的 `NullIdempotencyStore` / `NullOutboxStore`（`isAvailable()=false`）。账本写路径用 `apt_ledger_entries.uk_idempotency` 唯一键天然幂等，不依赖 Null 存储；Outbox 尽力而为、不回滚业务，持久化存储合同冻结后替换。

## 4. 跨账户锁顺序未定义

本包仅单账户 `apt_accounts.object_version` CAS，跨账户操作（如 OTC 双账户撮合、多账户结算）的锁顺序未定义，deferred 至 S02-P04（OTC）/S02-P06（Prediction 结算），已记录。多账户并发场景在锁顺序冻结前 FAIL_CLOSED。

## 5. 测试为领域层（非 HTTP 端到端）

本包 48 断言为领域层契约 + 集成（append-only 机械强制、append 输入校验、错误码映射、守恒/exactly-once/CAS 冲突/负余额/L2-L3 reversal/fail-closed），不覆盖 HTTP 控制器路由（本包无 C 端写控制器，仅 OpenAPI 只读路径定义）。HTTP 级端到端测试待路由/内核接线完成后补充。

## 6. DIFF 体积

本包 DIFF ~82785 bytes（14 文件，1277 insertions），低于外部审核工具 `max_diff_chars`（当前 100000），预期不触发 `diff_truncated`。本地 DIFF.txt 为完整未截断版本。
