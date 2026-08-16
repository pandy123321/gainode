# S02-P04 · Robot / Reward / Upgrade 基础 — 验收

> 对齐 `07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P04 的验证/停止条件/验收。

## 1. 功能验收

- [ ] **56 级规则读取器**：`RobotRuleReader` 从 Active Release → Snapshot 解析 `AI.*`；无 Active Release / 无 `AI.standard_capacity_rule_version` 时 `source_status=UNAVAILABLE`。
- [ ] **只读投影**：`RobotService::summary/detail/allowedActions` 无 Active Release 时返回 unavailable reason + `allowed_actions=[]`，真实 start/upgrade/reward 写操作 closed。
- [ ] **状态机骨架**：Robot R2/R4/R5/R6/R7/R8/R9/R10/R11/R12、Reward W2/W3/W7/W8、Upgrade pending→processing→completed/failed/cancelled 全部经 transition（审计 + CAS），非法转移 `OBJECT_VERSION_CONFLICT`。
- [ ] **fail-closed 写路径**：`start/stop/hold/completeClaim/expire/reverse/quote/submit` 全部 `DEPENDENCY_UNAVAILABLE(503)`。

## 2. 边界验证

- [ ] 56 级边界（level 1 / 56）在 RuleReader 映射中正确解析（无 Active Release 时为 unavailable，不 mock 数值）。
- [ ] 无 Active Release：所有写操作 closed，只读投影返回 unavailable reason。
- [ ] quote expiry / double start / double upgrade / double claim / 并发升级 / Power 不足 / Reward budget cap / claim timeout / expiry / reversal：凡依赖 TBC 参数的场景一律 fail-closed，不产生账本分录。
- [ ] 前端不能传正式 coefficient/cap/cost：写 API 不接收这些字段；服务端投影只读下发。

## 3. 不变式验收

- [ ] Robot、Upgrade、Reward 三状态轴分离，状态互不合并。
- [ ] 每次（已落地的）经济变化有 snapshot/ledger/audit（本包写路径均 fail-closed，故无新增 ledger 分录，仅审计事件 append-only）。
- [ ] 无固定收益、无前端计算；`standard_capacity`/`daily_yield_coefficient`/`power_cap`/`apt_cost` 全 decimal string。
- [ ] append-only：`parameter_snapshots` 只读，RuleReader 零写入。

## 4. 测试验收

- [ ] `tests/Contract/S02P04RobotRuleContractTest.php` 全 PASS。
- [ ] `tests/Integration/S02P04RobotStateMachineTest.php` 全 PASS。
- [ ] OpenAPI parse/ref/operationId 无断裂；新增 schemas 已注册 `gainode-v2.yaml`。

## 5. 停止条件（本包内确认）

- [ ] 56 级规则或正式参数未 Active → 相关写操作 closed，未用旧 Mining 值补洞。
- [ ] 升级资金去向/大额审批未冻结 → `quote/submit` 保持 closed。
- [ ] 本包未 push、未提审（Development Agent 职责边界）。
