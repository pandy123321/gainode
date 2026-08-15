# Requirement: Machine Contract 第二批 2B-1（状态合同补齐）

## 状态

- **Owner Signoff：未完成（本 task 产出 Owner Decision Matrix，待 Owner 裁决）**
- **Independent Review：未开始**
- **冻结状态：CANDIDATE（未 FROZEN）**

## 背景

MC2（`TASK-20260815-001`）已冻结 8 个核心实体的状态转移矩阵 + Event Catalog + `audit_events` DDL，并将非核心实体拆为 **2B-1（P0）** 与 **2B-2（P1/P2）** 两小批（Owner 裁决 #19）。

本 task（S01-P02）执行 **2B-1 状态合同补齐**，为 S01-P03（2B-1 DDL 与 Model/DAO/Service 骨架）提供冻结前的状态合同依据。

## 范围

固定对象（9 个）：

```text
Result
Settlement
SettlementBatch
RefundCase
CorrectionCase
OtcTrade
RobotUpgradeOrder
ConsentReceipt
AuditEvent
```

## 规则（约束）

1. **Result / Settlement 复制 05 已有 canonical state**（enum 已冻结，不新增状态值），并补齐其状态转移矩阵。
2. **AuditEvent 复用现有 MC2 `audit_events` DDL**，不重复创建。
3. **缺失 canonical enum 的实体只生成 Owner Decision Matrix**，不自创状态。
4. 每个状态必须定义：初态、合法转移、终态、触发者、Writer、幂等、并发、审计、账本效果。
5. **未批准前保持 FAIL_CLOSED**。
6. 触发者/Writer 只能使用 05 §8 已冻结角色，不自创角色。

## 对象 canonical state 现状

| 对象 | 05 §3 字段 | 05 §4 canonical enum | 处理方式 |
|---|---|---|---|
| Result | 有 `status` | `provisional / official / disputed / corrected` | 复制 enum + 补齐转移矩阵 |
| Settlement | 有 `status` | `queued / calculating / review / payable / paid / failed` | 复制 enum + 补齐转移矩阵 |
| SettlementBatch | 有 `status` | **缺失** | Owner Decision Matrix |
| RefundCase | 有 `status` | **缺失** | Owner Decision Matrix |
| CorrectionCase | 有 `status` | **缺失** | Owner Decision Matrix |
| OtcTrade | 有 `status` | **缺失** | Owner Decision Matrix |
| RobotUpgradeOrder | 有 `status` | **缺失** | Owner Decision Matrix |
| ConsentReceipt | 有 `status` | **缺失** | Owner Decision Matrix |
| AuditEvent | 无状态机 | append-only | 复用 MC2 DDL |

## 非目标（NON_GOALS）

- 不生成 2B-1 任何 DDL（属 S01-P03）。
- 不写任何 PHP Model/DAO/Service（属 S01-P03）。
- 不自创 canonical state、不自创角色、不自创 API。
- 不修改 05 契约（6 缺 enum 实体的 enum 补充需 Owner 裁决后走 05 变更流程，见 acceptance）。
- 不涉及 2B-2 对象（ApprovalRequest/ParameterRelease/Notice/… 属 S01-P04）。

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3/§4/§8）
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH2_STATE_TRANSITION_FREEZE.md`
- `.project-ai/tasks/TASK-20260815-001/design.md`（Part C 非核心实体清单 + Part D Owner 裁决）
- `.project-ai/rules/coding.md`（数据库规则）
