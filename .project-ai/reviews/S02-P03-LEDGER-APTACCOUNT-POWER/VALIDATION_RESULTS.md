# S02-P03 验证结果（Validation Results）

## 机械断言

```text
DDL_TABLE_COUNT_DELTA = 0                                              PASS
APPEND_ONLY_NOT_RELAXED = 经济字段仍禁 UPDATE/DELETE                    PASS
LEDGER_AUTHORITATIVE_WRITER = LedgerService 唯一写账本                   PASS
SINGLE_ECONOMIC_MUTATION_TEMPLATE = 统一 11 步事务模板                   PASS
DECIMAL_STRING = 金额全 bcmath / quantity>0 / direction∈{1,-1}          PASS
NEGATIVE_BALANCE_PROTECTED = INSUFFICIENT_APT(422)                      PASS
IDEMPOTENCY = 同 key 二次写 → IDEMPOTENCY_CONFLICT(409)                 PASS
CAS_CONFLICT = 陈旧 object_version → OBJECT_VERSION_CONFLICT(409)       PASS
L2_PENDING_REVERSAL = ACCOUNT_DELTA=0 / NO reversal / AUDIT=YES         PASS
L3_POSTED_REVERSAL = LEDGER_REVERSAL + 反向分录 + 余额归位               PASS
DISPUTE_FAIL_CLOSED = dispute/resolveDispute → DEPENDENCY_UNAVAILABLE    PASS
POWER_FAIL_CLOSED = consume/recover/previewImpact → DEPENDENCY_UNAVAILABLE PASS
OPENAPI_YAML_VALID = safe_load 通过                                     PASS
OPENAPI_REF_RESOLVED = 0 missing（全量 $ref 检查）                      PASS
OPENAPI_VERSION = 3.1.0                                                 PASS
PHP_SYNTAX = 6/6                                                        PASS
TEST_ASSERTIONS = 48（18 Contract + 30 Integration）                    PASS
SECRET_SCAN = PASS（AKIA 命中为已轮换历史记录）                          PASS
DIFF_UNTRUNCATED = YES（82785 bytes）                                   PASS
PRODUCTION = NO-GO                                                     PASS
```

## 状态机核对（Ledger L1/L2/L3）

```text
L1 pending→posted            = 追加分录 + 更新余额 + 追加审计，同事务原子          ✅
L2 pending→reversed          = ACCOUNT_DELTA=0、ECONOMIC_REVERSAL_ENTRY=NO、
                               AUDIT_EVENT=YES                                  ✅
L3 posted→reversed           = 追加 entry_type=LEDGER_REVERSAL、direction=-(原)、
                               quantity=原、reversal_of=原；余额反向归位         ✅
L4/L5 pending/posted→disputed = FAIL_CLOSED（RiskCase CONTRACT_GAP）             ✅
L6/L7 disputed→posted/reversed = FAIL_CLOSED（同上）                             ✅
```

## 错误码 HTTP 映射核对

```text
INSUFFICIENT_APT.httpStatus()       = 422  ✅
OBJECT_VERSION_CONFLICT.httpStatus() = 409  ✅
IDEMPOTENCY_CONFLICT.httpStatus()    = 409  ✅
DEPENDENCY_UNAVAILABLE.httpStatus()  = 503  ✅
DomainException.resultCode()         = 05 §7 字符串码  ✅
```

## OpenAPI 结构核对

```text
入口 gainode-v2.yaml（3.1.0）     = 注册 ledger paths + schemas $ref            ✅
components/schemas/ledger.yaml    = LedgerEntry / AssetBalance / PowerPosition  ✅
paths/ledger.yaml                 = asset_balance / ledger_entries / power_position（3 只读）✅
```

## 一致性核对

- `context.md` 当前执行包进度 = 已追加 `S02-P03 已落地` 段落，与交付一致。
- `manifest.yaml` `stage02_p03_ledger_aptaccount_power` 记录 COMPLETE + 交付清单，与 REVIEW_REQUEST 一致；`contextVersion` 27→28。
- 测试断言计数（18 + 30 = 48）与 S02P03LedgerContractTest / S02P03LedgerMutationTest 实际输出一致。
- `PAYLOAD_MANIFEST.csv` 14 文件与 `git diff --name-status 0084fae..978ca8a` 一致。
