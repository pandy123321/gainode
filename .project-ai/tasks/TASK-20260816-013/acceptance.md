# Acceptance: S02-P06 · OTC / Power（状态机骨架 + fail-closed + 只读投影）

## 机械验收（可自动验证）

| 项 | 命令 / 证据 | 期望 | 实际 |
|---|---|---|---|
| Contract 测试 | `php tests/Contract/S02P06OtcContractTest.php` | 26 断言全过 | ✅ 26/26 |
| Integration 测试 | `php tests/Integration/S02P06OtcStateMachineTest.php` | 35 断言全过 | ✅ 35/35 |
| PHP lint | `php -l`（4 文件） | 无语法错误 | ✅ |
| OpenAPI YAML 解析 | pyyaml safe_load（otc.yaml/apt_otc.yaml/gainode-v2.yaml） | 全部可解析 | ✅ |
| OpenAPI schema 注册 | gainode-v2.yaml components.schemas | OtcOrder/OtcTrade/OtcEligibility/OtcCapacity 4 项 | ✅ |

## 业务验收（人工/复核）

1. **OtcOrder 状态机 O1-O12**：主流程 draft→review→matching→partial→completed 与旁路 cancelled/expired/rejected/disputed 全部按 MC2 §3.6 转移矩阵，非法转移抛 `OBJECT_VERSION_CONFLICT`（409）。
2. **终态语义**：cancelled/expired/rejected = TRUE_TERMINAL 不可再转移；completed = STABLE_WITH_EXCEPTION_TRANSITIONS 可经 O11 争议；disputed = 中间态由 RISK_APPROVER 二选一裁决。
3. **fail-closed**：quote/createOrder/recordTrade 全部抛 `DEPENDENCY_UNAVAILABLE`（503），未用任何 TBC 参数补洞。
4. **OtcTrade append-only**：单态 completed；无覆盖/删除路径；争议/冲正走 RiskCase + ledger reversal 不覆盖 Trade。
5. **审计与 CAS**：每个转移写 `audit_events` + 回写 `audit_event_id` + `object_version` 递增，并发冲突 CAS 决胜。
6. **金额 decimal string**：quantity_apt/price/power 全部 string，禁 float。
7. **只读投影**：detail/listByUser/listByOrder/getByBuyer/getBySeller 只读，不改状态。
8. **不新增 DDL**：otc_orders/otc_trades 沿用 MC1/2B-1 已建表。

## 停止条件核验（07 §S02-P06）

- [x] OTC 正式 fee/limit/库存/Power 参数未 Active（06 全部 TBC）→ 写操作 closed，仅保留安全只读投影。
- [x] 储备/dispute 规则未冻结 → dispute 处置的账本/Power 调整 FAIL_CLOSED。

## 交接物

- 2 个 Service 重写（OtcOrderService/OtcTradeService）
- 1 个 OpenAPI schema 新增（otc.yaml）+ 2 个 OpenAPI 更新（apt_otc.yaml/gainode-v2.yaml）
- 2 个测试新增（Contract/Integration，共 61 断言）
- 1 个 TASK 三件套（本目录）
- context.md + manifest.yaml 指针更新
