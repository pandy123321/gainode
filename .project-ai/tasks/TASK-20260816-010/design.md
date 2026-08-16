# S02-P03 · Ledger / AptAccount / Power 基础 — 设计

> 项目：Gainode　工作区：`E:\github\sports`　阶段：STAGE-02　包：`S02-P03`
> 权威契约：`05_DATA_STATE_PERMISSION_API_CONTRACT.md` §3/§4；MC1 / MC2（候选）

## 1. 架构分层

```
LedgerService（Authoritative Writer: apt_ledger_entries）
  ├── 事务模板（TransactionBoundary）
  ├── append/post/cancel/reverse（L1/L2/L3）
  ├── dispute/resolveDispute（L4–L7 → FAIL_CLOSED）
  └── 受控 state 转移（raw Query Builder，白名单三列 + 乐观锁）
        │
        ├── AptAccountService（Authoritative Writer: apt_accounts）
        │     ├── applyEntryEffect()  ← CAS 余额变更（object_version）
        │     ├── getAggregateDisputeHold()  ← 账户级聚合投影（S02-P03 恒 0）
        │     └── getEffectiveAvailable()
        │
        ├── AuditEventService（Authoritative Writer: audit_events）
        │     └── appendAudit()
        │
        └── OutboxStore（Null 内核，尽力而为，不回滚业务）
```

**Authoritative Writer 唯一性**：`LedgerService` 只写 `apt_ledger_entries`，余额变更委托给 `AptAccountService`（唯一 `apt_accounts` writer），审计委托 `AuditEventService`。三者同事务原子提交。

## 2. 受控 state 转移（关键设计）

`AptLedgerEntryModel` 的 append-only 防线（`save()`/`delete()` 抛异常 + `AptLedgerEntryAppendOnlyBuilder` + `AptLedgerEntryDao` 覆写）覆盖 **ORM 正常路径**，阻断一切「意外」update。但 MC2 Ledger Mutation Field Contract 授权 `state`/`audit_event_id`/`object_version` 三列的**受控转移**。

实现：`LedgerService` 内私有方法 `transitionState()` 使用 **raw Query Builder**（`Db::connection('mysql')->table('apt_ledger_entries')`），显式 `WHERE ledger_entry_id=? AND state=? AND object_version=?`，只更新白名单三列，`affected_rows ≠ 1 → OBJECT_VERSION_CONFLICT`。该路径是「显式受控 update」，不放宽 append-only 防线。

```sql
UPDATE apt_ledger_entries
   SET state=?, audit_event_id=?, object_version=object_version+1
 WHERE ledger_entry_id=? AND state=? AND object_version=?
```

## 3. 方法契约

### 3.1 AptAccountService

| 方法 | 语义 |
|---|---|
| `applyEntryEffect(accountId, quantity, direction, expectedVersion, lastLedgerEntryId)` | 事务内 CAS 更新余额：CREDIT 增 `balance_apt_i`/`total_earned_apt`；DEBIT 减 `balance_apt_i`/增 `total_spent_apt`，负余额先抛 `INSUFFICIENT_APT`；`updateAll` 带 `object_version` 条件，affected≠1 → `OBJECT_VERSION_CONFLICT` |
| `getAggregateDisputeHold(accountId)` | 返回 `'0'`（dispute fail-closed，恒 0） |
| `getEffectiveAvailable(account)` | `bcsub(balance_apt_i, aggregate_hold)` |

### 3.2 LedgerService

| 方法 | 状态转移 | 经济效果 |
|---|---|---|
| `append(data)` | INSERT `pending` | 无 |
| `post(entryId, actorId, actorRole)` | `pending → posted`（L1） | 应用分录效果 + 审计 |
| `cancel(entryId, actorId, actorRole)` | `pending → reversed`（L2） | 无（`ACCOUNT_DELTA=0`）+ 审计 |
| `reverse(entryId, actorId, actorRole)` | `posted → reversed`（L3） | 追加 `LEDGER_REVERSAL` 反向分录 + 反向余额 + 审计 |
| `dispute(...)` | L4/L5 | FAIL_CLOSED `DEPENDENCY_UNAVAILABLE` |
| `resolveDispute(...)` | L6/L7 | FAIL_CLOSED `DEPENDENCY_UNAVAILABLE` |

`post` 与 `reverse` 共用「Economic Mutation Lock」私有步骤：幂等 → 读账户 → 聚合 hold → effective_available → guard → append entry → CAS 余额 → 审计 → commit。

### 3.3 PowerPositionService

`consume()` / `recover()` / `previewImpact()` 一律 FAIL_CLOSED（`DEPENDENCY_UNAVAILABLE`），规则未冻结前禁止任何 Power 变更。

## 4. 幂等设计

- 账本写操作幂等由 `apt_ledger_entries.uk_idempotency` 天然保证：`append()` 先 `getByIdempotencyKey()` 查重，命中抛 `IDEMPOTENCY_CONFLICT`。
- `IdempotencyStore`（Null 内核）用于无自然幂等列的通用写操作，本包不依赖。

## 5. 审计与 Outbox

- 每次状态流转追加 `audit_events`（`event_code` 对齐 Event Catalog：`LEDGER_ENTRY_POSTED`/`LEDGER_ENTRY_REVERSED`/`LEDGER_ENTRY_DISPUTED`/`LEDGER_ENTRY_DISPUTE_RESOLVED`/`LEDGER_ENTRY_DISPUTE_REVERSED`），回写 `apt_ledger_entries.audit_event_id`。
- Outbox 用 `OutboxStore` 尽力而为（Null 内核 `isAvailable()=false` 时跳过），通知异步失败不回滚业务。

## 6. 测试设计（SQLite in-memory，命名 `mysql`）

表结构简化对齐模型字段：`apt_accounts` / `apt_ledger_entries`（含 `object_version`）/ `power_positions` / `audit_events`。

覆盖：
- 守恒：`balance = Σ signed_delta(posted 分录)`。
- exactly-once：同 idempotency_key 二次 append → `IDEMPOTENCY_CONFLICT`。
- CAS 冲突：陈旧 `object_version` → `OBJECT_VERSION_CONFLICT`。
- 负余额：DEBIT 超余额 → `INSUFFICIENT_APT`。
- reversal：L2 无经济 reversal；L3 追加反向分录 + 余额归位。
- fail-closed：dispute/resolveDispute/Power consume → `DEPENDENCY_UNAVAILABLE`。
