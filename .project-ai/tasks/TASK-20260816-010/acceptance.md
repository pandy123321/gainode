# S02-P03 · Ledger / AptAccount / Power 基础 — 验收

> 项目：Gainode　工作区：`E:\github\sports`　阶段：STAGE-02　包：`S02-P03`

## 1. 机械断言（静态/一致性）

- [ ] `apt_ledger_entries` / `audit_events` 经济字段无 UPDATE/DELETE 路径（append-only 机械强制未放宽）。
- [ ] `LedgerService` 是 `apt_ledger_entries` 唯一 Authoritative Writer；余额变更仅经 `AptAccountService`。
- [ ] 所有经济写路径复用同一 `TransactionBoundary` 事务模板；`applyEntryEffect` 带 `object_version` CAS。
- [ ] 金额运算全部 `bcmath`，无 float；`quantity > 0`、`entry_direction ∈ {1,-1}`、`asset = APT-I` 校验。
- [ ] `php -l` 全部通过；OpenAPI `$ref` 全解析；`gainode-v2.yaml` 注册新 path/schema。

## 2. 行为断言（测试证明）

- [ ] **守恒**：任一账户 `balance_apt_i == Σ signed_delta(posted 分录)`；reversal 后归位。
- [ ] **exactly-once**：同 `idempotency_key` 二次写 → `IDEMPOTENCY_CONFLICT`。
- [ ] **CAS 冲突**：陈旧 `object_version` → `OBJECT_VERSION_CONFLICT`（affected≠1 统一）。
- [ ] **负余额**：DEBIT 使 `effective_available < 0` → `INSUFFICIENT_APT`；不产生负 stored_balance。
- [ ] **L2 pending reversal**：`ACCOUNT_DELTA=0`、`ECONOMIC_REVERSAL_ENTRY=NO`（无经济 reversal 分录）、`AUDIT_EVENT=YES`。
- [ ] **L3 posted reversal**：追加 `entry_type=LEDGER_REVERSAL`、`entry_direction=-(原)`、`quantity=原`、`reversal_of=原`；余额反向。
- [ ] **FAIL_CLOSED**：`dispute`/`resolveDispute`/Power `consume`/`recover` → `DEPENDENCY_UNAVAILABLE`。
- [ ] **超级管理员无旁路**：无任何「跳过状态机直接改余额/账本」的方法。

## 3. 停止条件核对（07 §S02-P03）

- [ ] 任何写路径需要直接 update ledger **经济列** → 拒绝写入（本包仅白名单三列受控转移）。
- [ ] 无 Active Snapshot → 拒绝写入（本包不 mock snapshot，后续 Robot/Reward 由 snapshot 供给）。
- [ ] 跨账户锁顺序未定义 → 本包仅单账户 CAS，跨账户锁顺序 deferred 至 S02-P04/P06，记录于 KNOWN_LIMITATIONS。

## 4. 交付物核对

- [ ] `library/service/ledger/LedgerService.php` / `AptAccountService.php` / `library/service/power/PowerPositionService.php`
- [ ] `library/model/ledger/AptLedgerEntryModel.php`（`object_version` + 常量）
- [ ] `openapi/components/schemas/ledger.yaml` + `openapi/paths/ledger.yaml` + `openapi/gainode-v2.yaml`
- [ ] `tests/Contract/` + `tests/Integration/` 测试（全 PASS）
- [ ] `.project-ai/context.md` + `manifest.yaml` 更新；本地 commit（不 push）+ 复审快照包
