# S02-P03 质量审核报告（Quality Review）

> QUALITY-01 独立审核。只写 `.project-ai/reviews/**`。

## 0. 审核绑定

```text
REVIEW_ID                = GAINODE-S02P03-LEDGER-APTACCOUNT-POWER-IR-20260816-001
PROJECT                  = Gainode
FORMAL_STAGE             = STAGE-02
PACKAGE_ID               = S02-P03-LEDGER-APTACCOUNT-POWER
BASE_COMMIT              = 0084fae
SNAPSHOT_COMMIT          = 978ca8a
REVIEW_RANGE             = 0084fae..978ca8a
REVIEW_ROUND             = 1
SNAPSHOT_LOCKED          = YES
```

## 1. 材料完整性矩阵

REVIEW_REQUEST / REVIEW_RANGE / IMPLEMENTATION_COMMIT / PAYLOAD_MANIFEST / DIFF(82785B 未截断) / PACKAGE_SHA256 / SECRET_SCAN / SELF_REVIEW / VALIDATION_RESULTS / KNOWN_LIMITATIONS 全部齐备。

## 2. 变更概览

S02-P03 APT 数量账经济写路径统一模板：14 文件 / 1277 insertions / 29 deletions。不建表（DDL_TABLE_COUNT_DELTA=0，复用 MC1 `apt_ledger_entries`/`apt_accounts`/`audit_events`）。落地 11 步 Economic Mutation Lock + Ledger L1/L2/L3 状态机 + 账户 CAS + dispute/Power fail-closed。

## 3. 审核结论

**APPROVED** — 0 P0 / 0 P1 / 0 BLOCKING_P2 / 0 NON_BLOCKING_P2 / 1 P3。

## 4. 独立验证记录（QUALITY-01 实际执行）

| 验证项 | 方法 | 结果 |
|---|---|---|
| append-only 防护 | 读 AptLedgerEntryModel | save()/delete() 抛异常 + AppendOnlyBuilder 注入 + $timestamps=false ✅ |
| LedgerService 11 步模板 | 读源码 | append/post/cancel/reverse + dispute fail-closed + transitionState CAS + appendAudit ✅ |
| AptAccountService CAS | 读源码 | applyEntryEffect WHERE object_version CAS，affected≠1→409 ✅ |
| 负余额保护 | 读源码 | DEBIT balance<quantity → INSUFFICIENT_APT(422) ✅ |
| bcmath 精度 | 读源码 | 全程 bcadd/bcsub/bccomp scale=18，禁 float ✅ |
| 幂等 | 读源码 | append 同 key → IDEMPOTENCY_CONFLICT(409) ✅ |
| L2/L3 reversal | 读源码 | L2 无经济效果；L3 追加 LEDGER_REVERSAL 反向分录 ✅ |
| dispute/Power fail-closed | 读源码 | 全 DEPENDENCY_UNAVAILABLE(503) ✅ |
| 审计写入 | 读源码 | appendAudit 正确调用 AuditEventService::create()（对比 S02-P02 已修复方向）✅ |
| Contract 测试 | 实际运行 | 18 断言全过 ✅ |
| Integration 测试 | 实际运行 | 30 断言全过（守恒/负余额/CAS/L2-L3/fail-closed）✅ |

## 5. Freeze / Machine Contract 一致性

```text
DDL_TABLE_COUNT_DELTA = 0（复用 MC1 apt_ledger_entries/apt_accounts/audit_events）✅
append-only 未放宽（Model/Builder/DAO 三层 + transitionState 白名单三列）✅
Ledger 状态机 L1/L2/L3 对齐 MC2 §5（pending/posted/reversed）✅
entry_direction/entry_type 对齐 MC2 Event Catalog（Owner 裁决 #16）✅
asset=APT-I 唯一（APT-C OUT_OF_SCOPE）✅
```

## 6~8. P0 / P1 / P2 Findings

无。

## 9. P3 Findings（1）

### S02-P03-P3-001 — 负余额保护口径表述不一致（stored_balance vs effective_available）

- **FILE_PATH**: `library/service/ledger/AptAccountService.php` L120-L124
- **RELATED_CONTRACT**: REVIEW_REQUEST「NEGATIVE_BALANCE_PROTECTED = DEBIT 使 effective_available<0」
- **CURRENT_BEHAVIOR**: 负余额保护用 `balance_apt_i`（stored_balance），`bccomp($balance, $quantity) < 0`。代码注释亦写「禁止负 stored_balance」。
- **EXPECTED_BEHAVIOR**: REVIEW_REQUEST 声明口径为 `effective_available < 0`（`= balance_apt_i - aggregate_dispute_hold`）。
- **IMPACT**: 当前 `getAggregateDisputeHold()` 恒 `0`（dispute L4/L5 未冻结），两者数值等价，无实际缺陷。未来 dispute 冻结后若未同步改用 `effective_available`，用户可能花费被 dispute hold 的余额。
- **MINIMUM_SAFE_FIX**: 未来 dispute 冻结（S02-P07/后续）时，将负余额保护改为 `getEffectiveAvailable($account)` 口径，并同步回归测试。
- **GATE_IMPACT**: NON_BLOCKING（当前等价，记录为跨包协作风险）。

## 10. Closed Finding 回归

N/A（首审）。

## 11. 关键矩阵

```text
权限  = N/A（本包无对外鉴权端点，写路径为领域服务）✅
状态  = Ledger L1/L2/L3 状态机正确 ✅
资金  = 守恒 + 负余额保护 + CAS + bcmath 全通过 ✅
数据  = DDL_TABLE_COUNT_DELTA=0 ✅
API   = OpenAPI 3 只读路径 + schemas 注册 ✅
审计  = appendAudit 正确写 audit_event ✅（S02-P02 P2-002 修复方向已示范）
```

## 12~14. 验证 / 未执行 / 工具限制

STATIC_CHECK = PASS（php -l 6/6）／TEST = PASS（48 断言）／OPENAPI_PARSE = PASS（$ref 0 missing）／BUILD = NOT_RUN／RUNTIME_CHECK = NOT_RUN（SQLite 内存库测试已覆盖经济突变路径）／DEPLOYMENT = NOT_RUN。

## 15. 开发 Agent 继续条件

```text
SNAPSHOT_LOCKED = YES
NEXT_PACKAGE_OVERLAP = NO
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
```

## 16. Package 合并建议

```text
CODE_MERGE_RECOMMENDATION = APPROVED
```

## 17. Formal Stage Gate 状态

STAGE-02 尚有 S02-P04~P09 待审核，本包不触发 Gate。

## 18. 修复提示词

无 BLOCKING Finding。P3-001 已记录，需在 dispute 冻结时同步负余额保护口径（`effective_available`）。

---

```text
SNAPSHOT_LOCKED                 = YES
REVIEW_COMPLETENESS             = COMPLETE
VERDICT                         = APPROVED
P0_OPEN                         = 0
P1_OPEN                         = 0
BLOCKING_P2_OPEN                = 0
NON_BLOCKING_P2_OPEN            = 0
P3_OPEN                         = 1
CODE_MERGE_RECOMMENDATION       = APPROVED
DEV_NEXT_PACKAGE_BLOCKED_BY_REVIEW = NO
BLOCKED_PACKAGE                 = NONE
FORMAL_STAGE_GATE               = NOT_APPLICABLE
PRODUCTION_APPROVAL             = NO
```
