# Acceptance: Machine Contract 第二批

> 本文件定义**冻结前**的验收标准。当前状态：**冻结候选已产出（2026-08-15），待 Owner Signoff**。
> 冻结流程剩余：Owner Signoff → Independent Review（State Machine gate）→ 置 FROZEN。
> 候选交付物：
> - `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`
> - `0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_audit_events.sql`

## 冻结前必须完成的确认（Owner 决策项）

**状态：全部 22 项 + 2 项财务硬骨头已由 Owner 于 2026-08-15 裁决完毕。** 下方逐条附裁决结果。

### A. Ledger Mutation Contract

- [x] 1. Ledger `dispute` 仲裁规则 → **运营发起，超级管理员裁决**（A.1 L4–L7）。
- [x] 2. Ledger `reversal` 触发条件与审批流 → **运营发起，超级管理员审批**（A.1 L2/L3）。
- [x] 3. `disputed` 期间余额是否冻结 → **要冻住；方案 A（不改原账数字，`state=disputed` 标记 + 业务层排除冻结）**。
- [x] 4. `pending` 分录长驻策略 → **允许长驻，不删不清理，stale 报 RiskCase**。

### B. Robot / AI Reward

- [x] 5. Robot 冷却阈值 / review 触发 → **生产参数 TBC，只定义规则**。
- [x] 6. Robot `restricted` 范围 / `inactive→paused` → **restricted 由 allowed_actions 下发；`inactive→paused` 不合法**。
- [x] 7. AI Reward 领取窗口 / 预算退回 / review → **窗口时长 TBC；退回原预算池；review 触发 TBC**。
- [x] 8. AI Reward `held→expired_returned` 直接路径 → **不合法**。

### C. Market / Prediction Order

- [x] 9. Market `void` 原因清单 → **四类（赛事取消/延期超期/数据不可用/监管），reason_code 承载**。
- [x] 10. `exception→settled` 是否人工审批 → **必须运营 + 超级管理员确认**。
- [x] 11. Result corrected 是否重开结算 → **是，`settled→settlement`，仅一次**。
- [x] 12. `corrected` 是否回 settled → **不回，终态，重新结算走新对象**。

### D. OTC

- [x] 13. `review_required` 触发 / 有效期 → **大额卖出、单人高频异常需人工确认；有效期 TBC**。
- [x] 14. OTC 争议处置目标态 → **超级管理员判 `cancelled`（退钱）或 `completed`（维持成交），不回 partial**。

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

- [x] 财务 1（争议冻结会计）→ **方案 A**（不改原账数字，`state=disputed` 标记 + 业务层排除）。
- [x] 财务 2（投注结算会计）→ **下注先扣钱；赢=本金+盈利入账；输=不追加；走盘=退本金**。

### 角色裁决

- [x] **财务审核人 = 超级管理员（ADMIN_SECURITY）**；争议/冲正/结算异常/OTC 争议/纠错等涉财审批统一由超级管理员承担，发起方为运营或系统。
- ⚠️ 单人项目职责分离（OPS_OPERATOR↔ADMIN_SECURITY）执行时须遵守 `p1_010_override_contract`。

## 冻结时的硬性验收标准（Owner Signoff 后触发）

- [ ] 状态转移矩阵（A.1–A.6）经 Owner 逐条确认，无自创状态（枚举全部来自 05 §4）。
- [ ] Event Catalog 事件码与 `entry_type`/`entry_direction` 对齐，DDL 中 `entry_type` 由 `varchar(64)` 改为可冻结的枚举或引用。
- [ ] `audit_events` 表 DDL 定义（append-only，支持 MC1 §3.6 审计不变量）。
- [ ] 非核心实体 DDL 以日期命名文件（`sql/YYYYMMDD_*.sql`）提交，forward-only，无 DROP。
- [ ] 变更 DDL 走 `rules/coding.md` 数据库规则第 6 条（新增日期文件，不改历史）。
- [ ] 冻结后更新 MC1 Freeze 文档的 CONTRACT GAP 状态（由「待冻结」→「已冻结，见第二批」）。
- [ ] 重新触发 Independent Review（State Machine gate）。

## 明确不做（本 task 边界）

- [ ] 不落正式 DDL 到 `sql/`（仅草案）。
- [ ] 不改业务代码、不解除 STAGE-01 骨架的 FAIL_CLOSED。
- [ ] 不冻结、不发布（发布需 Owner 确认后另走 AI Code Review Assistant 流程）。
- [ ] 不涉及 OpenAPI 3.1 与 Environment Freeze（另属 STAGE-02 / 独立任务）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`
- `.project-ai/tasks/TASK-20260815-001/design.md`（本 task 草案本体，含 Part D Owner 裁决记录 + Part E audit_events DDL 草案）
