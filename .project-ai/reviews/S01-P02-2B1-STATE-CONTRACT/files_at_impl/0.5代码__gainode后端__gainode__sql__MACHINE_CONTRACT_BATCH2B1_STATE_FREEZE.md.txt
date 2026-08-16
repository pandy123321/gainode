# Machine Contract 第二批 2B-1 — State Contract Freeze（候选）

> 状态：**CANDIDATE（未 FROZEN）** — Owner Signoff ✅（6 缺 enum 实体已逐项裁决 2026-08-16，全部采纳各 D.x RECOMMENDED_OPTION，已补入 05 §4 V2.3）；Independent Review 未开始（Result/Settlement 转移矩阵 + 6 实体 enum 待 State Machine gate）。
> 说明：本文件为 Machine Contract 第二批 **2B-1 小批**（9 对象）的状态合同冻结候选。Result/Settlement enum 复制 05 §4（已冻结）；6 缺 enum 实体经 Owner 裁决后补入 05 §4；AuditEvent 复用 MC2 `audit_events` DDL。转移矩阵均为**候选**，正式 FROZEN 前须重提 Independent Review（State Machine gate）并通过。
> 起草日期：2026-08-16
> 关联 DDL：无（本批不生成 DDL，属 S01-P03；`audit_events` 复用 `20260815_machine_contract_batch2_audit_events.sql`）
> 权威契约：`Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3 对象字段 / §4 统一状态机 / §8 RBAC）
> 前置冻结：`MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`（MC1）、`MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`（MC2，协同关系权威源）
> 任务：`.project-ai/tasks/TASK-20260816-001/`

## 1. 冻结范围

本批冻结 **2B-1 小批 9 个对象的状态合同**（enum + 状态转移矩阵候选）。冻结后，这 9 个对象的状态流转由本文件授权，非法流转 FAIL_CLOSED。

| 对象 | 类型 | enum 来源 | 状态 |
|---|---|---|---|
| Result | 工作流对象 | 复制 05 §4 `provisional / official / disputed / corrected` | 候选（转移矩阵待 gate） |
| Settlement | 工作流对象 | 复制 05 §4 `queued / calculating / review / payable / paid / failed` | 候选（转移矩阵待 gate） |
| SettlementBatch | 工作流对象 | Owner 裁决 2B1-ENUM-01 | 候选 |
| RefundCase | 工作流对象 | Owner 裁决 2B1-ENUM-02 | 候选 |
| CorrectionCase | 工作流对象 | Owner 裁决 2B1-ENUM-03 | 候选 |
| OtcTrade | 持久领域实体 | Owner 裁决 2B1-ENUM-04（单态 append-only） | 候选 |
| RobotUpgradeOrder | 工作流对象 | Owner 裁决 2B1-ENUM-05 | 候选 |
| ConsentReceipt | 持久领域实体 | Owner 裁决 2B1-ENUM-06（两态） | 候选 |
| AuditEvent | 持久领域实体 | 复用 MC2 `audit_events` DDL | 复用（不重复创建） |

**不包含**（拆出本批，另行交付）：
- 2B-1 的 DDL（属 S01-P03）。
- 2B-2 对象（ApprovalRequest/ParameterRelease/Notice/… 属 S01-P04）。
- 非持久投影（S01-P06）。

## 2. 角色映射（05 §8 canonical，不自创角色）

- Result 确认/发起 → **OPS_OPERATOR**；Result 争议裁决/纠错审批 → **RISK_APPROVER**。
- Settlement 复核/批准 → **RISK_APPROVER**（与 Result confirmer 分离，SoD）。
- RefundCase/CorrectionCase 发起 → **OPS_OPERATOR**；审批 → **RISK_APPROVER**。
- OtcTrade 撮合成交 → 系统；争议裁决 → **RISK_APPROVER**（作用于 OtcOrder，不覆盖 Trade）。
- RobotUpgradeOrder 发起 → **END_USER**；大额人工确认 → **OPS_OPERATOR + RISK_APPROVER**（MC2 Owner 裁决 #13）。
- ConsentReceipt 同意 → **END_USER**；到期 → 系统。
- 对账只读 → **FINANCE_REVIEWER**（不可写）。
- 审计只读 → **AUDITOR**。

> ⚠️ 职责分离提醒：本项目 11 角色由 OWNER 单人兼任（manifest `p1_004_owner_freeze`）。系统层面 `OPS_OPERATOR(发起) ≠ RISK_APPROVER(审批)` 的角色分离仍成立；若同一自然人同时持有两角色并自审自批，须满足 `p1_010_override_contract`。

## 3. Result 状态合同

> canonical enum（05 §4，已冻结）：`provisional / official / disputed / corrected`
> 关键不变量：**Result `official` ≠ Settlement `paid`**；`corrected` 仅一次（MC2 Owner 裁决 #11）。

### 3.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `provisional` |
| TRUE_TERMINAL | `corrected` |
| STABLE_WITH_EXCEPTION_TRANSITIONS | `official`（可再 `disputed`/`corrected`） |
| INTERMEDIATE | `disputed` |

### 3.2 转移矩阵（候选）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 账本效果 |
|---|---|---|---|---|---|---|
| RS1 | `provisional` → `official` | RESULT_CONFIRMED | OPS_OPERATOR | 主备源一致、字段完整、`event_id` 有效 | ResultService | 无；触发 Market M6 |
| RS2 | `official` → `disputed` | RESULT_DISPUTED | OPS_OPERATOR 发起 + RISK_APPROVER 裁决 | `RiskCase` 存在 | ResultService | 冻结结算（不进 `payable`） |
| RS3 | `disputed` → `official` | DISPUTE_UPHELD | RISK_APPROVER | 裁决维持原结果 | ResultService | 恢复结算 |
| RS4 | `disputed` → `corrected` | DISPUTE_CORRECTED | RISK_APPROVER | 裁决纠错 | ResultService | 触发 CorrectionCase |
| RS5 | `official` → `corrected` | RESULT_CORRECTED | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 仅 settlement error；审批通过；**仅一次** | ResultService | 触发 Market M12 + Order P7 |

**非法转移（FAIL_CLOSED）**：`corrected → *`、`official → provisional`、`disputed → provisional`、`provisional → corrected`（越级）。

## 4. Settlement 状态合同

> canonical enum（05 §4，已冻结）：`queued / calculating / review / payable / paid / failed`

### 4.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `queued` |
| TRUE_TERMINAL | `paid`（触发 Market M7） |
| PAUSED_NOT_TERMINAL | `failed`（可重试，需人工介入） |
| INTERMEDIATE | `calculating / review / payable` |

### 4.2 转移矩阵（候选）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 账本效果 |
|---|---|---|---|---|---|---|
| ST1 | `queued` → `calculating` | SETTLEMENT_STARTED | 系统 | `Market=settlement` 且 `Result=official` | SettlementService | 无 |
| ST2 | `calculating` → `payable` | SETTLEMENT_CALCULATED | 系统 | 计算完成、金额确定、无复核触发 | SettlementService | 无 |
| ST3 | `calculating` → `review` | SETTLEMENT_REVIEW_REQUIRED | 系统 | 触发条件（大额/异常，参数 TBC） | SettlementService | 冻结支付 |
| ST4 | `review` → `payable` | SETTLEMENT_REVIEW_APPROVED | RISK_APPROVER | 复核通过；与 Result confirmer 分离 | SettlementService | 放行支付 |
| ST5 | `payable` → `paid` | SETTLEMENT_PAID | 系统 | 账本过账成功 | SettlementService | **CREDIT 赢家**（结算会计矩阵）；触发 Market M7 |
| ST6 | `queued/calculating/review/payable` → `failed` | SETTLEMENT_FAILED | 系统 | 计算/支付异常 | SettlementService | 冻结；触发 Market M9 |
| ST7 | `failed` → `queued` | SETTLEMENT_RETRY | OPS_OPERATOR | `case_id` + 理由 + 恢复条件满足 | SettlementService | 重试结算 |

**非法转移（FAIL_CLOSED）**：`paid → *`、`failed → payable/paid`（必须先回 `queued`）、`review → paid`（必须经 `payable`）、`calculating → queued`。

## 5. 6 缺 enum 实体状态合同（enum 已 Owner 裁决，转移候选）

> 依据 S01-P02 步骤 3，每项必须列：初态、合法转移、终态、触发者、Writer、失败态、重试、幂等、审计、账本效果。以下 enum 已 Owner 裁决并补入 05 §4（V2.3），转移为候选，未 FROZEN。触发者仅用 05 §8 已有角色。

### 5.1 SettlementBatch — `created / processing / completed / partially_failed / failed`

```text
初态 = created
合法转移（候选）= created→processing→completed / processing→partially_failed / partially_failed→processing(重试) / *→failed
终态 = completed / failed
触发者 = 系统；异常人工复核 = OPS_OPERATOR + RISK_APPROVER
Writer = SettlementBatchService
失败态 = failed / partially_failed
重试 = partially_failed→processing
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = 无直接（聚合 Settlement；Settlement=paid 才写账）
```

### 5.2 RefundCase — `pending / approved / executing / completed / rejected / failed`

```text
初态 = pending
合法转移（候选）= pending→approved→executing→completed / pending→rejected / executing→failed
终态 = completed / rejected
触发者 = OPS_OPERATOR 发起 + RISK_APPROVER 审批
Writer = RefundCaseService
失败态 = failed
重试 = failed→executing
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = ORDER_REFUND（+本金 CREDIT，退款范围 = 全额本金，对齐 MC2 P5/P10/P11/P12）
```

### 5.3 CorrectionCase — `pending / approved / executing / completed / rejected / failed`

```text
初态 = pending
合法转移（候选）= pending→approved→executing→completed / pending→rejected / executing→failed
终态 = completed / rejected
触发者 = OPS_OPERATOR 发起 + RISK_APPROVER 审批
Writer = CorrectionCaseService
失败态 = failed
重试 = failed→executing
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = ORDER_CORRECTION（双向，reversal 旧 + 新增，对齐 MC2 P7/P8 + 结算会计矩阵）
```

### 5.4 OtcTrade — `completed`（append-only 单态）

```text
初态 = completed（单态，见 2B1-ENUM-04）
合法转移 = 无（append-only 成交事实）；争议/冲正走 RiskCase + ledger reversal，不覆盖 Trade
终态 = completed
触发者 = 系统（撮合成交自动生成）；争议裁决 = RISK_APPROVER（作用于 OtcOrder）
Writer = OtcTradeService
失败态 = 无
重试 = 无
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = OTC_TRADE（buyer DEBIT + seller CREDIT）+ Power 消耗，对齐 MC2 O6/O9
```

### 5.5 RobotUpgradeOrder — `pending / processing / completed / failed / cancelled`

```text
初态 = pending
合法转移（候选）= pending→processing→completed / pending→cancelled / processing→failed
终态 = completed / cancelled / failed
触发者 = END_USER 发起；大额人工确认 = OPS_OPERATOR + RISK_APPROVER（MC2 Owner 裁决 #13）
Writer = RobotUpgradeOrderService
失败态 = failed
重试 = failed→processing
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = ROBOT_UPGRADE（DEBIT apt_cost）+ Power Cap 更新
```

### 5.6 ConsentReceipt — `active / expired`（两态）

```text
初态 = active
合法转移（候选）= active→expired（撤回/取代不新增状态值，由新版本 receipt + consent_version 表达）
终态 = expired
触发者 = END_USER 同意（active）；系统（到期 expired）
Writer = ConsentReceiptService
失败态 = 无
重试 = 无
幂等 = idempotency_key（consent_type + consent_version 去重）
审计 = append audit_events
账本副作用 = 无（consent 不产生账本分录）
```

## 6. AuditEvent 复用声明

- **复用 MC2 已冻结候选 `audit_events` DDL**（`20260815_machine_contract_batch2_audit_events.sql` + MC2 Freeze §6）。
- **不重复创建**、不新增字段、不修改 append-only 约束。
- 2B-1 各实体的审计事件全部写入 `audit_events`，通过 `target_object_type` + `target_object_id` 关联。
- 敏感写操作实体（Result/Settlement/OtcTrade/RobotUpgradeOrder）预留 `audit_event_id` 指针列（S01-P03 落实），与 `apt_ledger_entries.audit_event_id` 同机制。

## 7. 跨实体协同（对齐 MC2 已冻结关系）

| 联动 | 依据（MC2 Freeze） |
|---|---|
| `Result=official` → Market M6（`awaiting_result`→`settlement`） | MC2 §3.4 |
| `Settlement=paid` → Market M7（`settlement`→`settled`）→ Order P4 | MC2 §3.4/§3.5 |
| `Settlement=failed` → Market M9 → Order P5（`settling`→`refunding`，RefundCase 审批） | MC2 §3.4/§3.5/§3.7 |
| `Result=corrected` → Market M12 → Order P7/P8（`settled`→`correcting`→`corrected`）；经 CorrectionCase | MC2 §3.4/§3.5/§3.7 |
| `Market=void` → Order P10/P11/P12 → `refunding` → RefundCase | MC2 §3.7 |
| `OtcOrder=completed` → 生成 `OtcTrade` → Ledger 分录（`OTC_TRADE`）+ Power 消耗/释放 | MC2 §3.6/§5 |

> 结算会计矩阵（MC2 §5，权威）：`WIN`=CREDIT 本金+盈利；`LOSS`=NO_LEDGER_ENTRY（stake 已 DEBIT）；`PUSH`=CREDIT 本金。任一订单净账本效果仅一次且方向确定。

## 8. 通用工程约束（S01-P03 建 DDL/骨架时落实）

| 维度 | 约束 |
|---|---|
| 主键 | Snowflake bigint unsigned（`$incrementing=false`，`$keyType='string'`） |
| 并发 | 每表 `object_version int unsigned` CAS 乐观锁（对应 05 If-Match / `OBJECT_VERSION_CONFLICT 409`） |
| 幂等 | 每表 `idempotency_key varchar(64) UNIQUE` 可空 |
| 审计 | 敏感写表 `audit_event_id` 指针 + append `audit_events` |
| 金额 | `decimal(36,18)`（APT 数量）／`decimal(18,8)`（price/系数）／`decimal(18,4)`（Power） |
| 时间 | `created_time/updated_time` int unsigned（Unix 秒） |
| 状态列 | 领域状态用 ENUM（冻结 enum 后）；05 未定义前 `varchar(32) NULL` + FAIL_CLOSED |
| 失败安全 | 转移矩阵未冻结一律 FAIL_CLOSED，不建表、不写业务 |

## 9. 冻结状态与 gate

```text
OWNER_SIGNOFF = COMPLETE（6 缺 enum 实体 2026-08-16 逐项裁决）
05_SECTION4_SUPPLEMENT = DONE（V2.3）
INDEPENDENT_REVIEW = PENDING（State Machine gate）
FROZEN_STATUS = CANDIDATE
RESULT_ENUM = provisional/official/disputed/corrected（复制 05 §4，未新增）
SETTLEMENT_ENUM = queued/calculating/review/payable/paid/failed（复制 05 §4，未新增）
OWNER_DECISION_MATRIX_COUNT = 6
NO_SELF_INVENTED_STATE = YES
NO_SELF_INVENTED_ROLE = YES
AUDIT_EVENTS_REUSED_NOT_RECREATED = YES
```

正式 FROZEN 前须重提 Independent Review（State Machine gate）并通过。

## 信息来源

- 05 §3（对象字段）/§4（统一状态机）/§8（RBAC）
- MC1 Freeze §3.6/§3.7/§3.9
- MC2 Freeze §3.4/§3.5/§3.6/§3.7/§5/§6
- `.project-ai/tasks/TASK-20260816-001/design.md`（Part A/B/D/E）
- `manifest.yaml` decisionSources `machine_contract_batch2b1_state_contract`
