# Design: Machine Contract 第二批 2B-1（状态合同补齐）

## 状态

- **Owner Signoff：完成（6 实体 enum 已逐项裁决，2026-08-16，全部采纳各 D.x 的 RECOMMENDED_OPTION）**
- **Independent Review：未开始（Result/Settlement 转移矩阵候选 + 6 实体 enum，待 State Machine gate）**
- **冻结状态：CANDIDATE（未 FROZEN）**

## 权威依据与角色（05 canonical）

- 角色（05 §8，MC2 §2 已确认）：`OPS_OPERATOR`（运营，含 Result 确认/发起）、`RISK_APPROVER`（财务裁决/审批）、`FINANCE_REVIEWER`（对账只读）、`LEDGER_OPERATOR`、`AUDITOR`、`END_USER`、`ADMIN_SECURITY`（不涉财）。
- 职责分离（05 §8 已确认）：**Result 确认 ≠ Settlement 批准**；申请人不得审批本人申请。
- 冻结规则：本文件不新增 canonical state、不新增角色；Result/Settlement enum 复制自 05 §4。

---

## Part A — Result 状态合同（enum 复制 05 §4）

> canonical enum（05:752，已冻结）：`provisional / official / disputed / corrected`
> 语义（05 §4 预测聚合映射）：`provisional` = 结果待确认；`official` = 已确认（触发结算）；`disputed` = 争议（冻结结算）；`corrected` = 纠错后（触发重结算）。
> 关键不变量：**Result `official` ≠ Settlement `paid`**（05 §4 已作废/已取消说明 + 结算会计矩阵）。

### A.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `provisional` |
| TRUE_TERMINAL | `corrected`（Result corrected 重开仅一次，MC2 Owner 裁决 #11） |
| STABLE_WITH_EXCEPTION_TRANSITIONS | `official`（可再 `disputed`/`corrected`） |
| INTERMEDIATE | `disputed`（需 RISK_APPROVER 裁决后离开） |

### A.2 状态转移矩阵（候选，待 State Machine gate）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 幂等 | 并发 | 审计 | 账本效果 |
|---|---|---|---|---|---|---|---|---|---|
| RS1 | `provisional` → `official` | RESULT_CONFIRMED | OPS_OPERATOR（Result confirmer） | 主备源一致、字段完整、`event_id` 有效 | ResultService | `idempotency_key` 防重（重复确认返回已确认，不再触发） | `object_version` CAS | append audit | 无；触发 Market M6（`awaiting_result`→`settlement`） |
| RS2 | `official` → `disputed` | RESULT_DISPUTED | OPS_OPERATOR 发起 + RISK_APPROVER 裁决 | `RiskCase` 存在（对账差异） | ResultService | `idempotency_key` 防重 | `object_version` CAS | append audit | 冻结结算（Settlement 停于当前中间态；不进 `payable`） |
| RS3 | `disputed` → `official` | DISPUTE_UPHELD | RISK_APPROVER | 裁决维持原结果 | ResultService | `idempotency_key` 防重 | `object_version` CAS | append audit | 恢复结算 |
| RS4 | `disputed` → `corrected` | DISPUTE_CORRECTED | RISK_APPROVER | 裁决纠错 | ResultService | `idempotency_key` 防重 | `object_version` CAS | append audit | 触发 CorrectionCase（见 Part C） |
| RS5 | `official` → `corrected` | RESULT_CORRECTED | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 仅 settlement error；审批通过；**仅一次** | ResultService | `idempotency_key` 防重 | `object_version` CAS | append audit | 触发 Market M12（`settled`→`settlement` 重开）+ Order P7（`settled`→`correcting`） |

**非法转移（FAIL_CLOSED）**：`corrected → *`（终态不可再转移）；`official → provisional`；`disputed → provisional`；跨状态越级（如 `provisional → corrected`）。

---

## Part B — Settlement 状态合同（enum 复制 05 §4）

> canonical enum（05:755，已冻结）：`queued / calculating / review / payable / paid / failed`
> 语义（05 §4 预测聚合映射）：`queued/calculating/payable` = 结算处理中；`paid` = 已结算（唯一"已结算"真值）；`review` = 人工复核；`failed` = 结算失败（异常）。

### B.1 初态与终态

| 项 | 值 |
|---|---|
| 初态 | `queued` |
| TRUE_TERMINAL | `paid`（结算完成，触发 Market M7 `settlement`→`settled`） |
| PAUSED_NOT_TERMINAL | `failed`（可经重试恢复，需人工介入） |
| INTERMEDIATE | `calculating / review / payable` |

### B.2 状态转移矩阵（候选，待 State Machine gate）

| ID | 从 → 到 | 触发事件 | 触发者 | Guard | Writer | 幂等 | 并发 | 审计 | 账本效果 |
|---|---|---|---|---|---|---|---|---|---|
| ST1 | `queued` → `calculating` | SETTLEMENT_STARTED | 系统 | `Market=settlement` 且 `Result=official` | SettlementService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无（分录在 ST5） |
| ST2 | `calculating` → `payable` | SETTLEMENT_CALCULATED | 系统 | 计算完成、金额确定、无复核触发 | SettlementService | `idempotency_key` 防重 | `object_version` CAS | append audit | 无（分录在 ST5） |
| ST3 | `calculating` → `review` | SETTLEMENT_REVIEW_REQUIRED | 系统 | 触发条件（大额/异常，生产参数 TBC） | SettlementService | `idempotency_key` 防重 | `object_version` CAS | append audit | 冻结支付 |
| ST4 | `review` → `payable` | SETTLEMENT_REVIEW_APPROVED | RISK_APPROVER（Settlement approver） | 复核通过；**与 Result confirmer 分离** | SettlementService | `idempotency_key` 防重 | `object_version` CAS | append audit | 放行支付 |
| ST5 | `payable` → `paid` | SETTLEMENT_PAID | 系统 | 账本过账成功 | SettlementService | `idempotency_key` 防重 | `object_version` CAS | append audit | **CREDIT 赢家（结算会计矩阵：WIN=本金+盈利；PUSH=本金；LOSS=无分录）**；触发 Market M7 |
| ST6 | `queued`/`calculating`/`review`/`payable` → `failed` | SETTLEMENT_FAILED | 系统 | 计算/支付异常 | SettlementService | `idempotency_key` 防重 | `object_version` CAS | append audit | 冻结；触发 Market M9（`settlement`→`exception`） |
| ST7 | `failed` → `queued` | SETTLEMENT_RETRY | OPS_OPERATOR | `case_id` + 理由 + 恢复条件满足 | SettlementService | `idempotency_key` 防重 | `object_version` CAS | append audit | 重试结算 |

**非法转移（FAIL_CLOSED）**：`paid → *`（终态）；`failed → payable/paid`（必须先回 `queued` 重走）；`review → paid`（必须经 `payable`）；`calculating → queued`。

---

## Part C — 2B-1 跨实体协同（对齐 MC2 已冻结关系）

| 联动 | 依据（MC2 Freeze） |
|---|---|
| `Result=official` → Market M6（`awaiting_result`→`settlement`） | MC2 §3.4 |
| `Settlement=paid` → Market M7（`settlement`→`settled`）→ Order P4（`settling`→`settled`） | MC2 §3.4/§3.5 |
| `Settlement=failed` → Market M9（`settlement`→`exception`）→ Order P5（`settling`→`refunding`，RefundCase 审批） | MC2 §3.4/§3.5/§3.7 |
| `Result=corrected` → Market M12（`settled`→`settlement`）→ Order P7/P8（`settled`→`correcting`→`corrected`）；经 CorrectionCase | MC2 §3.4/§3.5/§3.7 |
| `Market=void` → Order P10/P11/P12 → `refunding` → RefundCase | MC2 §3.7 |
| `OtcOrder=completed` → 生成 `OtcTrade` → Ledger 分录（`OTC_TRADE`）+ Power 消耗/释放 | MC2 §3.6/§5 |

> 结算会计矩阵（MC2 §5，权威）：`WIN`=CREDIT 本金+盈利；`LOSS`=NO_LEDGER_ENTRY（stake 已 DEBIT）；`PUSH`=CREDIT 本金。任一订单净账本效果仅一次且方向确定。

---

## Part D — 6 缺 enum 实体 Owner Decision Matrix

> 依据 Owner 裁决 #21：`settlement_batches` / `otc_trades` / `refund_cases` / `correction_cases` 的 status **冻结前须补进 05 §4**（走 05 变更流程），否则 **FAIL_CLOSED 不建表**。
> **Owner 已于 2026-08-16 逐项裁决（2B1-ENUM-01..06 全部采纳各 D.x 的 RECOMMENDED_OPTION），已补入 05 §4（V2.3），解除 FAIL_CLOSED，S01-P03 可建表。** 以下 D.1-D.6 保留完整决策矩阵作追溯（RECOMMENDED_OPTION 即已确认项），D.7 为已确认 enum 的状态合同摘要（转移仍候选，待 Independent Review，未 FROZEN）。

### D.1 SettlementBatch（`settlement_batches`）

```text
DECISION_ID = 2B1-ENUM-01
DECISION_REQUIRED = SettlementBatch.status 的 canonical enum
AFFECTED_PACKAGE = S01-P03（2B-1 DDL）
AFFECTED_OBJECTS = settlement_batches
CURRENT_AUTHORITY = Owner（05 §4 未定义）
MISSING_DECISION = canonical enum 值集
OPTION_A = created / processing / completed / partially_failed / failed
OPTION_B = queued / calculating / payable / paid / failed（复用 Settlement enum）
RECOMMENDED_OPTION = OPTION_A（批量容器语义，需表达「部分成功」partially_failed）
RISK_OF_EACH_OPTION = A：新增 partially_failed 语义需定义；B：与 Settlement 混淆，无「部分失败」粒度
SAFE_WORK_CONTINUING = S01-P02 其余对象（Result/Settlement 合同）不受阻塞
RESUME_CONDITION = Owner 裁决 enum 后补 05 §4，再 S01-P03 建 DDL
```

### D.2 RefundCase（`refund_cases`）

```text
DECISION_ID = 2B1-ENUM-02
DECISION_REQUIRED = RefundCase.status 的 canonical enum
AFFECTED_PACKAGE = S01-P03
AFFECTED_OBJECTS = refund_cases
CURRENT_AUTHORITY = Owner（05 §4 未定义）
MISSING_DECISION = canonical enum 值集
OPTION_A = pending / approved / executing / completed / rejected / failed
OPTION_B = draft / pending / changes_requested / approved / rejected / executing / executed / failed（复用 Approval enum）
RECOMMENDED_OPTION = OPTION_A（RefundCase 是执行型工作流，精简六态，含 failed 支持重试）
RISK_OF_EACH_OPTION = A：六态是否覆盖「需修改」场景待确认；B：引入 changes_requested 对退款无意义，冗余
SAFE_WORK_CONTINUING = 不受阻塞；P5/P10/P11/P12 退款 FAIL_CLOSED 直至冻结
RESUME_CONDITION = Owner 裁决 enum 后补 05 §4，再 S01-P03 建 DDL
```

### D.3 CorrectionCase（`correction_cases`）

```text
DECISION_ID = 2B1-ENUM-03
DECISION_REQUIRED = CorrectionCase.status 的 canonical enum
AFFECTED_PACKAGE = S01-P03
AFFECTED_OBJECTS = correction_cases
CURRENT_AUTHORITY = Owner（05 §4 未定义）
MISSING_DECISION = canonical enum 值集
OPTION_A = pending / approved / executing / completed / rejected / failed
OPTION_B = draft / pending / changes_requested / approved / rejected / executing / executed / failed（复用 Approval enum）
RECOMMENDED_OPTION = OPTION_A（与 RefundCase 对称；Correction 有「旧结果→新结果」二元结构）
RISK_OF_EACH_OPTION = A：同 RefundCase；B：冗余
SAFE_WORK_CONTINUING = 不受阻塞；Order P7/P8 纠错 FAIL_CLOSED 直至冻结
RESUME_CONDITION = Owner 裁决 enum 后补 05 §4，再 S01-P03 建 DDL
```

### D.4 OtcTrade（`otc_trades`）

```text
DECISION_ID = 2B1-ENUM-04
DECISION_REQUIRED = OtcTrade.status 的 canonical enum
AFFECTED_PACKAGE = S01-P03
AFFECTED_OBJECTS = otc_trades
CURRENT_AUTHORITY = Owner（05 §4 未定义；05 §3 OtcTrade 有 status 无 enum）
MISSING_DECISION = canonical enum 值集
OPTION_A = pending / completed / cancelled / reversed
OPTION_B = completed（单态事实记录；争议/冲正走 RiskCase + ledger reversal，OtcTrade 本身保持 completed）
RECOMMENDED_OPTION = OPTION_B（OtcTrade 是 append-only 成交事实；MC2 O12 争议判 cancelled/completed 作用于 OtcOrder 而非 Trade；reversal 走账本）
RISK_OF_EACH_OPTION = A：引入 cancelled/reversed 破坏 append-only 事实语义，需额外规则；B：争议期间 Trade 无显式标记，依赖 RiskCase 关联
SAFE_WORK_CONTINUING = 不受阻塞
RESUME_CONDITION = Owner 裁决后补 05 §4，再 S01-P03 建 DDL
```

### D.5 RobotUpgradeOrder（`robot_upgrade_orders`）

```text
DECISION_ID = 2B1-ENUM-05
DECISION_REQUIRED = RobotUpgradeOrder.status 的 canonical enum
AFFECTED_PACKAGE = S01-P03
AFFECTED_OBJECTS = robot_upgrade_orders
CURRENT_AUTHORITY = Owner（05 §4 未定义；05 §3 有 status 无 enum）
MISSING_DECISION = canonical enum 值集
OPTION_A = pending / processing / completed / failed / cancelled
OPTION_B = draft / pending / changes_requested / approved / rejected / executing / executed / failed（复用 Approval enum，因有 approval_id）
RECOMMENDED_OPTION = OPTION_A（升级订单是执行型，非完整审批流；approval_id 仅关联大额人工确认，见 MC2 Owner 裁决 #13）
RISK_OF_EACH_OPTION = A：五态是否需 rejected 场景待确认；B：强制审批流与「默认系统自动执行」冲突
SAFE_WORK_CONTINUING = 不受阻塞
RESUME_CONDITION = Owner 裁决后补 05 §4，再 S01-P03 建 DDL
```

### D.6 ConsentReceipt（`consent_receipts`）

```text
DECISION_ID = 2B1-ENUM-06
DECISION_REQUIRED = ConsentReceipt.status 的 canonical enum
AFFECTED_PACKAGE = S01-P03
AFFECTED_OBJECTS = consent_receipts
CURRENT_AUTHORITY = Owner（05 §4 未定义；05 §3 有 status 无 enum）
MISSING_DECISION = canonical enum 值集
OPTION_A = active / expired / withdrawn / superseded
OPTION_B = active / expired（两态；superseded 由新版本 receipt 表达，withdrawn 由产品撤回流程另行定义）
RECOMMENDED_OPTION = OPTION_B（consent 语义以「当前有效版本」为主；`consent_version` + `content_hash` 已表达版本演进；避免 over-engineering）
RISK_OF_EACH_OPTION = A：withdrawn/superseded 触发规则待定义，增加冻结面；B：无法区分「撤回」与「到期」的历史原因
SAFE_WORK_CONTINUING = 不受阻塞
RESUME_CONDITION = Owner 裁决后补 05 §4，再 S01-P03 建 DDL
```

### D.7 状态合同摘要（enum 已 Owner 裁决，转移候选，未 FROZEN）

> 依据 S01-P02 步骤 3：每项表必须列初态、合法转移、终态、触发者、Authoritative Writer、失败态、重试、幂等、审计、账本副作用。以下为 6 缺 enum 实体的**状态合同摘要**（enum 已 Owner 裁决 = OPTION_A，2026-08-16；转移为候选，不冻结；独立审核通过前置 FROZEN）。触发者仅用 05 §8 已有角色。

#### D.7.1 SettlementBatch

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

#### D.7.2 RefundCase

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

#### D.7.3 CorrectionCase

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

#### D.7.4 OtcTrade

```text
初态 = completed（单态，见 D.4 推荐 OPTION_B）
合法转移 = 无（append-only 成交事实）；争议/冲正走 RiskCase + ledger reversal，不覆盖 Trade
终态 = completed
触发者 = 系统（撮合成交自动生成）；争议裁决 = RISK_APPROVER
Writer = OtcTradeService
失败态 = 无
重试 = 无
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = OTC_TRADE（buyer DEBIT + seller CREDIT）+ Power 消耗，对齐 MC2 O6/O9
```

#### D.7.5 RobotUpgradeOrder

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

#### D.7.6 ConsentReceipt

```text
初态 = active
合法转移（候选）= active→expired（两态，Owner 裁决 OPTION_B：撤回/取代不新增状态值，由新版本 receipt + consent_version 表达）
终态 = expired
触发者 = END_USER 同意（active）；系统（到期 expired）
Writer = ConsentReceiptService
失败态 = 无
重试 = 无
幂等 = idempotency_key（consent_type + consent_version 去重）
审计 = append audit_events
账本副作用 = 无（consent 不产生账本分录）
```

---

## Part E — AuditEvent（`audit_events`）复用声明

- **复用 MC2 已冻结候选 `audit_events` DDL**（`20260815_machine_contract_batch2_audit_events.sql` + MC2 Freeze §6）。
- **不重复创建**、不新增字段、不修改 append-only 约束。
- 2B-1 各实体（Result/Settlement/OtcTrade/RobotUpgradeOrder/ConsentReceipt/RefundCase/CorrectionCase/SettlementBatch）的审计事件全部写入 `audit_events`，通过 `target_object_type` + `target_object_id` 关联。
- 敏感写操作实体（Result/Settlement/OtcTrade/RobotUpgradeOrder）预留 `audit_event_id` 指针列（S01-P03 落实），与 `apt_ledger_entries.audit_event_id` 同机制。

---

## 通用工程约束（S01-P03 建 DDL/骨架时落实）

| 维度 | 约束 |
|---|---|
| 主键 | Snowflake bigint unsigned（`$incrementing=false`，`$keyType='string'`） |
| 并发 | 每表 `object_version int unsigned` CAS 乐观锁（对应 05 If-Match / `OBJECT_VERSION_CONFLICT 409`） |
| 幂等 | 每表 `idempotency_key varchar(64) UNIQUE` 可空 |
| 审计 | 敏感写表 `audit_event_id` 指针 + append `audit_events` |
| 金额 | `decimal(36,18)`（APT 数量）／`decimal(18,8)`（price/系数）／`decimal(18,4)`（Power） |
| 时间 | `created_time/updated_time` int unsigned（Unix 秒） |
| 状态列 | 领域状态用 ENUM（冻结 enum 后），05 未定义前 `varchar(32) NULL` + FAIL_CLOSED |
| 失败安全 | 未冻结状态一律 FAIL_CLOSED，不建表、不写业务 |

## 信息来源

- 05 §3（对象字段）/§4（状态机 + 展示映射）/§8（RBAC）
- MC1 Freeze §3.6/§3.7/§3.9
- MC2 Freeze §3.4/§3.5/§3.6/§3.7/§5（结算会计矩阵）/§6
- `.project-ai/tasks/TASK-20260815-001/design.md` Part C/D（Owner 裁决 #19–#22）
