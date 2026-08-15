# Requirement: Machine Contract 第二批草案 — Ledger Mutation Contract + Event Catalog + 非核心实体清单

## 状态

> **Owner Signoff 完成（2026-08-15）；Independent Review = CHANGES_REQUIRED（IR 682），修复中**。本 task 已由 Owner 逐项裁决（22 项 + 2 财务硬骨头 + 6 个尾巴默认答案，见 `design.md` Part D）；IR 629 返回 6 P1 + 2 P2，已修复并重提；IR 638（复审）返回 4 P1 + 2 P2，已按 Owner 二次裁决修复（P1-2 方案 A：Ledger 新增 object_version；P1-4 方案 A：settling 退款改走结算异常 + RefundCase）；IR 659（三审）返回 2 P1 + 3 P2（dispute hold 四格冻结、pending reversal 语义、DisputeCase→RiskCase、object_version 补 CR、证据完整性），已修复；IR 679（四审）返回 1 P1 + 2 P2（posted CREDIT shortfall 边界、RiskCase 冻结状态矛盾、证据截断），已修复；IR 682（五审）返回 2 P1 + 1 P2（shortfall 检查时机、账户级并发、证据完整性），修复中。冻结候选已落盘：
> - 候选文档：`0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`
> - DDL：`0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_audit_events.sql`
> - DDL：`0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_ledger_object_version.sql`
> - Change Request：`0.5代码/gainode后端/gainode/sql/CHANGE_REQUEST_CR-20260815-001.md`
> 冻结流程：Owner Signoff ✅ → Independent Review（CHANGES_REQUIRED，修复后重提）→ 置 FROZEN。
> 在正式 FROZEN 前，所有依赖这些契约的状态流转保持 **FAIL_CLOSED**。

## 背景

Machine Contract 冻结分两批（manifest `p1_003_two_phase_freeze`，OWNER_DIRECTIVE 2026-08-12「同意分两批」）：

| 批次 | 内容 | 状态 |
|---|---|---|
| 第一批（STAGE-01 前） | 8 核心实体 DDL + Canonical State Freeze | ✅ FROZEN（2026-08-13） |
| 第二批（STAGE-01~02 并行） | OpenAPI 3.1 + Event Catalog + Environment Freeze | ⏳ 本 task 起草 |

MC1 冻结文档（`MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`）明确标注了以下 **CONTRACT GAP**：

- 05 §4 仅定义 Ledger canonical enum，**未定义精确状态转移矩阵**（各 transition 触发条件、dispute 仲裁、reversal 触发条件均 TBC）。
- `apt_ledger_entries.entry_type` / `entry_direction` 取值「与 Event Catalog 对齐后冻结」。
- 审计事件表 schema「待 Event Catalog / Ledger Mutation Contract 阶段正式冻结」。

STAGE-01 已落地的 8 个 Model/DAO/Service 骨架因此全部处于 FAIL_CLOSED（状态流转被拒绝）。本 task 起草的第二批契约，正是解除这些 FAIL_CLOSED 的前提。

## 核心任务

产出三份草案（合并在 `design.md`）：

1. **Ledger Mutation Contract**（状态转移矩阵草案）— 6 个状态机 + Ledger 的合法转移路径、触发事件、guard、副作用、可逆性。
2. **Event Catalog**（事件目录草案）— 业务事件码（`entry_type` 对齐）、事件与转移/账本/Power/审计的关系。
3. **非核心实体清单 + DDL 草案** — 05 §3 对象全集中，除 MC1 第一批 8 实体外，哪些纳入第二批 DDL + state freeze，哪些延后。

> 说明：第二批清单中的 **OpenAPI 3.1** 与 **Environment Freeze** 不在本 task 范围内 —— 依 `context.md`「API Freeze 推迟至 STAGE-02」，STAGE-01 不定义 API；Environment Freeze 另行处理。

## 强制要求

1. **不冻结、不越权**：本 task 产出冻结候选（`audit_events` DDL 已落盘日期命名候选文件，未 FROZEN），不写 FROZEN，不改业务代码、不解除 FAIL_CLOSED。
2. **来源可追溯**：每个转移/事件码/实体都标注 05 §4 出处或明确标注【待确认】。
3. **诚实边界**：05 未定义的内容一律标【待确认】，不自行发明并当作既成事实。
4. **不解除 FAIL_CLOSED**：在 Owner Signoff + Independent Review 通过前，任何状态流转实现仍 MUST FAIL_CLOSED。
5. 每份文档包含「已确认信息 / 基于代码的推断 / 待确认事项 / 信息来源」四节。

## 交付物

| 文件 | 内容 |
|---|---|
| `requirement.md` | 本文件（背景 + 范围 + 强制要求） |
| `design.md` | Part A 状态转移矩阵 + Part B Event Catalog + Part C 非核心实体清单 |
| `acceptance.md` | 冻结前的验收标准（Owner Signoff + Independent Review gate） |
