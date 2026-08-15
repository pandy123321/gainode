# SELF_REVIEW — MC2 复审包（IR 686 修复）

## 自检结论

Development Agent 对 `7e6f828..2795e38`（commit `2795e38`）的 IR 686 修复自检：

```text
IMPLEMENTATION_STATUS = DONE
MODIFIED_FILES = 5（见 PAYLOAD_MANIFEST.csv）
SELF_CHECK = PASS
BUILD_RESULT = NOT_RUN（本包为合同文档，无编译产物）
TEST_RESULT = NOT_RUN（本包不涉及可执行代码）
STATIC_CHECK_RESULT = PASS（git diff --check 无空白错误）
SECRET_SCAN_RESULT = PASS（0 hits）
UNEXECUTED_VALIDATIONS = 运行时/数据库验证（属 STAGE-05 Sandbox，不在本包）
KNOWN_LIMITATIONS = 见 KNOWN_LIMITATIONS.md
```

## IR 686 四项逐项核对

### P1-1：统一 Economic Mutation Lock

- 落点：`design.md` A.1.2（D 节）+ Freeze `§3.1`。
- 证据：`APT_ACCOUNT_ECONOMIC_MUTATION_LOCK = apt_accounts.object_version`；`ALL_ACCOUNT_ECONOMIC_MUTATIONS_REQUIRE_ACCOUNT_CAS = YES`；11 步同事务顺序（acquire/check object_version → lock → read stored balance → aggregate hold → effective_available → guard → CAS ledger → mutate projection → audit → object_version+1 → commit）。
- 覆盖域：`balance_apt_i/balance_apt_c/frozen_apt_i/frozen_apt_c/aggregate_dispute_hold`。
- 跨操作串行断言：`ACCOUNT_ECONOMIC_OVERSUBSCRIPTION = FORBIDDEN`、`L5_WITHDRAWAL_CONCURRENCY = PASS`、`L5_PREDICTION_STAKE_CONCURRENCY = PASS`。

### P2-1：PRE_HOLD_MUTATION_GUARD

- 落点：`design.md` A.1.2（D 节）+ Freeze `§3.1`。
- 证据：`SHORTFALL_CHECK_PHASE` 重命名为通用 `PRE_HOLD_MUTATION_GUARD`；显式适用 `L4（pending DEBIT→disputed，PENDING_DEBIT_SHORTFALL_PRECHECK）` + `L5（posted CREDIT→disputed，POSTED_CREDIT_SHORTFALL_PRECHECK）`。
- 判定公式：`shortfall = max(0, projected_aggregate_hold - stored_available)`；`projected_effective_available < 0 → L4/L5 = DENY`。

### P2-2：并发错误码统一

- 落点：`design.md` A.1.2（D 节）+ Freeze `§3.1`。
- 证据：`ACCOUNT_CONFLICT_API_CODE = OBJECT_VERSION_CONFLICT`（HTTP 409）；`ACCOUNT_LOCK_CONFLICT` 若保留仅 `INTERNAL_ONLY=YES` + `API_ERROR_MAPPING=OBJECT_VERSION_CONFLICT`。

### P2-3：Review 证据完整性

- 落点：`acceptance.md` IR 686 P2-3 行 + Freeze `§8`。
- 证据：门禁保持 `REVIEW_PACKAGE_TRUNCATED = NO`；本轮已由 Development Agent 直接生成完整未截断 diff（`DIFF.txt`，41930 字符），不再依赖工具 `get_latest_commit` 的 25000 硬截断。

## 遗留与边界

- MC2 仍未 `FROZEN`：状态为 `IMPLEMENTED / RE_REVIEW_PENDING`，需本复审 `APPROVED` 后置 FROZEN。
- 未定义维度（shortfall 后是否生成 RiskCase、账户是否 restricted、OTC/Withdrawal/Robot 是否禁启、是否需 ApprovalRequest）仍 deferred 至 2B-2。
- 本包不修改产品业务代码；不触发 STAGE-01 骨架 FAIL_CLOSED 解除。
