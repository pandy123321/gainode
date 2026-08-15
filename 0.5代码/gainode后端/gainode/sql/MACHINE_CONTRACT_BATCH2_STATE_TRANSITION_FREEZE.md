# Machine Contract 第二批 — State Transition Freeze（候选）

> 状态：**FROZEN CANDIDATE（候选，待 Owner Signoff + Independent Review）**
> 说明：本文件为 Machine Contract 第二批的**冻结候选**。状态转移矩阵 + Event Catalog 已由 Owner 逐项裁决（2026-08-15，22 项 + 2 财务硬骨头，见本文件 §4）。正式 FROZEN 前须通过 Independent Review（State Machine gate）。
> 起草日期：2026-08-15
> 关联 DDL：`0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_audit_events.sql`
> 权威契约：`Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§4 统一状态机）
> 前置冻结：`MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md`（MC1，本文件关闭其 §3.6 CONTRACT GAP）
> 治理依据：`manifest.yaml` decisionSources `p1_003_two_phase_freeze`（两批冻结决策）

## 1. 冻结范围

本批冻结 **8 个核心实体的状态转移矩阵 + Event Catalog + `audit_events` 表 DDL**。冻结后，8 个核心实体的状态流转由本文件授权，非法流转 FAIL_CLOSED。

| 范围 | 内容 | 状态 |
|---|---|---|
| Part A | 6 个状态机 + Ledger 的合法转移路径、触发事件、guard、副作用、可逆性 | 候选 |
| Part B | Event Catalog（业务事件码，对齐 `entry_type`/`entry_direction`） | 候选 |
| Part C | `audit_events` 表 DDL（append-only，对齐 05 §3 AuditLog） | 候选 |

**不包含**（拆出本批，另行交付）：
- 非核心实体 DDL（`results`/`settlements`/`otc_trades` 等 19 表，拆 2B-1 / 2B-2 两小批）。
- OpenAPI 3.1（推迟至 STAGE-02）。
- Environment Freeze（独立任务）。

## 2. 角色映射（Owner 裁决 2026-08-15）

- **财务审核人 = 超级管理员（ADMIN_SECURITY）**。
- 争议裁决、冲正审批、结算异常确认、OTC 争议处置、纠错审批等涉财操作，统一由超级管理员承担；发起方为运营（OPS_OPERATOR）或系统。

> ⚠️ 职责分离提醒：本项目 11 角色由 OWNER 单人兼任（manifest `p1_004_owner_freeze`）。系统层面 `OPS_OPERATOR(发起) ≠ ADMIN_SECURITY(审批)` 的角色分离仍成立；若同一自然人同时持有两角色并自审自批，须满足 `p1_010_override_contract`（非紧急 SELF_APPROVAL=FORBIDDEN；紧急单人需 MFA + 事后 48h 审计）。

## 3. 状态转移矩阵

> 通用不变量（对所有状态机成立）：① 每个 transfer 由该实体唯一 Authoritative Writer（Service）执行；② 附带 `object_version` 乐观锁校验；③ 追加 append-only 审计事件并回写 `audit_event_id`（同事务原子）；④ 终端态不可回退；⑤ 超级管理员不得绕过状态机。
> 表头：`可逆性` = 该转移是否允许退回源状态；`终态` = 进入后不可再变。

### 3.1 Ledger Entry — `pending / posted / reversed / disputed`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| L1 | `pending` → `posted` | 日记账批次原子过账 | 系统 / 运营 | 批次内分录借贷平衡；账户余额校验通过 | 更新 `apt_accounts` 余额；追加审计事件 | 可逆（仅经 L3 冲正） |
| L2 | `pending` → `reversed` | 入账前冲正 | 运营发起 + 超级管理员审批 | 分录尚未 posted | 追加 reversal 分录（`reversal_of` 指向原分录） | 终态 |
| L3 | `posted` → `reversed` | 入账后冲正 | 运营发起 + 超级管理员审批 | 冲正审批通过 | 追加 reversal 分录；反向更新 `apt_accounts` 余额 | 终态 |
| L4 | `pending` → `disputed` | 对账不符/异常标记 | 运营 / 系统 | 对账差异记录 | 冻结标记（见 3.1 注） | 可逆（L6/L7） |
| L5 | `posted` → `disputed` | 入账后发现争议 | 运营 / 系统 | 对账差异记录 | 冻结该笔影响（见 3.1 注） | 可逆（L6/L7） |
| L6 | `disputed` → `posted` | 仲裁确认有效 | 超级管理员裁决 | 裁决通过 | 解除冻结 | 可逆 |
| L7 | `disputed` → `reversed` | 仲裁判定冲正 | 超级管理员裁决 | 裁决冲正 | 追加 reversal 分录 | 终态 |

- **终端态**：`reversed`。`posted` 为稳定态（可经 L3/L5 流转，不可退回 `pending`）。
- **禁止**：`posted → pending`、任何态的物理删除/覆盖（append-only）。
- **争议冻结实现（方案 A，Owner 裁决）**：`state=disputed` 作为冻结标记，**不追加反向分录、不改原账数字**；业务层计算可用余额时排除 `disputed` 分录影响。仲裁后 L6 解除或 L7 正式冲正。
- **pending 长驻策略（Owner 裁决）**：允许长驻，不删除不清理；对账任务标记 stale pending 并生成 RiskCase。

### 3.2 Robot — `inactive / active / cooling / review / restricted / paused`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| R1 | `inactive` → `active` | 用户启动 Robot | END_USER | Power 充足（走 PowerImpactPreview）；资格通过 | 消耗 Power；追加审计事件 | 可逆（R3/R4） |
| R2 | `active` → `cooling` | 连续运行达上限 | 系统 | 冷却阈值 = 生产参数 TBC | 停止产出 | 可逆（R6） |
| R3 | `active` → `inactive` | 用户停止 Robot | END_USER | — | 释放/结算运行中资源 | 可逆（R1） |
| R4 | `active` → `review` | 触发风控/异常 | 系统 / RISK_ANALYST | 风控引擎标记（TBC） | 锁定产出 | 可逆（R5） |
| R5 | `review` → `active` | 风控解除 | RISK_ANALYST / 系统 | 解除条件满足 | 恢复运行 | 可逆 |
| R6 | `cooling` → `active` | 冷却期结束 | 系统 | 冷却时长已满 | 恢复运行 | 可逆（R2） |
| R7 | `active` → `restricted` | 策略受限 | 系统 / OPS_OPERATOR | 受限范围由 allowed_actions 下发 | 部分功能禁用 | 可逆（R8） |
| R8 | `restricted` → `active` | 受限解除 | OPS_OPERATOR | 解除条件满足 | 恢复完整功能 | 可逆 |
| R9 | `active` → `paused` | 管理员手动暂停 | OPS_OPERATOR | 授权 | 暂停产出 | 可逆（R10） |
| R10 | `paused` → `active` | 管理员恢复 | OPS_OPERATOR | 授权 | 恢复运行 | 可逆 |
| R11 | `review` → `inactive` | 风控确认违规停用 | RISK_ANALYST | 风控处置结论 | 停用 | 可逆（R1 重启） |
| R12 | `cooling`/`review`/`restricted` → `paused` | 管理员强制暂停 | OPS_OPERATOR | 授权 | 暂停 | 可逆 |

- **无严格终态**；`review`/`cooling`/`restricted` 为中间锁定态。
- **`inactive→paused` 不合法**（Owner 裁决）：paused 仅作用于运行态。

### 3.3 AI Reward — `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| W1 | `candidate` → `held` | 奖励记账确认 | 系统 | 预算内；资格快照通过 | 生成 ledger entry（CREDIT）；回填 `ledger_entry_id` | 可逆（仅经 W9） |
| W2 | `held` → `pending_claim` | 进入领取窗口 | 系统 | 窗口时长 = 生产参数 TBC | 设置 `expires_at` | 可逆（W5） |
| W3 | `pending_claim` → `claiming` | 用户发起领取 | END_USER | 幂等防重 | 冻结领取 | 可逆（W4/W5） |
| W4 | `claiming` → `claimed` | 领取完成 | 系统 | 账户状态正常 | 更新 `apt_accounts`；回填 `claim_id` | 可逆（仅经 W10） |
| W5 | `pending_claim` → `expired_returned` | 领取窗口过期 | 系统 | 超过 `expires_at` | 退回预算池；追加 ledger entry（DEBIT） | 终态 |
| W7 | `candidate` → `review` | 风控冻结 | 系统 / 风控引擎 | 风控标记（TBC） | 冻结 | 可逆（W8/W9） |
| W8 | `review` → `held` | 风控解除 | 超级管理员 / 系统 | 解除条件满足 | 恢复可领 | 可逆 |
| W9 | `held`/`review` → `reversed` | 财务冲正 | 运营发起 + 超级管理员审批 | 冲正审批通过 | 追加 reversal ledger entry | 终态 |
| W10 | `claimed` → `reversed` | 领取后冲正 | 运营发起 + 超级管理员审批 | 冲正审批通过 | 追加 reversal；扣回账户余额 | 终态 |

- **终端态**：`expired_returned`、`reversed`、`claimed`（claimed 仅可经 W10 冲正）。
- **`held→expired_returned` 直接路径不合法**（Owner 裁决）：held 必须先经 W2 进入领取窗口。

### 3.4 Market — `draft / open / closing / locked / awaiting_result / settlement / settled / void / exception`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| M1 | `draft` → `open` | 发布市场 | OPS_OPERATOR | 参数/策略版本已冻结 | 开放投注 | 可逆（仅 M8） |
| M2 | `open` → `closing` | 临近锁定 | 系统 | 达到 `lock_at - t` | 促使用户行动 | 可逆 |
| M3 | `closing` → `locked` | 到达锁定时间 | 系统 | 时间到 | 禁止新投注 | 终态（可 M5/M8） |
| M4 | `open` → `locked` | 直接锁定 | OPS_OPERATOR / 系统 | 允许跳过 closing（运营兜底） | 禁止新投注 | 终态（可 M5/M8） |
| M5 | `locked` → `awaiting_result` | 赛事开始/等待结果 | 系统 | 已锁定 | 等待 Result | 可逆 |
| M6 | `awaiting_result` → `settlement` | 结果确认 | 系统 | Result = `official` | 开始结算 | 可逆（M9） |
| M7 | `settlement` → `settled` | 结算完成 | 系统 | Settlement = `paid` | 订单批量 settled | 终态（可 M12） |
| M8 | `awaiting_result`/`open`/`draft` → `void` | 赛事取消/作废 | OPS_OPERATOR / 系统 | 四类原因（赛事取消/延期超期/数据不可用/监管） | 触发订单退款 | 终态 |
| M9 | `settlement` → `exception` | 结算异常 | 系统 | 结算失败 | 冻结结算 | 可逆（M10/M11） |
| M10 | `exception` → `settlement` | 异常处理后重试 | OPS_OPERATOR / 系统 | 恢复条件满足 | 重试结算 | 可逆 |
| M11 | `exception` → `settled` | 异常处理直接完成 | 运营 + 超级管理员确认 | 双人确认通过（涉及资金） | 标记完成 | 终态 |
| M12 | `settled` → `settlement` | Result corrected 重开结算 | 系统 | 仅一次 | 重开结算；关联 Order 走 correcting | 可逆（M7/M9） |

- **终端态**：`settled`、`void`。
- **`settlement≠settled`**（05 §4）；`exception→settled` 双人确认（Owner 裁决 #10）；`void` 四类原因（Owner 裁决 #9）；Result corrected 重开仅一次（Owner 裁决 #11）。

### 3.5 Prediction Order — `submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| P1 | `submitted` → `locked` | 市场锁定联动 | 系统 | Market 进入 `locked` | 锁定订单 | 终态（可 P3+） |
| P2 | `locked` → `awaiting_result` | 等待结果 | 系统 | 赛事开始 | 等待 | 可逆 |
| P3 | `awaiting_result` → `settling` | 开始结算 | 系统 | Result official + Market settlement | 进入结算 | 可逆 |
| P4 | `settling` → `settled` | 结算完成 | 系统 | 结算计算完成 | 更新账户/ledger | 终态（可 P7） |
| P5 | `settling` → `refunding` | 结算异常需退款 | 系统 | 仅 `Market void` 触发 | 冻结待退 | 可逆（P6） |
| P6 | `refunding` → `refunded` | 退款完成 | 系统 | 退款入账 | 更新账户 | 终态 |
| P7 | `settled` → `correcting` | 结算错误纠错 | 运营发起 + 超级管理员审批 | 仅 settlement error；审批通过 | 冻结 | 可逆（P8） |
| P8 | `correcting` → `corrected` | 纠错完成 | 超级管理员审批 | 纠错审批通过 | 生成 reversal + new ledger | 终态 |
| P9 | `settling` → `correcting` | 结算中发现错误 | 运营发起 | 发现错误 | 冻结纠错 | 可逆（P8） |

- **终端态**：`settled`、`refunded`、`corrected`。
- **`corrected` 不回 `settled`**（Owner 裁决 #12）：重新结算产生新 Order/新分录（CorrectionCase 追踪）。
- **`RESULT_UNKNOWN` 不混入订单状态**（05 §4）。

### 3.6 OTC Order — `draft / review / matching / partial / completed / cancelled / expired / rejected / disputed`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| O1 | `draft` → `review` | 提交审核 | END_USER | `review_required=1` | 进入审核队列 | 可逆（O3/O4） |
| O2 | `draft` → `matching` | 提交撮合 | END_USER | `review_required=0`；资格通过 | 进入撮合 | 可逆（O6/O7） |
| O3 | `review` → `matching` | 审核通过 | KYC_REVIEWER / OPS_OPERATOR | 审核通过 | 进入撮合 | 可逆 |
| O4 | `review` → `rejected` | 审核驳回 | KYC_REVIEWER / OPS_OPERATOR | 审核驳回 | 保留历史 | 终态 |
| O5 | `matching` → `partial` | 部分成交 | 系统 | 成交部分 | 更新 filled/remaining | 可逆（继续 O5/O6/O7） |
| O6 | `matching` → `completed` | 全部成交 | 系统 | 全部成交 | 生成 Trade + Ledger | 终态（可 O11） |
| O7 | `matching` → `cancelled` | 用户取消 | END_USER | 未成交部分可取消 | 释放 remaining | 终态 |
| O8 | `matching` → `expired` | 有效期到期 | 系统 | 超过有效期 | 释放 remaining | 终态 |
| O9 | `partial` → `completed` | 剩余全部成交 | 系统 | 剩余成交 | 生成 Trade + Ledger | 终态（可 O11） |
| O10 | `partial` → `cancelled`/`expired` | 取消剩余 / 到期 | END_USER / 系统 | 仅释放 remaining | 释放 remaining | 终态 |
| O11 | `completed` → `disputed` | 成交后争议 | END_USER / 系统 | 争议触发 | 冻结 | 可逆（O12） |
| O12 | `disputed` → `cancelled`/`completed` | 争议处置 | 超级管理员裁决 | 裁决二选一 | 取消退钱 或 维持成交；反向冲正走 ledger reversal | 终态 |

- **终端态**：`completed`、`cancelled`、`expired`、`rejected`（`completed` 可经 O11）。
- **`cancelled`≠`expired`**（05 §4）；`partial+cancelled/expired` 只释放 remaining；`disputed` 冻结直到处置。
- **review_required 触发（Owner 裁决 #13）**：大额卖出、单人高频异常需人工确认；有效期 = 生产参数 TBC。
- **争议处置（Owner 裁决 #14）**：超级管理员判 `cancelled`（退钱）或 `completed`（维持成交），不回 `partial`。

### 3.7 跨实体协同（Owner 裁决确认）

| 联动 | 依据 |
|---|---|
| Market `void` → Prediction Order `refunding → refunded` | 05 §4「已作废/已取消」 |
| Result `corrected` → Market `settled → settlement` 重开 → Order `correcting → corrected` | 05 §3 Result/CorrectionCase；仅一次 |
| AI Reward `held` → Ledger `pending → posted` | 05 §3 AIReward.ledger_entry_id |
| OTC `completed` → Ledger 分录 → Power 释放 | 05 §3 OtcTrade |

## 4. Owner 裁决记录（2026-08-15）

22 项待确认 + 2 项财务硬骨头已由 Owner 逐项裁决。完整记录见 `.project-ai/tasks/TASK-20260815-001/design.md` Part D。关键裁决：

| # | 裁决 |
|---|---|
| 角色 | 财务审核人 = 超级管理员（ADMIN_SECURITY）；发起方 = 运营/系统 |
| 1 | 争议由运营发起，超级管理员裁决 |
| 2 | 冲正由运营发起，超级管理员审批 |
| 3 | 争议期间钱冻住（方案 A：不改原账数字，标记 + 业务层排除） |
| 10 | `exception→settled` 运营 + 超级管理员确认 |
| 13 | 大额卖出、单人高频异常需人工确认 |
| 14 | OTC 争议判 cancelled/completed 二选一，不回 partial |
| 财务 1 | 争议冻结 = 方案 A |
| 财务 2 | 投注结算 = 下注先扣；赢=本金+盈利入账；输=不追加；走盘=退本金 |

## 5. Event Catalog（对齐 `entry_type`/`entry_direction`）

> `entry_direction`：`1 = CREDIT(入账)`，`-1 = DEBIT(出账)`（Owner 裁决 #16）。事件码命名 `ENTITY_ACTION`（Owner 裁决 #15），覆盖 8 核心实体。

| event_code | 来源实体 | 触发转移 | ledger entry_type | entry_direction | Power 影响 | 审计 |
|---|---|---|---|---|---|---|
| ROBOT_STARTED | robots | R1 | — | — | consume | 是 |
| ROBOT_STOPPED | robots | R3 | — | — | release | 是 |
| ROBOT_COOLING_ENTERED | robots | R2 | — | — | — | 是 |
| ROBOT_REVIEW_LOCKED | robots | R4 | — | — | — | 是 |
| ROBOT_PAUSED | robots | R9 | — | — | — | 是 |
| REWARD_HELD | robot_rewards | W1 | REWARD_ACCRUAL | 1 (CREDIT) | — | 是 |
| REWARD_CLAIMED | robot_rewards | W4 | REWARD_CLAIM | -1 (DEBIT) | — | 是 |
| REWARD_EXPIRED_RETURNED | robot_rewards | W5 | REWARD_EXPIRY_RETURN | -1 (DEBIT) | — | 是 |
| REWARD_REVERSED | robot_rewards | W9/W10 | REWARD_REVERSAL | -1 (DEBIT) | — | 是 |
| LEDGER_ENTRY_POSTED | apt_ledger_entries | L1 | — | — | — | 是 |
| LEDGER_ENTRY_REVERSED | apt_ledger_entries | L2/L3 | LEDGER_REVERSAL | 反向 | — | 是 |
| LEDGER_ENTRY_DISPUTED | apt_ledger_entries | L4/L5 | — | — | — | 是 |
| MARKET_PUBLISHED | prediction_markets | M1 | — | — | — | 是 |
| MARKET_LOCKED | prediction_markets | M3/M4 | — | — | — | 是 |
| MARKET_VOIDED | prediction_markets | M8 | — | — | — | 是 |
| MARKET_SETTLED | prediction_markets | M7 | — | — | — | 是 |
| ORDER_SUBMITTED | prediction_orders | 创建 submitted | ORDER_STAKE | -1 (DEBIT) | — | 是 |
| ORDER_SETTLED | prediction_orders | P4 | ORDER_SETTLEMENT | 见注 | — | 是 |
| ORDER_REFUNDED | prediction_orders | P6 | ORDER_REFUND | 1 (CREDIT) | — | 是 |
| ORDER_CORRECTED | prediction_orders | P8 | ORDER_CORRECTION | 双向 | — | 是 |
| OTC_ORDER_CREATED | otc_orders | 创建 draft | — | — | freeze | 是 |
| OTC_ORDER_COMPLETED | otc_orders | O6/O9 | OTC_TRADE | 双向 | consume | 是 |
| OTC_ORDER_CANCELLED | otc_orders | O7/O10 | — | — | release | 是 |
| OTC_ORDER_EXPIRED | otc_orders | O8/O10 | — | — | release | 是 |
| OTC_ORDER_DISPUTED | otc_orders | O11 | — | — | — | 是 |

- **注（ORDER_SETTLED，Owner 裁决 #17 / 财务 2）**：赢 = 本金+盈利 CREDIT；输 = 不追加（stake 已由 ORDER_STAKE DEBIT 扣减）；走盘 = 本金退回 CREDIT。

## 6. audit_events 表

见 `20260815_machine_contract_batch2_audit_events.sql`。关键约束：
- append-only（无 `updated_time` 列；`$timestamps=false` + `UPDATED_AT=null`；Model/DAO/Builder 三级 fail-closed，复用 MC1 §3.6 已验证机制）。
- **独立表**，不与 `sys_operation_logs` 合并（Owner 裁决）。
- `before/after_snapshot_id` 引用 `parameter_snapshots`（Owner 裁决）。
- 关联回写：`apt_ledger_entries.audit_event_id` 是「最新审计事件指针」；完整时间线由 `audit_events` 重建。

## 7. 变更控制

本批冻结后修改任何状态转移路径、事件码或 `audit_events` 字段语义，必须：
1. 走 05 契约变更流程（先改 05 §3/§4，再改 DDL/矩阵）；
2. 更新本 Freeze 文档版本号；
3. 变更 DDL 以新增日期文件提交（不改历史 dated SQL）；
4. 重新触发 Independent Review（State Machine gate）。

## 8. 验收对照

- [ ] 状态转移矩阵（3.1–3.6）经 Owner 逐条裁决，无自创状态（枚举全部来自 05 §4）
- [ ] Event Catalog 事件码与 `entry_type`/`entry_direction` 对齐
- [ ] `audit_events` 表 DDL 定义（append-only，支持 MC1 §3.6 审计不变量）
- [ ] 关闭 MC1 Freeze 文档 §3.6 CONTRACT GAP（「待冻结」→「已冻结，见第二批」）
- [ ] 重新触发 Independent Review（State Machine gate）
