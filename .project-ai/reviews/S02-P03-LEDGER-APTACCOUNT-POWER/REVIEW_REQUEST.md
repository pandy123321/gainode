# S02-P03 复审请求（供 Quality Agent）

## 提审绑定

```text
PACKAGE_ID            = S02-P03-LEDGER-APTACCOUNT-POWER
TASK_ID               = TASK-20260816-010
IMPLEMENTATION_COMMIT = 978ca8a（Ledger/AptAccount/Power 经济写路径基础，14 文件）
BASE_COMMIT           = 0084fae
BRANCH                = feature/gainode-v3-serial-development
PACKAGE_SHA256        = 6b8bbbd6a61bd0aaf5116b4fef4475b0ce58399171d76e95f747a92b8c6ff459（DIFF.txt）
DIFF_UNTRUNCATED      = YES（82785 bytes，UTF-8 无 BOM）
REVIEW_PACKAGE_TRUNCATED = NO
SECRET_SCAN           = PASS（见 SECRET_SCAN.txt，唯一 AKIA 命中为 manifest 已轮换历史记录）
DDL_TABLE_COUNT_DELTA = 0（本包不建表，仅操作 MC1 冻结 DDL：apt_ledger_entries / apt_accounts / audit_events）
```

## 范围

S02-P03 APT 数量账经济写路径统一事务模板（11 步 Economic Mutation Lock），落地 `apt_ledger_entries` L1/L2/L3 状态转移 + `apt_accounts` 余额 CAS + `audit_events` 追加审计 + 幂等去重 + 负余额保护；L4-L7（dispute）与 Power 变更 fail-closed。交付 14 文件（1277 insertions / 29 deletions）：

```text
0.5代码/gainode后端/gainode/library/service/ledger/LedgerService.php          append/post/cancel/reverse + dispute/resolveDispute fail-closed + 白名单三列受控转移 + appendAudit
0.5代码/gainode后端/gainode/library/service/ledger/AptAccountService.php     getAggregateDisputeHold / getEffectiveAvailable / applyEntryEffect（CAS + 负余额保护）
0.5代码/gainode后端/gainode/library/service/power/PowerPositionService.php   consume/recover/previewImpact 全 fail-closed
0.5代码/gainode后端/gainode/library/model/ledger/AptLedgerEntryModel.php     补 object_version + ENTRY_DIRECTION/ENTRY_TYPE/ASSET 常量
0.5代码/gainode后端/gainode/openapi/components/schemas/ledger.yaml           LedgerEntry/AssetBalance/PowerPosition schema
0.5代码/gainode后端/gainode/openapi/paths/ledger.yaml                        3 只读路径 me/asset、me/ledger-entries、me/power
0.5代码/gainode后端/gainode/openapi/gainode-v2.yaml                          注册 ledger paths + schemas
0.5代码/gainode后端/gainode/tests/Contract/S02P03LedgerContractTest.php      18 断言（领域常量/append-only/输入校验/错误码映射）
0.5代码/gainode后端/gainode/tests/Integration/S02P03LedgerMutationTest.php  30 断言（守恒/exactly-once/CAS/负余额/L2-L3/fail-closed）
.project-ai/tasks/TASK-20260816-010/{requirement,design,acceptance}.md        任务文档
.project-ai/context.md / .project-ai/manifest.yaml                           进度指针 + stage02_p03_ledger_aptaccount_power
```

## 非目标

- 不建表、不改 DDL（`DDL_TABLE_COUNT_DELTA = 0`），仅操作 MC1 冻结表（`apt_ledger_entries` / `apt_accounts` / `audit_events`）。
- 不落地 L4-L7 dispute 四格矩阵与 Dispute Hold（`RiskCase` machine contract = CONTRACT_GAP → fail-closed，不 mock）。
- 不实现 Power 精确消耗/恢复（Active Rule/Parameter TBC → fail-closed）。
- 不实现 Prediction stake/settlement、OTC debit/credit、Withdrawal、Robot/Reward 等 S02-P04~P08 具体业务写路径。
- 不落地 idempotency/outbox 持久化（账本用 `uk_idempotency` 天然幂等；Outbox 沿用 S02-P01 Null fail-closed）。
- 不 push、不提审（Development Agent 职责边界）。

## 关键不变量（请逐项验证）

```text
DDL_TABLE_COUNT_DELTA           = 0
APPEND_ONLY_NOT_RELAXED         = apt_ledger_entries / audit_events 经济字段仍禁 UPDATE/DELETE
LEDGER_AUTHORITATIVE_WRITER     = LedgerService 唯一写 apt_ledger_entries；余额仅经 AptAccountService
SINGLE_ECONOMIC_MUTATION_TEMPLATE = 所有经济写路径复用同一 11 步事务模板 + object_version CAS
DECIMAL_STRING                  = 金额全 bcmath，禁 float；quantity>0；entry_direction∈{1,-1}；asset=APT-I
NEGATIVE_BALANCE_PROTECTED      = DEBIT 使 effective_available<0 → INSUFFICIENT_APT(422)，禁止负 stored_balance
IDEMPOTENCY                     = 同 idempotency_key 二次写 → IDEMPOTENCY_CONFLICT(409)
CAS_CONFLICT                    = 陈旧 object_version → OBJECT_VERSION_CONFLICT(409)
L2_PENDING_REVERSAL             = ACCOUNT_DELTA=0、ECONOMIC_REVERSAL_ENTRY=NO、AUDIT_EVENT=YES
L3_POSTED_REVERSAL              = 追加 entry_type=LEDGER_REVERSAL、direction=-(原)、reversal_of=原
DISPUTE_FAIL_CLOSED             = dispute/resolveDispute → DEPENDENCY_UNAVAILABLE(503)
POWER_FAIL_CLOSED               = consume/recover/previewImpact → DEPENDENCY_UNAVAILABLE(503)
OPENAPI_YAML_VALID              = safe_load 通过
OPENAPI_REF_RESOLVED            = 0 missing（gainode-v2.yaml 全量 $ref 检查）
OPENAPI_VERSION                 = 3.1.0
TEST_ASSERTIONS                 = 48（18 Contract + 30 Integration）
PHP_SYNTAX                      = 6/6（php -l 全过）
PRODUCTION                       = NO-GO（dispute/Power 未冻结 → fail-closed）
```

## 交接声明（Dev → Quality）

按 CR-20260816-003 OPTION_A（开发 agent 一开到底），本包为 STAGE-02 经济写路径基础包，在 MC1 冻结 DDL 上落地 11 步 Economic Mutation Lock 统一事务模板。关键 fail-closed 边界：

- **dispute L4-L7 未冻结**：`RiskCase` machine contract = CONTRACT_GAP（2B-2 未冻结），`LedgerService::dispute()` / `resolveDispute()` 一律抛 `DEPENDENCY_UNAVAILABLE`；`AptAccountService::getAggregateDisputeHold()` 恒返回 `0`（无争议冻结）。Dispute Hold Matrix / DISPUTE_SHORTFALL_POLICY 待 RiskCase 冻结后落地。
- **Power 变更未冻结**：精确消耗/恢复规则由 Active Rule/Parameter 决定（TBC），`PowerPositionService::consume()` / `recover()` / `previewImpact()` 全 `DEPENDENCY_UNAVAILABLE`。
- **append-only 未放宽**：`apt_ledger_entries` / `audit_events` 经济字段仍禁 UPDATE/DELETE；仅 `state`/`audit_event_id`/`object_version` 白名单三列经 `transitionState()` 受控流转（raw Query Builder + transition guard），附 `object_version` CAS 乐观锁。
- **超级管理员无旁路**：无任何「跳过状态机直接改余额/账本」的方法，所有经济写路径复用同一事务模板。

## 审核重点

1. **append-only 完整性**：`AptLedgerEntryModel` / `AuditEventModel` 的 Builder/DAO/Model 三层机械强制是否未放宽；`transitionState()` 是否仅允许白名单三列（state/audit_event_id/object_version）受控更新。
2. **L1/L2/L3 状态转移**：`pending→posted`（L1 过账）是否同事务原子更新余额 + 追加审计；`pending→reversed`（L2 取消）是否 `ACCOUNT_DELTA=0` 且不追加经济 reversal 分录；`posted→reversed`（L3 冲正）是否追加 `entry_type=LEDGER_REVERSAL`、`entry_direction=-(原)`、`reversal_of=原` 且余额反向归位。
3. **CAS 乐观锁**：`applyEntryEffect` 是否带 `object_version` CAS（affected rows≠1 → `OBJECT_VERSION_CONFLICT`）；余额更新是否 bcmath decimal string，无 float。
4. **负余额保护**：DEBIT 使 `effective_available < 0` 是否抛 `INSUFFICIENT_APT`（不产生负 stored_balance）；`getEffectiveAvailable = stored_available - aggregate_dispute_hold`。
5. **幂等**：`getByIdempotencyKey` 去重是否返回原结果 / 冲突 `IDEMPOTENCY_CONFLICT(409)`。
6. **fail-closed**：`dispute`/`resolveDispute` 与 Power `consume`/`recover`/`previewImpact` 是否抛 `DEPENDENCY_UNAVAILABLE(503)`，不 mock、不返回假成功。
7. **错误码映射**：`INSUFFICIENT_APT→422`、`OBJECT_VERSION_CONFLICT→409`、`IDEMPOTENCY_CONFLICT→409`、`DEPENDENCY_UNAVAILABLE→503` 是否经 `DomainException` + `ErrorDict` 正确映射。
8. **OpenAPI 3.1**：`schemas/ledger.yaml`（LedgerEntry/AssetBalance/PowerPosition）与 `paths/ledger.yaml`（3 只读路径）是否与实现一致；`gainode-v2.yaml` 是否注册且 `$ref` 0 missing。
9. **测试**：S02P03LedgerContractTest（18 断言）+ S02P03LedgerMutationTest（30 断言）是否独立可运行且全过，覆盖守恒/exactly-once/CAS 冲突/负余额/L2-L3 reversal/fail-closed 负路径。
10. **治理一致性**：`context.md` / `manifest.yaml` 进度指针是否与交付一致；`stage02_p03_ledger_aptaccount_power` 是否 COMPLETE 且交付清单准确。
