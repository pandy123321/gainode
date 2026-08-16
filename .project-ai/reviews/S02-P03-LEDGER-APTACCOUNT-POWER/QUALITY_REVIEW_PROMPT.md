# S02-P03 独立审核提示词（Independent Review Agent）

你是 Gainode 项目的独立审核 Agent。请以 **Evidence First（证据优先）** 原则，对本包 S02-P03 Ledger/AptAccount/Power 基础进行只读审核，逐项验证并输出 PASS / CHANGES_REQUIRED 结论。

## 审核对象

```text
PACKAGE_ID = S02-P03-LEDGER-APTACCOUNT-POWER
COMMIT     = 978ca8a（14 文件，1277 insertions / 29 deletions）
BASE       = 0084fae
BRANCH     = feature/gainode-v3-serial-development
```

## 审核要点（逐项验证）

1. **append-only 完整性**：`AptLedgerEntryModel` / `AuditEventModel` 的 Model/Builder/DAO 三层机械强制是否未放宽（经济字段仍禁 UPDATE/DELETE）；`LedgerService::transitionState()` 是否仅允许白名单三列（state / audit_event_id / object_version）受控更新，且附 transition guard 与 object_version CAS。
2. **L1/L2/L3 状态转移**：`pending→posted`（L1）是否同事务原子（追加分录 + 更新余额 + 追加审计）；`pending→reversed`（L2）是否 `ACCOUNT_DELTA=0` 且不追加经济 reversal 分录、仅追加审计；`posted→reversed`（L3）是否追加 `entry_type=LEDGER_REVERSAL`、`entry_direction=-(原)`、`quantity=原`、`reversal_of=原` 且余额反向归位。
3. **CAS 乐观锁 + decimal string**：`AptAccountService::applyEntryEffect()` 是否带 `object_version` CAS（affected rows≠1 → `OBJECT_VERSION_CONFLICT`）；金额是否全 bcmath、`quantity>0`、`entry_direction∈{1,-1}`、`asset=APT-I`；`signed_delta = quantity × entry_direction`。
4. **负余额保护**：DEBIT 使 `effective_available < 0` 是否抛 `INSUFFICIENT_APT(422)`，不产生负 stored_balance；`getEffectiveAvailable = stored_available - aggregate_dispute_hold`；`getAggregateDisputeHold` 是否恒 `0`（dispute fail-closed）。
5. **幂等**：`getByIdempotencyKey` 去重是否返回原结果 / 冲突 `IDEMPOTENCY_CONFLICT(409)`，保证 exactly-once。
6. **fail-closed**：`dispute`/`resolveDispute` 与 Power `consume`/`recover`/`previewImpact` 是否抛 `DEPENDENCY_UNAVAILABLE(503)`，不 mock、不返回假成功、无「跳过状态机直接改余额/账本」的旁路方法。
7. **错误码映射**：`INSUFFICIENT_APT→422`、`OBJECT_VERSION_CONFLICT→409`、`IDEMPOTENCY_CONFLICT→409`、`DEPENDENCY_UNAVAILABLE→503` 是否经 `DomainException` + `ErrorDict::httpStatus()` 正确映射。
8. **OpenAPI 3.1**：`schemas/ledger.yaml`（LedgerEntry/AssetBalance/PowerPosition）与 `paths/ledger.yaml`（asset_balance/ledger_entries/power_position 三只读路径）是否与实现一致；`gainode-v2.yaml` 是否注册且全量 `$ref` 0 missing。
9. **测试**：S02P03LedgerContractTest（18 断言）+ S02P03LedgerMutationTest（30 断言）是否独立可运行且全过，覆盖守恒/exactly-once/CAS 冲突/负余额/L2 无经济 reversal/L3 LEDGER_REVERSAL/fail-closed 负路径。
10. **治理一致性**：`context.md` / `manifest.yaml` 进度指针是否与交付一致；`stage02_p03_ledger_aptaccount_power` 是否 COMPLETE 且交付清单准确；`DDL_TABLE_COUNT_DELTA=0` 是否成立。

## 证据要求

- 每项结论引用具体文件行/字段作为证据。
- 发现缺陷标注严重级（BLOCKING / P2 / P3）+ 缺陷 ID。
- 不修改任何文件，仅输出审核报告。

## 参考权威契约

- `Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md`（S02-P03 固定步骤）
- `05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3 资产/账本 / §7 错误分类 / §10 数据新鲜度）
- `sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`（MC1 冻结 DDL）
- `sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（MC2 State Transition + Ledger Mutation Field Contract + Dispute Hold Matrix + DISPUTE_SHORTFALL_POLICY）
- `.project-ai/tasks/TASK-20260816-010/{requirement,design,acceptance}.md`
