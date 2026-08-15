# Acceptance: Machine Contract 第二批

> 本文件定义**冻结前**的验收标准。当前状态：**Owner Signoff 完成（2026-08-15）；Independent Review = CHANGES_REQUIRED（IR 679），修复中**。
> 冻结流程：Owner Signoff ✅ → Independent Review（CHANGES_REQUIRED，修复后重提）→ 置 FROZEN。
> 候选交付物：
> - `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`
> - `0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_audit_events.sql`
> - `0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_ledger_object_version.sql`
> - `0.5代码/gainode后端/gainode/sql/CHANGE_REQUEST_CR-20260815-001.md`

## 冻结前必须完成的确认（Owner 决策项）

**状态：全部 22 项 + 2 项财务硬骨头已由 Owner 于 2026-08-15 裁决完毕。** 下方逐条附裁决结果。

### A. Ledger Mutation Contract

- [x] 1. Ledger `dispute` 仲裁规则 → **运营发起，RISK_APPROVER 裁决**（A.1 L4–L7）。
- [x] 2. Ledger `reversal` 触发条件与审批流 → **运营发起，RISK_APPROVER 审批**（A.1 L2/L3）。
- [x] 3. `disputed` 期间余额是否冻结 → **要冻住；方案 A（不改原账数字，`state=disputed` 标记 + `dispute_hold` 投影，按 `origin × entry_direction` 四格精确冻结，禁止统一「排除 disputed」）**。
- [x] 4. `pending` 分录长驻策略 → **允许长驻，不删不清理，stale 报 RiskCase**。

### B. Robot / AI Reward

- [x] 5. Robot 冷却阈值 / review 触发 → **生产参数 TBC，只定义规则**。
- [x] 6. Robot `restricted` 范围 / `inactive→paused` → **restricted 由 allowed_actions 下发；`inactive→paused` 不合法**。
- [x] 7. AI Reward 领取窗口 / 预算退回 / review → **窗口时长 TBC；退回原预算池；review 触发 TBC**。
- [x] 8. AI Reward `held→expired_returned` 直接路径 → **不合法**。

### C. Market / Prediction Order

- [x] 9. Market `void` 原因清单 → **四类（赛事取消/延期超期/数据不可用/监管），reason_code 承载**。
- [x] 10. `exception→settled` 是否人工审批 → **必须运营 + RISK_APPROVER 确认**。
- [x] 11. Result corrected 是否重开结算 → **是，`settled→settlement`，仅一次**。
- [x] 12. `corrected` 是否回 settled → **不回，终态，重新结算走新对象**。

### D. OTC

- [x] 13. `review_required` 触发 / 有效期 → **大额卖出、单人高频异常需人工确认；有效期 TBC**。
- [x] 14. OTC 争议处置目标态 → **RISK_APPROVER 判 `cancelled`（退钱）或 `completed`（维持成交），不回 partial**。

### E. Event Catalog

- [x] 15. 事件码命名 / 全集 → **采用 Part B，覆盖 8 核心实体**。
- [x] 16. `entry_direction` 语义 → **1=CREDIT 入账，-1=DEBIT 出账**。
- [x] 17. `ORDER_SETTLED` 赢/输/走盘 → **赢=本金+盈利入账；输=不追加；走盘=退本金**。
- [x] 18. `audit_events` DDL → **对齐 05 §3 AuditLog（Part E）**。

### F. 非核心实体清单

- [x] 19. 第二批精确范围 → **拆 2B-1（P0）/ 2B-2（P1/P2）两小批**。
- [x] 20. 只读投影/值对象是否落表 → **投影不落表；SettlementMethod 落表**。
- [x] 21. status enum 补充 → **先补 05 §4 再建表，否则 FAIL_CLOSED**。
- [x] 22. `auth_sessions.status` 转移矩阵 → **单独冻结，归 2B-2**。

### 财务硬骨头

- [x] 财务 1（争议冻结会计）→ **方案 A**（不改原账数字，`state=disputed` 标记 + `dispute_hold` 投影，四格 `origin × entry_direction` 精确冻结）。
- [x] 财务 2（投注结算会计）→ **下注先扣钱；赢=本金+盈利入账；输=不追加；走盘=退本金**。

### 角色裁决（05 canonical，IR 629 P1-5 修订）

- [x] **财务裁决/审批 = 05 canonical 分工**：争议裁决/冲正审批/结算异常确认/OTC 争议处置/纠错审批 → **RISK_APPROVER**（批准风险处置）；对账差异发现/发起争议 → **FINANCE_REVIEWER**（读 Ledger/对账，不可写）；发起方 = OPS_OPERATOR 或系统。ADMIN_SECURITY 不涉财。
- ⚠️ 单人项目职责分离（OPS_OPERATOR↔RISK_APPROVER）执行时须遵守 `p1_010_override_contract`。

## IR 629 修复项核查（Independent Review 返回 CHANGES_REQUIRED）

| # | 修复项 | 状态 |
|---|---|---|
| P1-1 | Event Catalog 补全（A.1–A.6 全部 transition → event_code）+ 删除 W6 引用 | ✅ 已修复（design.md Part B） |
| P1-2 | Market void 源状态补 closing/locked + Order 新增 P10/P11/P12 refund 路径 | ✅ 已修复（design.md A.4/A.5） |
| P1-3 | ORDER_SETTLED 结算会计矩阵消歧（WIN/LOSS/PUSH） | ✅ 已修复（design.md B.3） |
| P1-4 | Ledger Mutation Field Contract + Accounting Delta Matrix（方案 A） | ✅ 已修复（design.md A.1.1/A.1.2） |
| P1-5 | 角色改 05 canonical（RISK_APPROVER/FINANCE_REVIEWER，ADMIN_SECURITY 不涉财） | ✅ 已修复（design.md A.0.1/D.0） |
| P1-6 | audit snapshot 改 typed reference（snapshot_type + snapshot_id） | ✅ 已修复（design.md Part E + DDL） |
| P2-1 | 终态三档拆分（TRUE_TERMINAL / STABLE_WITH_EXCEPTION / NON_REVERSIBLE） | ✅ 已修复（design.md A.0 + 各实体） |
| P2-2 | Owner Signoff/Freeze 状态统一 + 落盘表述修正 | ✅ 已修复（本文件 + design.md 头部） |

## IR 638 修复项核查（复审，Independent Review 返回 CHANGES_REQUIRED）

> 说明：以下「✅ 已修复」**不是闭环证据**，验证以实际契约/DDL 为准（IR 638 P2-2 要求）。权威验证源：`design.md` 正文 + `MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md` + 两个 DDL 文件。

| # | 修复项 | 修复落点 |
|---|---|---|
| P1-1 | Accounting Delta Matrix 改 `signed_delta = quantity × entry_direction` 机械公式 + CREDIT/DEBIT 双套示例 + reversal 分录字段（entry_direction 反向、reversal_of 指向原分录） | design.md A.1.2；Freeze §3.1 |
| P1-2 | 方案 A：Ledger 新增 `object_version` 列（dated migration `20260815_..._ledger_object_version.sql`），白名单三列（state/audit_event_id/object_version）+ CAS 乐观锁（`affected_rows≠1`=OBJECT_VERSION_CONFLICT）；不改 MC1 历史 SQL | design.md A.0/A.1.1；Freeze §3.1；新增 DDL |
| P1-3 | FINANCE_REVIEWER 只读：L4/L5/L6/L7 的 state 写入归 Authoritative Writer/系统；FINANCE_REVIEWER 仅提交 RiskCase；审批 ≠ 执行 | design.md A.0.1/A.1；Freeze §2/§3.1 |
| P1-4 | 方案 A：P5 `settling→refunding` 触发改为结算异常（Market=exception）+ RefundCase 审批；不再依赖 Market void；void→refund 仅 P10/P11/P12 | design.md A.5/A.7；Freeze §3.5/§3.7 |
| P2-1 | 删除自由文本「可逆性」列，改 `direct_reverse`（YES/NO + 反向转移 ID），终态分类归「状态分类」bullet | design.md A.0 + A.1–A.6；Freeze §3 各表 |
| P2-2 | 修复项以 design.md + Freeze 文档 + DDL 为权威验证源；本核查表仅作索引 | 本文件 |

## IR 659 修复项核查（三审，Independent Review 返回 CHANGES_REQUIRED：P0=0 / P1=2 / P2=3）

> 说明：以下「✅ 已修复」**不是闭环证据**，验证以实际契约/DDL 为准（IR 638 P2-2 / IR 659 P2-3 要求）。权威验证源：`design.md` 正文 + `MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md` + DDL/CR 文件。

| # | 修复项 | 修复落点 |
|---|---|---|
| P1-1 | 删除统一「排除 disputed 分录影响」；改为四格 Dispute Hold Matrix（`origin × entry_direction`），定义 `stored_balance`/`dispute_hold`/`effective_available` 三概念 + 机械字段（stored_balance_delta/dispute_hold_delta/effective_available_delta/L6_balance_delta/L6_hold_release/L7_balance_delta/L7_hold_release）；posted DEBIT dispute 保持扣款不恢复 available，pending DEBIT dispute 预留冻结 | design.md A.1.2；Freeze §3.1 |
| P1-2 | 统一 pending reversal 语义：`pending→reversed`（L2）与 `pending-origin disputed→reversed`（L7）= `ACCOUNT_DELTA=0`/`ECONOMIC_REVERSAL_ENTRY=NO`/`AUDIT_EVENT=YES`；仅 `posted→reversed`（L3）与 `posted-origin disputed→reversed` 才追加经济 reversal（`entry_direction=-(原)`、`quantity=原`、`reversal_of=原`） | design.md A.1/A.1.2；Freeze §3.1 |
| P2-1 | 删除未冻结的 `DisputeCase` 引用，统一为已冻结的 `RiskCase`（`risk_type=LEDGER_RECONCILIATION_DISPUTE`），UNKNOWN_ENTITY_REFERENCE=0 | design.md A.0.1/A.1；Freeze §2/§3.1 |
| P2-2 | 为 `object_version` 加列补正式 Change Request `CR-20260815-001`（BASE_FREEZE=MC1、CHANGE=ADD apt_ledger_entries.object_version、REASON=IR638 P1-2、MIGRATION=dated SQL、OWNER_DECISION=APPROVED、INDEPENDENT_REVIEW_REQUIRED=YES、NEW_FREEZE_TARGET=MC2） | 新增 `sql/CHANGE_REQUEST_CR-20260815-001.md`；design.md A.1.1；Freeze §3.1/§7/§8 |
| P2-3 | 提供未截断证据：本 Commit 只聚焦 IR 659 修复（少量文件、每文件改动集中），并在下方「冻结时的硬性验收标准」直接内嵌 Dispute Hold/Reversal 验收断言，供审核独立核对 | 本文件 + design.md + Freeze 文档 |

## IR 679 修复项核查（四审，Independent Review 返回 CHANGES_REQUIRED：P0=0 / P1=1 / P2=2）

> 说明：以下「✅ 已修复」**不是闭环证据**，验证以实际契约/DDL 为准。权威验证源：`design.md` 正文 + `MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md` + DDL/CR 文件。**本轮根因修复**：将 AI Code Review Assistant 的 `max_diff_chars` 从 25000 提升至 100000，消除历次「Diff 末尾截断」导致的证据不完整（IR 638 P2-2 / IR 659 P2-3 / IR 679 P2-2 的共同根因）。

| # | 修复项 | 修复落点 |
|---|---|---|
| P1-1 | 新增 `DISPUTE_SHORTFALL_POLICY`：`shortfall = max(0, dispute_hold - stored_available)`；posted CREDIT 已被部分消费后再 dispute/reversal 且 `shortfall > 0` → **FAIL_CLOSED**（L6/L7 拒绝执行，无经济效果，不改余额）。禁止自行实现负余额/部分冲正/自动债务/自动吞差额/后续 CREDIT 自动抵扣。未定义维度（RiskCase 生成/账户 restricted/OTC·Withdrawal·Robot 禁启/ApprovalRequest）deferred 至 2B-2，冻结前不执行（`SHORTFALL_UNDECIDED_EXECUTION = 0`）。拒绝尝试写 `outcome=REJECTED` + `reason_code=SHORTFALL_FAIL_CLOSED` 审计事件 | design.md A.1.2（新增 D 节 + 机械字段 shortfall + 验收断言）；Freeze §3.1/§8 |
| P2-1 | 统一 RiskCase 冻结状态：`RiskCase object schema = DEFINED`（05 §3 定义字段）+ `RiskCase state/type/DDL machine contract = CONTRACT_GAP`（05 §4 未冻结 canonical state）+ `TARGET_BATCH = 2B-2`；`risk_type=LEDGER_RECONCILIATION_DISPUTE` 标 `STATUS = CANDIDATE / PENDING_2B2_FREEZE`（type catalog 冻结前不得执行）；新增 `L4_DEPENDENCY_GATE = RISK_CASE_CONTRACT_FROZEN`、`L5_DEPENDENCY_GATE = RISK_CASE_CONTRACT_FROZEN`（未冻结 → FAIL_CLOSED，`UNFROZEN_RISK_TYPE_EXECUTION = 0`） | design.md A.0.1/A.1；Freeze §2/§3.1/§8 |
| P2-2 | 根因修复 Diff 截断：`max_diff_chars 25000 → 100000`；并新增机械断言 `POSTED_CREDIT_SHORTFALL_POLICY = DETERMINISTIC`、`SHORTFALL_UNDECIDED_EXECUTION = 0` 内嵌至合同正文，供审核独立核对 | settings.json（AI Code Review Assistant）+ design.md A.1.2 + Freeze §3.1 |

## 冻结时的硬性验收标准（Independent Review 通过后触发）

- [ ] 状态转移矩阵（A.1–A.6）经 Owner 逐条确认 + IR 通过，无自创状态（枚举全部来自 05 §4）。
- [ ] Event Catalog 覆盖 A.1–A.6 全部 transition ID（MISSING=0 / ORPHAN=0），事件码与 `entry_type`/`entry_direction` 对齐。
- [ ] Ledger Mutation Field Contract（方案 A：仅 state + audit_event_id + object_version 受控可变）+ Dispute Hold Matrix（四格 `origin × entry_direction`，`signed_delta = quantity × entry_direction`）无二次入账/冲正/扣款。
- [ ] Dispute Hold 验收断言：`POSTED_DEBIT_DISPUTE_AVAILABLE_INCREASE = 0`、`PENDING_DEBIT_DISPUTE_RESERVATION = PASS`。
- [ ] Reversal 验收断言：`PENDING_REVERSAL_ECONOMIC_ENTRY_COUNT = 0`、`POSTED_REVERSAL_DIRECTION = PASS`（pending 取消不生成经济 reversal，仅 posted 冲正生成）。
- [ ] **DISPUTE_SHORTFALL_POLICY**：`shortfall = max(0, dispute_hold - stored_available)`；`shortfall > 0 → FAIL_CLOSED`（`POSTED_CREDIT_SHORTFALL_POLICY = DETERMINISTIC`、`SHORTFALL_UNDECIDED_EXECUTION = 0`）。
- [ ] **RiskCase 冻结状态一致**：`object schema = DEFINED` + `machine contract = CONTRACT_GAP` + `TARGET_BATCH = 2B-2`；L4/L5 dependency gate = RISK_CASE_CONTRACT_FROZEN（未冻结 → FAIL_CLOSED）。
- [ ] `apt_ledger_entries` 已补齐 `object_version`（dated migration，不改 MC1 历史 SQL），并附 Change Request `CR-20260815-001`；CAS 乐观锁 DETERMINISTIC。
- [ ] FINANCE_REVIEWER 只读（对账差异发现/提交 RiskCase `risk_type=LEDGER_RECONCILIATION_DISPUTE`），不直接写 `apt_ledger_entries.state`；无未冻结 `DisputeCase` 引用（UNKNOWN_ENTITY_REFERENCE=0）。
- [ ] settling→refunding（P5）可达（结算异常 + RefundCase 审批），无 unreachable transition。
- [ ] `audit_events` 表 DDL（append-only + typed reference 快照）支持 MC1 §3.6 审计不变量。
- [ ] 非核心实体 DDL 以日期命名文件（`sql/YYYYMMDD_*.sql`）提交，forward-only，无 DROP。
- [ ] 变更 DDL 走 `rules/coding.md` 数据库规则第 6 条（新增日期文件，不改历史）。
- [ ] 冻结后更新 MC1 Freeze 文档的 CONTRACT GAP 状态（由「待冻结」→「已冻结，见第二批」）。
- [ ] 重新触发 Independent Review（State Machine gate）且结论为 APPROVE。

## 明确不做（本 task 边界）

- [ ] 不冻结、不发布（冻结需 IR 通过后另走 AI Code Review Assistant 发布流程）。
- [ ] 不改业务代码、不解除 STAGE-01 骨架的 FAIL_CLOSED。
- [ ] 不涉及 OpenAPI 3.1 与 Environment Freeze（另属 STAGE-02 / 独立任务）。

> 说明：`audit_events` DDL 已作为**冻结候选**落盘日期命名文件 `sql/20260815_machine_contract_batch2_audit_events.sql`（与 MC1 一致：先落 DDL，再 Signoff + IR，最后置 FROZEN）。该文件**未 FROZEN**，冻结前可修改。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`
- `.project-ai/tasks/TASK-20260815-001/design.md`（本 task 草案本体，含 Part D Owner 裁决记录 + Part E audit_events DDL 草案）
