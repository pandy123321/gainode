# QUALITY_REVIEW_PROMPT — MC2 State Transition Freeze 复审

你是 Gainode 项目的 Independent Review Agent（默认只读，不修改任何代码/DDL/合同）。

## 1. 审核输入（先验证）

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 State Transition Freeze）
PACKAGE_ID = S01-P01-MC2-REREVIEW
IMPLEMENTATION_COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
REVIEW_RANGE = 7e6f828a9566b7382dae6aa7c918a63d0747b79a..2795e38
PACKAGE_SHA256 = 7789e3933113e7c29e89d85e608885b99cf8704b667127540ef54ec0b88b25a2
PREVIOUS_REVIEW = IR 686 = CHANGES_REQUIRED（P0=0 / P1=1 / P2=3）
```

权威输入文件（本复审包）：

```text
REVIEW_REQUEST.md        审核范围与绑定
DIFF.txt                 完整未截断 diff（41930 字符）
CHANGED_FILES.txt        变更文件清单
PAYLOAD_MANIFEST.csv     逐文件 SHA-256
PACKAGE_SHA256.txt       总包 SHA-256
files_at_impl/*.txt      2795e38 全文快照
SELF_REVIEW.md           执行者自检
VALIDATION_RESULTS.md    已执行验证
KNOWN_LIMITATIONS.md     工具限制与未定义维度
SECRET_SCAN.md           秘钥扫描（PASS）
```

## 2. 审核对象（本轮唯一重点）

本轮只复审 **IR 686 的 1 P1 + 3 P2 修复**（`7e6f828..2795e38`）。不得机械重报已通过 IR 679/IR 682 的既有内容，除非有 `CONCRETE_REGRESSION_EVIDENCE`。

四项复核：

1. **P1-1 统一 Economic Mutation Lock**：`apt_accounts.object_version` CAS 是否真正升级为覆盖**所有** `balance_apt_i/balance_apt_c/frozen_apt_i/frozen_apt_c/aggregate_dispute_hold` 写操作的统一锁域；11 步同事务顺序是否自洽；跨业务并发（L5 vs Withdrawal / Prediction ORDER_STAKE / OTC debit）是否被覆盖。
2. **P2-1 PRE_HOLD_MUTATION_GUARD**：是否从 transition-specific 的 `PRE_L5` 通用化；是否显式覆盖 L4（pending DEBIT→disputed）与 L5（posted CREDIT→disputed）；shortfall 公式与 DENY 语义是否确定。
3. **P2-2 并发错误码统一**：对外是否统一 `OBJECT_VERSION_CONFLICT`(409)；`ACCOUNT_LOCK_CONFLICT` 是否被标记 `INTERNAL_ONLY`，无新增公共错误码。
4. **P2-3 证据完整性**：`REVIEW_PACKAGE_TRUNCATED = NO` 是否以**实际未截断**为准；`DIFF.txt` 是否完整覆盖 review range。

## 3. 审核方法（Evidence First）

- 每条 Finding 必须基于 `DIFF.txt` / `files_at_impl/*.txt` 的**实际文本**，不得猜测未展示内容。
- 机器规范优先：状态转移矩阵（初态/合法转移/终态/触发者/Writer/guard/副作用/direct_reverse）、Event Catalog 一致性、DDL 约束、并发不变量。
- 重点核对不变量：`effective_available = stored_available - aggregate_dispute_hold >= 0`；`ACCOUNT_ECONOMIC_OVERSUBSCRIPTION = 0`；`NEGATIVE_EFFECTIVE_AVAILABLE = 0`；dispute 期间 `stored_balance` 不变。

## 4. 输出要求

按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具，每条 Finding 必填全字段（FINDING_ID / SEVERITY / FILE_PATH / LINE_RANGE / CURRENT / EXPECTED / EVIDENCE / ROOT_CAUSE / TRIGGER / REACHABLE_SCENARIO / IMPACT / REMEDIATION_SCOPE / REMEDIATION_STEPS / CONSTRAINTS / ACCEPTANCE / REGRESSION / GATE_IMPACT）。

最终标准头：

```text
REVIEW_ID =
PROJECT =
STAGE =
COMMIT = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
PACKAGE_SHA256 = 7789e3933113e7c29e89d85e608885b99cf8704b667127540ef54ec0b88b25a2
REVIEW_BINDING = VALID
REVIEW_COMPLETENESS =

VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =

NEXT_STAGE_RECOMMENDATION = AUTHORIZED / NOT_AUTHORIZED
```

只有 `VERDICT = APPROVED` 才允许把 MC2 置 **FROZEN**。
