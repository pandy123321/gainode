# QUALITY_REVIEW_PROMPT — S01-P02 · 2B-1 状态合同补齐（含 Owner enum 裁决）

你是 Gainode 项目的 Independent Review Agent（默认只读，不修改任何代码/DDL/合同）。

## 1. 审核输入（先验证）

```text
PROJECT = Gainode
STAGE = STAGE-01（Machine Contract 第二批 2B-1 小批状态合同）
PACKAGE_ID = S01-P02-2B1-STATE-CONTRACT
IMPLEMENTATION_COMMIT = a32918c
BASE_COMMIT = 4bcf80fee4cb9603688465261a0ab5091eab7e07
REVIEW_RANGE = 4bcf80f..a32918c
PACKAGE_SHA256 = 4cb17ee80e5cd47d802181fafe71b8ee4f046d0e4caaa24a86f92765cd64d20f
```

权威输入文件（本复审包）：

```text
REVIEW_REQUEST.md        审核范围与绑定
DIFF.txt                 完整未截断 diff（42282 字符）
CHANGED_FILES.txt        变更文件清单（5 文件）
PAYLOAD_MANIFEST.csv     逐文件 SHA-256
PACKAGE_SHA256.txt       总包 SHA-256
files_at_impl/*.txt      a32918c 全文快照（5 文件）
SELF_REVIEW.md           执行者自检
VALIDATION_RESULTS.md    已执行验证
KNOWN_LIMITATIONS.md     工具限制与未定义维度
SECRET_SCAN.md           秘钥扫描（PASS）
```

## 2. 审核对象（固定 9 对象）

### 2.1 Result（enum 复制 05 §4）

- 核对 enum 严格等于 05 §4 canonical `provisional / official / disputed / corrected`，**未新增状态值**。
- 核对转移矩阵 RS1-RS5 每项字段完整性（初态/合法转移/终态/触发者/Writer/幂等/并发/审计/账本效果）。
- 核对非法转移 FAIL_CLOSED（`corrected → *`、`official → provisional`、`disputed → provisional`、`provisional → corrected`）。
- 核对不变量：**Result `official` ≠ Settlement `paid`**；`corrected` 仅一次（MC2 Owner 裁决 #11）。

### 2.2 Settlement（enum 复制 05 §4）

- 核对 enum 严格等于 05 §4 canonical `queued / calculating / review / payable / paid / failed`，**未新增状态值**。
- 核对转移矩阵 ST1-ST7 每项字段完整性。
- 核对非法转移 FAIL_CLOSED（`paid → *`、`failed → payable/paid`、`review → paid`、`calculating → queued`）。
- 核对 SoD：**Result confirmer ≠ Settlement approver**（ST4 触发者 = RISK_APPROVER）。
- 核对账本效果对齐结算会计矩阵（WIN/PUSH/LOSS）。

### 2.3 6 缺 enum 实体（SettlementBatch/RefundCase/CorrectionCase/OtcTrade/RobotUpgradeOrder/ConsentReceipt）

- 核对 Owner 裁决 enum 已正确补入 05 §4（V2.3），与 Freeze Candidate §5 摘要一致。
- 核对 enum 值（见 REVIEW_REQUEST「Owner 裁决」段）。
- 核对状态合同摘要每项字段完整性（初态/合法转移/终态/触发者/Writer/失败态/重试/幂等/审计/账本效果）。
- 核对 **未自创状态、未自创角色**，转移矩阵标注「候选/未 FROZEN」。

### 2.4 AuditEvent

- 核对 **复用 MC2 `audit_events` DDL**，未重复 CREATE TABLE，未改 append-only 约束。

## 3. 审核方法（Evidence First）

- 每条 Finding 必须基于 `DIFF.txt` / `files_at_impl/*.txt` 的**实际文本**。
- 机器规范优先：与 MC2 Freeze 已冻结协同关系（M6/M7/M9/M10/M12、P3/P5/P6/P7/P10/P11/P12、结算会计矩阵）逐一比对。
- 重点核对：enum 是否与 05 §4 完全一致；是否引入 05 §8 之外的角色；是否把候选转移矩阵当作已冻结。

## 4. 输出要求

按 `INDEPENDENT_REVIEW_AGENT_PROTOCOL.md` 出具，每条 Finding 必填全字段。

最终标准头：

```text
REVIEW_ID =
PROJECT =
STAGE =
COMMIT = a32918c
PACKAGE_SHA256 = 4cb17ee80e5cd47d802181fafe71b8ee4f046d0e4caaa24a86f92765cd64d20f
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

- enum 已确定（复制 + Owner 裁决），但**转移矩阵候选**，State Machine gate 通过后才视为 FROZEN。
- S01-P03 在转移矩阵未 FROZEN 前仍以 fail-closed 骨架落地，不实现业务。
