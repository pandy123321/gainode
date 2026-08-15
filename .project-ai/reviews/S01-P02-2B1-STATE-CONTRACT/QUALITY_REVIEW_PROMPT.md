# QUALITY_REVIEW_PROMPT — S01-P02 · 2B-1 状态合同补齐

你是 Gainode 项目的 Independent Review Agent（默认只读，不修改任何代码/DDL/合同）。

## 1. 审核输入（先验证）

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-1 小批状态合同）
PACKAGE_ID = S01-P02-2B1-STATE-CONTRACT
IMPLEMENTATION_COMMIT = c2d57ce1eec2c30ff076d04aac6256f1ad4b18e0
BASE_COMMIT = 4bcf80fee4cb9603688465261a0ab5091eab7e07
REVIEW_RANGE = 4bcf80f..c2d57ce
PACKAGE_SHA256 = eba2536266f9950605ba4aa599cd3ecd286e17a5998ebd215c3781623cf2a2df
```

权威输入文件（本复审包）：

```text
REVIEW_REQUEST.md        审核范围与绑定
DIFF.txt                 完整未截断 diff（25522 字符）
CHANGED_FILES.txt        变更文件清单（3 新增文件）
PAYLOAD_MANIFEST.csv     逐文件 SHA-256
PACKAGE_SHA256.txt       总包 SHA-256
files_at_impl/*.txt      c2d57ce 全文快照（3 文件）
SELF_REVIEW.md           执行者自检
VALIDATION_RESULTS.md    已执行验证
KNOWN_LIMITATIONS.md     工具限制与未定义维度
SECRET_SCAN.md           秘钥扫描（PASS）
```

## 2. 审核对象（固定 9 对象）

### 2.1 Result（enum 复制 05 §4）

- 核对 enum 是否严格等于 05 §4 canonical `provisional / official / disputed / corrected`，**未新增状态值**。
- 核对转移矩阵 RS1-RS5 是否每项定义：初态、合法转移、终态、触发者、Authoritative Writer、幂等、并发、审计、账本效果。
- 核对非法转移 FAIL_CLOSED 列表（`corrected → *`、`official → provisional`、跨级跳转）。
- 核对不变量：**Result `official` ≠ Settlement `paid`**；`corrected` 仅一次（MC2 Owner 裁决 #11）。

### 2.2 Settlement（enum 复制 05 §4）

- 核对 enum 严格等于 05 §4 canonical `queued / calculating / review / payable / paid / failed`，**未新增状态值**。
- 核对转移矩阵 ST1-ST7 每项字段完整性。
- 核对非法转移 FAIL_CLOSED（`paid → *`、`failed → payable/paid`、`review → paid`）。
- 核对 SoD：**Result confirmer ≠ Settlement approver**（ST4 触发者 = RISK_APPROVER，与 Result confirmer 分离）。
- 核对账本效果对齐结算会计矩阵（WIN=本金+盈利 CREDIT；PUSH=本金 CREDIT；LOSS=NO_LEDGER_ENTRY）。

### 2.3 6 缺 enum 实体（SettlementBatch/RefundCase/CorrectionCase/OtcTrade/RobotUpgradeOrder/ConsentReceipt）

- 核对仅生成 **Owner Decision Matrix（2B1-ENUM-01..06）**，每项含 DECISION_ID / OPTION_A / OPTION_B / RECOMMENDED / RISK / RESUME_CONDITION。
- 核对 **未自创状态**、**未自创角色**、**未建 ENUM 表**。
- 核对 design.md **D.7 候选状态合同摘要**标注「候选/非冻结」，未宣称冻结。
- 核对 6 实体全部 `CONTRACT_GAP / FAIL_CLOSED`。

### 2.4 AuditEvent

- 核对 **复用 MC2 `audit_events` DDL**，未重复 CREATE TABLE，未改 append-only 约束。

## 3. 审核方法（Evidence First）

- 每条 Finding 必须基于 `DIFF.txt` / `files_at_impl/*.txt` 的**实际文本**，不得猜测未展示内容。
- 机器规范优先：与 MC2 Freeze 已冻结协同关系（M6/M7/M9/M10/M12、P3/P5/P6/P7/P10/P11/P12、结算会计矩阵）逐一比对，不得出现矛盾。
- 重点核对：状态机初态/合法转移/终态/触发者/Writer/guard/副作用/direct_reverse 是否自洽；是否引入 05 §8 之外的角色；是否把未冻结状态当作已冻结。

## 4. 输出要求

按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具，每条 Finding 必填全字段（FINDING_ID / SEVERITY / FILE_PATH / LINE_RANGE / CURRENT / EXPECTED / EVIDENCE / ROOT_CAUSE / TRIGGER / REACHABLE_SCENARIO / IMPACT / REMEDIATION_SCOPE / REMEDIATION_STEPS / CONSTRAINTS / ACCEPTANCE / REGRESSION / GATE_IMPACT）。

最终标准头：

```text
REVIEW_ID =
PROJECT =
STAGE =
COMMIT = c2d57ce1eec2c30ff076d04aac6256f1ad4b18e0
PACKAGE_SHA256 = eba2536266f9950605ba4aa599cd3ecd286e17a5998ebd215c3781623cf2a2df
REVIEW_BINDING = VALID
REVIEW_COMPLETENESS =

VERDICT = APPROVED / CHANGES_REQUIRED
P0_OPEN =
P1_OPEN =
P2_OPEN =
P3_OPEN =

NEXT_PACKAGE_RECOMMENDATION = S01-P03_AUTHORIZED / NOT_AUTHORIZED
```

说明：

- Result/Settlement 转移矩阵为**候选**，需 State Machine gate 通过后才视为该两对象 FROZEN。
- 6 缺 enum 实体在 Owner 裁决前保持 `FAIL_CLOSED`，本审核不将其置 FROZEN。
