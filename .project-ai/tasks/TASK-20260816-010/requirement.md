# S02-P03 · Ledger / AptAccount / Power 基础 — 需求

> 项目：Gainode　工作区：`E:\github\sports`　阶段：STAGE-02　包：`S02-P03`
> 权威执行计划：`Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md` §S02-P03（V3.3 FROZEN_FOR_EXECUTION）
> 权威契约：`05_DATA_STATE_PERMISSION_API_CONTRACT.md` §3/§4/§10；`sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`（MC1）；`sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（MC2，候选）

## 1. 目标

落地 **APT 数量账经济写路径的统一事务模板**（11 步 Economic Mutation Lock），实现 `apt_ledger_entries` 的 L1/L2/L3 状态转移、`apt_accounts` 余额 CAS 乐观锁、`audit_events` 追加审计、幂等去重与 negative balance 保护；L4–L7（dispute）与 Power 变更因依赖未冻结契约而 FAIL_CLOSED。

实现顺序固定：**Contract（状态机/字段可变性）→ Domain Service（模板/CAS）→ Model 补充 → OpenAPI → Tests**。

## 2. 冻结范围与 FAIL_CLOSED 边界

### 2.1 可落地（FROZEN / Owner 裁决 + 07 §S02-P03 已列步骤）

| 项 | 内容 |
|---|---|
| L1 | `pending → posted`：日记账过账，追加分录 + 更新 `apt_accounts` 余额 + 追加审计，同事务原子 |
| L2 | `pending → reversed`：入账前取消，`ACCOUNT_DELTA=0`、`ECONOMIC_REVERSAL_ENTRY=NO`、`AUDIT_EVENT=YES` |
| L3 | `posted → reversed`：入账后冲正，追加 `entry_type=LEDGER_REVERSAL` 反向分录 + 反向更新余额 |
| 幂等 | `apt_ledger_entries.uk_idempotency` + `getByIdempotencyKey()` 去重，冲突 `IDEMPOTENCY_CONFLICT(409)` |
| CAS | `apt_accounts.object_version` 乐观锁，affected rows≠1 → `OBJECT_VERSION_CONFLICT(409)` |
| 负余额 | DEBIT 使 `effective_available < 0` → `INSUFFICIENT_APT(422)`，禁止负 stored_balance |
| 审计 | `audit_events` append-only，每次状态流转同事务回写 `audit_event_id` |

### 2.2 FAIL_CLOSED（未冻结依赖）

| 项 | 原因 | 行为 |
|---|---|---|
| L4/L5（`pending/posted → disputed`） | `RiskCase` machine contract = CONTRACT_GAP（2B-2 未冻结） | 抛 `DEPENDENCY_UNAVAILABLE(503)` |
| L6/L7（`disputed → posted/reversed`） | 同上 | 抛 `DEPENDENCY_UNAVAILABLE(503)` |
| Power `consume/recover/convert` | 精确消耗/恢复规则由 Active Rule/Parameter 决定（TBC） | 抛 `DEPENDENCY_UNAVAILABLE(503)` |
| IdempotencyStore / OutboxStore 具体实现 | 存储表未冻结（Null 内核，fail-closed） | 账本用 `uk_idempotency` 天然幂等；Outbox 尽力而为，不回滚业务 |

## 3. 统一 Economic Mutation Lock（11 步，07 §S02-P03 步骤 1–7）

所有改 `balance_apt_i/balance_apt_c/frozen_apt_i/frozen_apt_c/aggregate_dispute_hold` 的操作，必须：

1. 按 `idempotency_key` 查原结果：已完成返回原响应，冲突 `IDEMPOTENCY_CONFLICT`。
2. 读取 `apt_accounts`，取得 `object_version`（预期版本）。
3. 读 stored balance，计算 `aggregate_dispute_hold`（S02-P03 恒为 0，dispute fail-closed）。
4. 计算 `effective_available = stored_available - aggregate_dispute_hold`。
5. 校验 guard：DEBIT 且 `quantity > effective_available` → `INSUFFICIENT_APT`。
6. 追加 immutable LedgerEntry（经济字段禁止 update）。
7. CAS 更新 `apt_accounts` 投影，affected rows≠1 → `OBJECT_VERSION_CONFLICT`。
8. 同事务追加 AuditEvent。
9. `object_version + 1`。
10. 提交；任何步骤失败全回滚（禁止「余额成功但账本失败」）。
11. reversal 追加反向分录并引用原 entry，禁止删除/覆盖。

## 4. 关键约束与不变式

- **append-only**：`apt_ledger_entries` / `audit_events` 经济字段不可 UPDATE/DELETE；仅 `state`/`audit_event_id`/`object_version` 三列经「显式受控 update 路径」（白名单三列 + 乐观锁 + transition guard）流转。
- **Ledger Mutation Field Contract**：`ledger_entry_id/account_id/asset/quantity/entry_direction/entry_type/source_object_type/source_object_id/journal_batch_id/reversal_of/idempotency_key/rule_version/snapshot_id/created_time` 永久 immutable。
- **decimal string**：金额字段一律 `decimal(36,18)` 字符串 + `bcmath` 运算，禁 float。
- **entry_direction**：`1=CREDIT`、`-1=DEBIT`；`signed_delta = quantity × entry_direction`；`quantity` 恒正。
- **资产**：仅 `APT-I`；`APT-C = OUT_OF_SCOPE`，禁止入账。
- **超级管理员无旁路**：所有经济写路径复用同一事务模板。

## 5. 非目标（NON_GOALS）

- 不落地 L4–L7 dispute 四格矩阵与 Dispute Hold（RiskCase 未冻结 → fail-closed，不 mock）。
- 不实现 Prediction stake/settlement、OTC debit/credit、Withdrawal、Robot/Reward 等 S02-P04~P08 具体业务写路径。
- 不实现 Power 精确消耗/恢复（规则 TBC）。
- 不改 MC1/2B-1/2B-2 已冻结 DDL（`20260813_machine_contract_batch1_8_core_entities.sql` 等）。
- 不 push、不提审（Development Agent 职责边界）。

## 6. 交付物清单

- `library/service/ledger/LedgerService.php`（append/post/cancel/reverse/dispute/resolveDispute + 事务模板）。
- `library/service/ledger/AptAccountService.php`（applyEntryEffect CAS + 聚合投影）。
- `library/service/power/PowerPositionService.php`（consume/recover/previewImpact fail-closed）。
- `library/model/ledger/AptLedgerEntryModel.php`（补 `object_version` + direction/type 常量）。
- `openapi/components/schemas/ledger.yaml` + `openapi/paths/ledger.yaml` + `openapi/gainode-v2.yaml` 注册。
- `tests/Contract/`、`tests/Integration/` 测试（守恒/exactly-once/CAS/负余额/reversal/fail-closed）。
