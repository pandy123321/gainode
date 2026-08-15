# Machine Contract 第二批 — State Transition Freeze（候选）

> 状态：**FROZEN CANDIDATE（候选，Owner Signoff ✅；Independent Review = CHANGES_REQUIRED，IR 638，修复中）**
> 说明：本文件为 Machine Contract 第二批的**冻结候选**。状态转移矩阵 + Event Catalog 已由 Owner 逐项裁决（2026-08-15，22 项 + 2 财务硬骨头，见本文件 §4）。IR 629 返回 6 P1 + 2 P2，已修复并重提；IR 638（针对修复后 commit）返回 4 P1 + 2 P2，已按 Owner 二次裁决修复（P1-2 方案 A：Ledger 新增 object_version；P1-4 方案 A：settling 退款改走结算异常 + RefundCase）。正式 FROZEN 前须重提 Independent Review（State Machine gate）并通过。
> 起草日期：2026-08-15
> 关联 DDL：`0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_audit_events.sql`；`0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_ledger_object_version.sql`
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

## 2. 角色映射（05 canonical，Owner 裁决 2026-08-15 修订）

- **财务裁决/审批 = 05 canonical 角色分工**：争议裁决、冲正审批、结算异常确认、OTC 争议处置、纠错审批 → **RISK_APPROVER**（批准风险处置）；对账差异发现、提交 DisputeCase/RiskCase → **FINANCE_REVIEWER**（读 Ledger/对账，不可写，**不直接改 `apt_ledger_entries.state`**）；发起方 = OPS_OPERATOR（运营）或系统。
- **ADMIN_SECURITY 不承担财务裁决**（05 定义其仅管角色/权限/安全配置，不可接触资产）。
- **角色与状态写入分离（IR 638 P1-3）**：FINANCE_REVIEWER 只读，不是任何 `apt_ledger_entries` 状态转移的直接执行者。L4/L5 的 `state` 写入由 Authoritative Writer（Ledger Service）/系统在合法工作流条件满足后执行；审批角色（RISK_APPROVER）批准 ≠ 执行（`approval actor != execution authority`）。

> ⚠️ 职责分离提醒：本项目 11 角色由 OWNER 单人兼任（manifest `p1_004_owner_freeze`）。系统层面 `OPS_OPERATOR(发起) ≠ RISK_APPROVER(审批)` 的角色分离仍成立；若同一自然人同时持有两角色并自审自批，须满足 `p1_010_override_contract`（非紧急 SELF_APPROVAL=FORBIDDEN；紧急单人需 MFA + 事后 48h 审计）。

## 3. 状态转移矩阵

> 通用不变量（对所有状态机成立）：① 每个 transfer 由该实体唯一 Authoritative Writer（Service）执行；② 附带 `object_version` 乐观锁校验（8 核心实体均含 object_version，`apt_ledger_entries` 经 dated migration `20260815_..._ledger_object_version.sql` 补齐，IR 638 P1-2 方案 A）；③ 追加 append-only 审计事件并回写 `audit_event_id`（同事务原子）；④ 状态不可任意流转（仅本文件定义的合法出边）；⑤ 超级管理员不得绕过状态机。
> 终态三档（IR 629 P2-1）：**TRUE_TERMINAL**（真终态，无出边）/ **STABLE_WITH_EXCEPTION_TRANSITIONS**（稳定态，仅冲正/纠错/争议例外可离开）/ **NON_REVERSIBLE_TO_PREVIOUS_STATE**（单条转移方向不可逆）。
> `direct_reverse` 列（IR 638 P2-1）：是否存在从目标态**直接回到源态**的合法转移，取值仅 `YES`（列反向转移 ID）/`NO`。终态/稳定态分类由各表「状态分类」bullet 承载，不用自由文本「可逆/不可逆」。

### 3.1 Ledger Entry — `pending / posted / reversed / disputed`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| L1 | `pending` → `posted` | 日记账批次原子过账 | 系统 / OPS_OPERATOR | 批次内分录借贷平衡；账户余额校验通过 | 更新 `apt_accounts` 余额；追加审计事件 | NO（`posted↛pending`） |
| L2 | `pending` → `reversed` | 入账前冲正 | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 分录尚未 posted | 追加 reversal 分录 | NO |
| L3 | `posted` → `reversed` | 入账后冲正 | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 冲正审批通过 | 追加 reversal 分录；反向更新 `apt_accounts` | NO |
| L4 | `pending` → `disputed` | 对账不符/异常标记 | Authoritative Writer / 系统（OPS_OPERATOR 发起；FINANCE_REVIEWER 仅提交差异 Case） | 对账差异记录 | 冻结标记（见 Accounting Delta Matrix） | NO（`disputed↛pending`） |
| L5 | `posted` → `disputed` | 入账后发现争议 | Authoritative Writer / 系统（OPS_OPERATOR 发起；FINANCE_REVIEWER 仅提交差异 Case） | 对账差异记录 | 冻结该笔影响 | YES（L6） |
| L6 | `disputed` → `posted` | 仲裁确认有效 | Authoritative Writer（RISK_APPROVER 裁决后执行） | 裁决通过 | 按 origin 入账或保持 | YES（L5） |
| L7 | `disputed` → `reversed` | 仲裁判定冲正 | Authoritative Writer（RISK_APPROVER 裁决后执行） | 裁决冲正 | 追加 reversal 分录 | NO |

- **状态分类（IR 629 P2-1）**：`reversed` = TRUE_TERMINAL；`posted` = STABLE_WITH_EXCEPTION_TRANSITIONS（可经 L3/L5，不可退回 pending）；`pending`/`disputed` = 中间态。
- **禁止**：`posted → pending`、任何态的物理删除/覆盖（append-only）。
- **争议冻结实现（方案 A，Owner 裁决）**：`state=disputed` 作为冻结标记，不追加反向分录、不改原账数字；业务层计算可用余额时排除 `disputed` 分录影响。
- **pending 长驻策略（Owner 裁决）**：允许长驻，不删除不清理；对账任务标记 stale pending 并生成 RiskCase。
- **角色写入（IR 638 P1-3）**：FINANCE_REVIEWER 只读，仅提交 DisputeCase/RiskCase，不直接改 `state`；L4–L7 的 state 写入归 Authoritative Writer/系统。

**Ledger Mutation Field Contract（IR 629 P1-4 + IR 638 P1-2 方案 A）**：仅 `state`、`audit_event_id`、`object_version` 三列受 Authoritative Writer 控制更新；其余字段（`ledger_entry_id`/`account_id`/`asset`/`quantity`/`entry_direction`/`entry_type`/`source_object_type`/`source_object_id`/`journal_batch_id`/`reversal_of`/`idempotency_key`/`rule_version`/`snapshot_id`/`created_time`）永久 immutable。`object_version` 经 dated migration `20260815_machine_contract_batch2_ledger_object_version.sql` 补列（不改 MC1 历史 SQL）。状态机更新走显式受控 update 路径（白名单三列 + object_version 乐观锁 + transition guard），不放宽 append-only 防线。乐观锁：`UPDATE ... SET state=?, audit_event_id=?, object_version=object_version+1 WHERE ledger_entry_id=? AND object_version=? AND state=?`；`affected_rows ≠ 1` = `OBJECT_VERSION_CONFLICT`（fail-closed）。

**Accounting Delta Matrix（IR 629 P1-4 + IR 638 P1-1：方向机械公式）**：`quantity` 恒为正数，方向由 `entry_direction`（`1=CREDIT`、`-1=DEBIT`）决定。统一 `signed_delta = quantity × entry_direction`。origin 由审计事件链确定（该分录最近一次进入 disputed 的 L4/L5 事件 before 快照）。

| 路径 | origin | L6 到 posted 账户 delta | L7 到 reversed 账户 delta |
|---|---|---|---|
| L4 | pending | `+signed_delta`（此前未入账，现入账） | `0`（未入账，仅取消标记） |
| L5 | posted | `0`（已入账，不重复） | `-signed_delta`（反向冲正） |

CREDIT/DEBIT 双套示例（禁止只验证 CREDIT）：pending+DEBIT → L6=`-100`、L7=`0`；posted+DEBIT → L6=`0`、L7=`+100`（冲正 DEBIT 恢复余额，不二次扣款）。reversal 分录字段：`entry_direction = -(原)`、`quantity = 原`、`reversal_of = 原 ledger_entry_id`、`entry_type = LEDGER_REVERSAL`。不变量：冻结期净影响恒为 0；仲裁后每个 origin 恰好一次正确余额效果，不二次入账/冲正。

### 3.2 Robot — `inactive / active / cooling / review / restricted / paused`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| R1 | `inactive` → `active` | 用户启动 Robot | END_USER | Power 充足（走 PowerImpactPreview）；资格通过 | 消耗 Power；追加审计事件 | YES（R3） |
| R2 | `active` → `cooling` | 连续运行达上限 | 系统 | 冷却阈值 = 生产参数 TBC | 停止产出 | YES（R6） |
| R3 | `active` → `inactive` | 用户停止 Robot | END_USER | — | 释放/结算运行中资源 | YES（R1） |
| R4 | `active` → `review` | 触发风控/异常 | 系统 / RISK_ANALYST | 风控引擎标记（TBC） | 锁定产出 | YES（R5） |
| R5 | `review` → `active` | 风控解除 | RISK_ANALYST / 系统 | 解除条件满足 | 恢复运行 | YES（R4） |
| R6 | `cooling` → `active` | 冷却期结束 | 系统 | 冷却时长已满 | 恢复运行 | YES（R2） |
| R7 | `active` → `restricted` | 策略受限 | 系统 / OPS_OPERATOR | 受限范围由 allowed_actions 下发 | 部分功能禁用 | YES（R8） |
| R8 | `restricted` → `active` | 受限解除 | OPS_OPERATOR | 解除条件满足 | 恢复完整功能 | YES（R7） |
| R9 | `active` → `paused` | 管理员手动暂停 | OPS_OPERATOR | 授权 | 暂停产出 | YES（R10） |
| R10 | `paused` → `active` | 管理员恢复 | OPS_OPERATOR | 授权 | 恢复运行 | YES（R9） |
| R11 | `review` → `inactive` | 风控确认违规停用 | RISK_ANALYST | 风控处置结论 | 停用 | NO（`inactive↛review`，重启走 R1） |
| R12 | `cooling`/`review`/`restricted` → `paused` | 管理员强制暂停 | OPS_OPERATOR | 授权 | 暂停 | NO（`paused↛cooling/review/restricted`，仅 R10 回 active） |

- **状态分类（IR 629 P2-1）**：无 TRUE_TERMINAL；`review`/`cooling`/`restricted` 为中间锁定态。
- **`inactive→paused` 不合法**（Owner 裁决）：paused 仅作用于运行态。

### 3.3 AI Reward — `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| W1 | `candidate` → `held` | 奖励记账确认 | 系统 | 预算内；资格快照通过 | 生成 ledger entry（CREDIT）；回填 `ledger_entry_id` | NO（`held↛candidate`，冲正走 W9） |
| W2 | `held` → `pending_claim` | 进入领取窗口 | 系统 | 窗口时长 = 生产参数 TBC | 设置 `expires_at` | NO（`pending_claim↛held`） |
| W3 | `pending_claim` → `claiming` | 用户发起领取 | END_USER | 幂等防重 | 冻结领取 | NO（`claiming↛pending_claim`） |
| W4 | `claiming` → `claimed` | 领取完成 | 系统 | 账户状态正常 | 更新 `apt_accounts`；回填 `claim_id` | NO（`claimed↛claiming`，冲正走 W10） |
| W5 | `pending_claim` → `expired_returned` | 领取窗口过期 | 系统 | 超过 `expires_at` | 退回预算池；追加 ledger entry（DEBIT） | NO |
| W7 | `candidate` → `review` | 风控冻结 | 系统 / RISK_ANALYST | 风控标记（TBC） | 冻结 | NO（`review↛candidate`，解除走 W8，冲正走 W9） |
| W8 | `review` → `held` | 风控解除 | RISK_APPROVER / 系统 | 解除条件满足 | 恢复可领 | NO（`held↛review`） |
| W9 | `held`/`review` → `reversed` | 财务冲正 | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 冲正审批通过 | 追加 reversal ledger entry | NO |
| W10 | `claimed` → `reversed` | 领取后冲正 | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 冲正审批通过 | 追加 reversal；扣回账户余额 | NO |

- **状态分类（IR 629 P2-1）**：`expired_returned`、`reversed` = TRUE_TERMINAL；`claimed` = STABLE_WITH_EXCEPTION_TRANSITIONS（仅经 W10）。
- **`held→expired_returned` 直接路径不合法**（Owner 裁决）：held 必须先经 W2 进入领取窗口。

### 3.4 Market — `draft / open / closing / locked / awaiting_result / settlement / settled / void / exception`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| M1 | `draft` → `open` | 发布市场 | OPS_OPERATOR | 参数/策略版本已冻结 | 开放投注 | NO（`open↛draft`；void 走 M8，不回 draft） |
| M2 | `open` → `closing` | 临近锁定 | 系统 | 达到 `lock_at - t` | 促使用户行动 | NO（`closing↛open`） |
| M3 | `closing` → `locked` | 到达锁定时间 | 系统 | 时间到 | 禁止新投注 | NO（`locked↛closing`） |
| M4 | `open` → `locked` | 直接锁定 | OPS_OPERATOR / 系统 | 允许跳过 closing（运营兜底） | 禁止新投注 | NO（`locked↛open`） |
| M5 | `locked` → `awaiting_result` | 赛事开始/等待结果 | 系统 | 已锁定 | 等待 Result | NO（`awaiting_result↛locked`） |
| M6 | `awaiting_result` → `settlement` | 结果确认 | 系统 | Result = `official` | 开始结算 | NO（`settlement↛awaiting_result`；异常走 M9） |
| M7 | `settlement` → `settled` | 结算完成 | 系统 | Settlement = `paid` | 订单批量 settled | YES（M12，仅 Result corrected 重开） |
| M8 | `draft`/`open`/`closing`/`locked`/`awaiting_result` → `void` | 赛事取消/作废 | OPS_OPERATOR / 系统 | 四类原因（赛事取消/延期超期/数据不可用/监管） | 触发订单退款 | NO |
| M9 | `settlement` → `exception` | 结算异常 | 系统 | 结算失败 | 冻结结算 | YES（M10） |
| M10 | `exception` → `settlement` | 异常处理后重试 | OPS_OPERATOR / 系统 | 恢复条件满足 | 重试结算 | YES（M9） |
| M11 | `exception` → `settled` | 异常处理直接完成 | OPS_OPERATOR + RISK_APPROVER 确认 | 双人确认通过（涉及资金） | 标记完成 | NO（`settled↛exception`） |
| M12 | `settled` → `settlement` | Result corrected 重开结算 | 系统 | 仅一次 | 重开结算；关联 Order 走 correcting | YES（M7） |

- **状态分类（IR 629 P2-1）**：`void` = TRUE_TERMINAL；`settled` = STABLE_WITH_EXCEPTION_TRANSITIONS（仅可经 M12）；`locked` = STABLE（可 M5/M8）。
- **`void` 源状态（IR 629 P1-2）**：`draft`/`open`/`closing`/`locked`/`awaiting_result`（结算前所有状态）均可 void；结算开始后（`settlement`/`settled`/`exception`）不 void，改走 exception/refund。
- **`settlement≠settled`**（05 §4）；`exception→settled` 双人确认（Owner 裁决 #10）；`void` 四类原因（Owner 裁决 #9）；Result corrected 重开仅一次（Owner 裁决 #11）。

### 3.5 Prediction Order — `submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| P1 | `submitted` → `locked` | 市场锁定联动 | 系统 | Market 进入 `locked` | 锁定订单 | NO（`locked↛submitted`） |
| P2 | `locked` → `awaiting_result` | 等待结果 | 系统 | 赛事开始 | 等待 | NO（`awaiting_result↛locked`） |
| P3 | `awaiting_result` → `settling` | 开始结算 | 系统 | Result official + Market settlement | 进入结算 | NO（`settling↛awaiting_result`） |
| P4 | `settling` → `settled` | 结算完成 | 系统 | 结算计算完成 | 更新账户/ledger | NO（`settled↛settling`，纠错走 P7） |
| P5 | `settling` → `refunding` | 结算异常需退款（IR 638 P1-4 方案 A） | Authoritative Writer（RefundCase 审批通过后执行） | Market = `exception` + RefundCase approved | 冻结待退 | NO（`refunding↛settling`） |
| P6 | `refunding` → `refunded` | 退款完成 | 系统 | 退款入账 | 更新账户 | NO |
| P7 | `settled` → `correcting` | 结算错误纠错 | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 仅 settlement error；审批通过 | 冻结 | NO（`correcting↛settled`） |
| P8 | `correcting` → `corrected` | 纠错完成 | RISK_APPROVER 审批 | 纠错审批通过 | 生成 reversal + new ledger | NO |
| P9 | `settling` → `correcting` | 结算中发现错误 | OPS_OPERATOR 发起 | 发现错误 | 冻结纠错 | NO（`correcting↛settling`） |
| P10 | `submitted` → `refunding` | Market void 退款（未锁定） | 系统 | 仅 `Market void` 触发 | 冻结待退 | NO（`refunding↛submitted`） |
| P11 | `locked` → `refunding` | Market void 退款（已锁定） | 系统 | 仅 `Market void` 触发 | 冻结待退 | NO（`refunding↛locked`） |
| P12 | `awaiting_result` → `refunding` | Market void 退款（等结果中） | 系统 | 仅 `Market void` 触发 | 冻结待退 | NO（`refunding↛awaiting_result`） |

- **状态分类（IR 629 P2-1）**：`refunded`、`corrected` = TRUE_TERMINAL；`settled` = STABLE_WITH_EXCEPTION_TRANSITIONS（仅经 P7）；`locked`/`awaiting_result` = STABLE（可继续结算或经 P11/P12 退款）。
- **void→refund 断路修复（IR 629 P1-2）**：Market void 时，结算前订单状态（`submitted`/`locked`/`awaiting_result`）进入 `refunding`（P10/P11/P12）；退款范围 = 全额本金；`idempotency_key` 防重。
- **settling→refunding 触发（IR 638 P1-4 方案 A）**：结算开始后不再走 `Market void` 退款（与「结算开始后不 void」自洽），改由**结算失败/异常（Market 进入 `exception`）+ RefundCase 审批通过**触发 P5；退款范围 = 全额本金；走 `ORDER_REFUND(+本金)` 分录。
- **`corrected` 不回 `settled`**（Owner 裁决 #12）：重新结算产生新 Order/新分录（CorrectionCase 追踪）。
- **`RESULT_UNKNOWN` 不混入订单状态**（05 §4）。

### 3.6 OTC Order — `draft / review / matching / partial / completed / cancelled / expired / rejected / disputed`

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| O1 | `draft` → `review` | 提交审核 | END_USER | `review_required=1` | 进入审核队列 | NO（`review↛draft`） |
| O2 | `draft` → `matching` | 提交撮合 | END_USER | `review_required=0`；资格通过 | 进入撮合 | NO（`matching↛draft`） |
| O3 | `review` → `matching` | 审核通过 | KYC_REVIEWER / OPS_OPERATOR | 审核通过 | 进入撮合 | NO（`matching↛review`） |
| O4 | `review` → `rejected` | 审核驳回 | KYC_REVIEWER / OPS_OPERATOR | 审核驳回 | 保留历史 | NO |
| O5 | `matching` → `partial` | 部分成交 | 系统 | 成交部分 | 更新 filled/remaining | NO（`partial↛matching`） |
| O6 | `matching` → `completed` | 全部成交 | 系统 | 全部成交 | 生成 Trade + Ledger | NO（`completed↛matching`，争议走 O11） |
| O7 | `matching` → `cancelled` | 用户取消 | END_USER | 未成交部分可取消 | 释放 remaining | NO |
| O8 | `matching` → `expired` | 有效期到期 | 系统 | 超过有效期 | 释放 remaining | NO |
| O9 | `partial` → `completed` | 剩余全部成交 | 系统 | 剩余成交 | 生成 Trade + Ledger | NO（`completed↛partial`，Owner 裁决禁止） |
| O10 | `partial` → `cancelled`/`expired` | 取消剩余 / 到期 | END_USER / 系统 | 仅释放 remaining | 释放 remaining | NO |
| O11 | `completed` → `disputed` | 成交后争议 | END_USER / 系统 | 争议触发 | 冻结 | YES（O12 的 `completed` 分支） |
| O12 | `disputed` → `cancelled`/`completed` | 争议处置 | RISK_APPROVER 裁决 | 裁决二选一 | 取消退钱 或 维持成交；反向冲正走 ledger reversal | YES（O11，仅 `completed` 分支；`cancelled` 分支不可逆） |

- **状态分类（IR 629 P2-1）**：`cancelled`、`expired`、`rejected` = TRUE_TERMINAL；`completed` = STABLE_WITH_EXCEPTION_TRANSITIONS（可经 O11 争议）；`disputed` = 中间态。
- **`cancelled`≠`expired`**（05 §4）；`partial+cancelled/expired` 只释放 remaining；`disputed` 冻结直到处置。
- **review_required 触发（Owner 裁决 #13）**：大额卖出、单人高频异常需人工确认；有效期 = 生产参数 TBC。
- **争议处置（Owner 裁决 #14）**：RISK_APPROVER 判 `cancelled`（退钱）或 `completed`（维持成交），不回 `partial`。

### 3.7 跨实体协同（Owner 裁决确认）

| 联动 | 依据 |
|---|---|
| Market `void` → Prediction Order `submitted`/`locked`/`awaiting_result` → `refunding` → `refunded`（P10/P11/P12） | 05 §4「已作废/已取消」+ IR 629 P1-2 |
| Market `exception` → Prediction Order `settling` → `refunding` → `refunded`（P5，RefundCase 审批） | 05 §3 RefundCase + IR 638 P1-4 方案 A |
| Result `corrected` → Market `settled → settlement` 重开 → Order `correcting → corrected` | 05 §3 Result/CorrectionCase；仅一次 |
| AI Reward `held` → Ledger `pending → posted` | 05 §3 AIReward.ledger_entry_id |
| OTC `completed` → Ledger 分录 → Power 释放 | 05 §3 OtcTrade |

## 4. Owner 裁决记录（2026-08-15）

22 项待确认 + 2 项财务硬骨头已由 Owner 逐项裁决。完整记录见 `.project-ai/tasks/TASK-20260815-001/design.md` Part D。关键裁决：

| # | 裁决 |
|---|---|
| 角色 | 财务裁决/审批 = 05 canonical（RISK_APPROVER/FINANCE_REVIEWER/OPS_OPERATOR；ADMIN_SECURITY 不涉财）；发起方 = 运营/系统 |
| 1 | 争议由运营发起，RISK_APPROVER 裁决 |
| 2 | 冲正由运营发起，RISK_APPROVER 审批 |
| 3 | 争议期间钱冻住（方案 A：不改原账数字，标记 + 业务层排除） |
| 10 | `exception→settled` 运营 + RISK_APPROVER 确认 |
| 13 | 大额卖出、单人高频异常需人工确认 |
| 14 | OTC 争议判 cancelled/completed 二选一，不回 partial |
| 财务 1 | 争议冻结 = 方案 A |
| 财务 2 | 投注结算 = 下注先扣；赢=本金+盈利入账；输=不追加；走盘=退本金 |

## 5. Event Catalog（对齐 `entry_type`/`entry_direction`）

> `entry_direction`：`1 = CREDIT(入账)`，`-1 = DEBIT(出账)`（Owner 裁决 #16）。事件码命名 `ENTITY_ACTION`（Owner 裁决 #15），覆盖 8 核心实体 A.1–A.6 全部 transition ID（IR 629 P1-1）。
> 一致性 gate（冻结前）：`MISSING_EVENT_FOR_TRANSITION = 0`；`UNKNOWN_TRANSITION_REFERENCE = 0`；`DUPLICATE_AMBIGUOUS_MAPPING = 0`。

| event_code | 来源实体 | 触发转移 | ledger entry_type | entry_direction | Power 影响 | 审计 |
|---|---|---|---|---|---|---|
| ROBOT_STARTED | robots | R1 | — | — | consume | 是 |
| ROBOT_STOPPED | robots | R3 | — | — | release | 是 |
| ROBOT_COOLING_ENTERED | robots | R2 | — | — | — | 是 |
| ROBOT_COOLING_EXITED | robots | R6 | — | — | — | 是 |
| ROBOT_REVIEW_LOCKED | robots | R4 | — | — | — | 是 |
| ROBOT_REVIEW_CLEARED | robots | R5 | — | — | — | 是 |
| ROBOT_DISABLED | robots | R11 | — | — | — | 是 |
| ROBOT_RESTRICTED | robots | R7 | — | — | — | 是 |
| ROBOT_RESTRICTION_LIFTED | robots | R8 | — | — | — | 是 |
| ROBOT_PAUSED | robots | R9, R12 | — | — | — | 是 |
| ROBOT_RESUMED | robots | R10 | — | — | — | 是 |
| REWARD_HELD | robot_rewards | W1 | REWARD_ACCRUAL | 1 (CREDIT) | — | 是 |
| REWARD_CLAIM_WINDOW_OPENED | robot_rewards | W2 | — | — | — | 是 |
| REWARD_CLAIMING | robot_rewards | W3 | — | — | — | 是 |
| REWARD_CLAIMED | robot_rewards | W4 | REWARD_CLAIM | -1 (DEBIT) | — | 是 |
| REWARD_EXPIRED_RETURNED | robot_rewards | W5 | REWARD_EXPIRY_RETURN | -1 (DEBIT) | — | 是 |
| REWARD_REVIEW_LOCKED | robot_rewards | W7 | — | — | — | 是 |
| REWARD_REVIEW_CLEARED | robot_rewards | W8 | — | — | — | 是 |
| REWARD_REVERSED | robot_rewards | W9, W10 | REWARD_REVERSAL | -1 (DEBIT) | — | 是 |
| LEDGER_ENTRY_POSTED | apt_ledger_entries | L1 | — | — | — | 是 |
| LEDGER_ENTRY_REVERSED | apt_ledger_entries | L2, L3 | LEDGER_REVERSAL | 反向 | — | 是 |
| LEDGER_ENTRY_DISPUTED | apt_ledger_entries | L4, L5 | — | — | — | 是 |
| LEDGER_ENTRY_DISPUTE_RESOLVED | apt_ledger_entries | L6 | — | — | — | 是 |
| LEDGER_ENTRY_DISPUTE_REVERSED | apt_ledger_entries | L7 | LEDGER_REVERSAL | 反向 | — | 是 |
| MARKET_PUBLISHED | prediction_markets | M1 | — | — | — | 是 |
| MARKET_CLOSING | prediction_markets | M2 | — | — | — | 是 |
| MARKET_LOCKED | prediction_markets | M3, M4 | — | — | — | 是 |
| MARKET_AWAITING_RESULT | prediction_markets | M5 | — | — | — | 是 |
| MARKET_SETTLEMENT_STARTED | prediction_markets | M6 | — | — | — | 是 |
| MARKET_SETTLED | prediction_markets | M7 | — | — | — | 是 |
| MARKET_VOIDED | prediction_markets | M8 | — | — | — | 是 |
| MARKET_SETTLEMENT_EXCEPTION | prediction_markets | M9 | — | — | — | 是 |
| MARKET_SETTLEMENT_RETRY | prediction_markets | M10 | — | — | — | 是 |
| MARKET_SETTLEMENT_COMPLETED | prediction_markets | M11 | — | — | — | 是 |
| MARKET_SETTLEMENT_REOPENED | prediction_markets | M12 | — | — | — | 是 |
| ORDER_SUBMITTED | prediction_orders | 创建 submitted | ORDER_STAKE | -1 (DEBIT) | — | 是 |
| ORDER_LOCKED | prediction_orders | P1 | — | — | — | 是 |
| ORDER_AWAITING_RESULT | prediction_orders | P2 | — | — | — | 是 |
| ORDER_SETTLING | prediction_orders | P3 | — | — | — | 是 |
| ORDER_SETTLED | prediction_orders | P4 | ORDER_SETTLEMENT | 见结算会计矩阵 | — | 是 |
| ORDER_REFUNDING | prediction_orders | P5, P10, P11, P12 | — | — | — | 是 |
| ORDER_REFUNDED | prediction_orders | P6 | ORDER_REFUND | 1 (CREDIT) | — | 是 |
| ORDER_CORRECTING | prediction_orders | P7, P9 | — | — | — | 是 |
| ORDER_CORRECTED | prediction_orders | P8 | ORDER_CORRECTION | 双向 | — | 是 |
| OTC_ORDER_CREATED | otc_orders | 创建 draft | — | — | freeze | 是 |
| OTC_ORDER_SUBMITTED_REVIEW | otc_orders | O1 | — | — | freeze | 是 |
| OTC_ORDER_SUBMITTED_MATCHING | otc_orders | O2 | — | — | freeze | 是 |
| OTC_ORDER_REVIEW_APPROVED | otc_orders | O3 | — | — | — | 是 |
| OTC_ORDER_REJECTED | otc_orders | O4 | — | — | release | 是 |
| OTC_ORDER_PARTIAL_FILLED | otc_orders | O5 | — | — | — | 是 |
| OTC_ORDER_COMPLETED | otc_orders | O6, O9 | OTC_TRADE | 双向 | consume | 是 |
| OTC_ORDER_CANCELLED | otc_orders | O7, O10, O12(cancelled) | — | — | release | 是 |
| OTC_ORDER_EXPIRED | otc_orders | O8, O10 | — | — | release | 是 |
| OTC_ORDER_DISPUTED | otc_orders | O11 | — | — | — | 是 |
| OTC_ORDER_DISPUTE_RESOLVED | otc_orders | O12 | — | — | — | 是 |

**结算会计矩阵（IR 629 P1-3，ORDER_SETTLED 消歧）**：

| Result | Settlement ledger effect | entry_direction |
|---|---|---|
| WIN | CREDIT = principal + profit | 1 (CREDIT) |
| LOSS | NO_LEDGER_ENTRY（stake 已由 ORDER_STAKE DEBIT 扣减） | — |
| PUSH | CREDIT = principal | 1 (CREDIT) |

> 不变量：任一订单净 ledger 效果仅发生一次且方向确定；void 走 ORDER_REFUND(+本金) 而非 ORDER_SETTLED；不存在二次扣款。

## 6. audit_events 表

见 `20260815_machine_contract_batch2_audit_events.sql`。关键约束：
- append-only（无 `updated_time` 列；`$timestamps=false` + `UPDATED_AT=null`；Model/DAO/Builder 三级 fail-closed，复用 MC1 §3.6 已验证机制）。
- **独立表**，不与 `sys_operation_logs` 合并（Owner 裁决）。
- **快照引用（IR 629 P1-6）**：`before/after_snapshot` 采用 `snapshot_type` + `snapshot_id` typed reference；`parameter_snapshots` 仅作为其中一种 type，不再滥用为通用业务对象快照。
- 关联回写：`apt_ledger_entries.audit_event_id` 是「最新审计事件指针」；完整时间线由 `audit_events` 重建。

## 7. 变更控制

本批冻结后修改任何状态转移路径、事件码或 `audit_events` 字段语义，必须：
1. 走 05 契约变更流程（先改 05 §3/§4，再改 DDL/矩阵）；
2. 更新本 Freeze 文档版本号；
3. 变更 DDL 以新增日期文件提交（不改历史 dated SQL）；
4. 重新触发 Independent Review（State Machine gate）。

## 8. 验收对照

- [ ] 状态转移矩阵（3.1–3.6）经 Owner 逐条裁决 + IR 通过，无自创状态（枚举全部来自 05 §4）
- [ ] Event Catalog 覆盖 A.1–A.6 全部 transition ID（MISSING=0 / ORPHAN=0），事件码与 `entry_type`/`entry_direction` 对齐
- [ ] Ledger Mutation Field Contract（方案 A：仅 state + audit_event_id + object_version 受控可变）+ Accounting Delta Matrix（`signed_delta = quantity × entry_direction`）无二次入账/冲正
- [ ] `apt_ledger_entries` 已补齐 `object_version`（dated migration `20260815_..._ledger_object_version.sql`，不改 MC1 历史 SQL），CAS 乐观锁 DETERMINISTIC
- [ ] FINANCE_REVIEWER 只读（对账差异发现/提交 Case），不直接写 `apt_ledger_entries.state`
- [ ] settling→refunding（P5）可达（结算异常 + RefundCase 审批），无 unreachable transition
- [ ] `audit_events` 表 DDL 定义（append-only + typed reference 快照，支持 MC1 §3.6 审计不变量）
- [ ] 关闭 MC1 Freeze 文档 §3.6 CONTRACT GAP（「待冻结」→「已冻结，见第二批」）
- [ ] 重新触发 Independent Review（State Machine gate）且结论为 APPROVE
