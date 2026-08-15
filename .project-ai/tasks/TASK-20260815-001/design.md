# Design: Machine Contract 第二批

> **Owner 已裁决（2026-08-15），冻结候选已收敛** — 本文件内容已由 Owner 逐项裁决（见 Part D），含 6 个尾巴默认答案；Contract 内容收敛，为 FROZEN 候选。
> 冻结流程剩余：Owner Signoff → Independent Review（State Machine gate）→ 置 FROZEN。
> 冻结前，8 个核心实体的状态流转保持 **FAIL_CLOSED**。
> 标注约定：`【已确认】` = 05 §4 / MC1 已冻结内容；`【Owner裁决】` = Owner 2026-08-15 拍板内容；`【待确认】` = 仍未决（06 TBC 处理）。

---

## Part A — Ledger Mutation Contract（状态转移矩阵）

### A.0 总则

- **来源**：枚举值全部来自 05 §4 canonical（`【已确认】`，MC1 已冻结）；转移路径由 Owner 2026-08-15 逐项裁决（`【Owner裁决】`，见 Part D），05 §4 本身未定义转移矩阵。
- **通用不变量**（对所有状态机成立，`【已确认】`依据 MC1 §3.6/§3.7 + 05 §11）：
  1. 每个 transfer 必须由该实体唯一 **Authoritative Writer**（Service）执行。
  2. 每个 transfer 必须附带 `object_version` 乐观锁校验（If-Match）。
  3. 每个 transfer 必须追加一条 append-only 审计事件并回写 `audit_event_id`（同事务原子）。
  4. 终端态不可回退到前一态（具体终端态清单见各实体）。
  5. 超级管理员不得绕过状态机（05 §11.2）。
- **表头说明**：`可逆性` 列 = 该转移是否允许被后续转移「退回」源状态（`终态` 表示进入后不可再变）。

### A.0.1 角色映射（Owner 裁决 2026-08-15）

> 本批涉财角色经 Owner 明确：**财务审核人 = 超级管理员（ADMIN_SECURITY）**。
> 因此原草案中标注 `FINANCE_REVIEWER` / `RISK_APPROVER` 的审批/裁决职责，统一由超级管理员承担；发起方为运营（OPS_OPERATOR）或系统。

| 原角色 | 裁决后职责 | 说明 |
|---|---|---|
| FINANCE_REVIEWER | 超级管理员（ADMIN_SECURITY）兼任 | 争议裁决、冲正审批、结算异常确认 |
| RISK_APPROVER | 超级管理员（ADMIN_SECURITY）兼任 | 高风险处置批准 |
| 发起方 | 运营（OPS_OPERATOR）或系统 | 争议发起、冲正发起 |

> **⚠️ 职责分离提醒（诚实边界，非阻碍）**：本项目 11 角色由 OWNER 单人兼任（manifest `p1_004_owner_freeze`）。系统层面 `OPS_OPERATOR(发起) ≠ ADMIN_SECURITY(审批)` 的角色分离仍成立；但若同一自然人同时持有两角色并自审自批，须满足 `p1_010_override_contract`（非紧急 SELF_APPROVAL=FORBIDDEN；紧急单人需 MFA + 事后 48h 审计）。此约束不影响本契约冻结，但执行时须遵守。

### A.1 Ledger Entry — `pending / posted / reversed / disputed`

> 05:746 `【已确认】`；MC1 §3.6 明确「本文件不授权任何具体 state transition」。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| L1 | `pending` → `posted` | 日记账批次原子过账 | 系统 / 运营 | 批次内分录借贷平衡；账户余额校验通过 | 更新 `apt_accounts` 余额；追加审计事件 | 可逆（仅经 L3 冲正，不能退回 pending） |
| L2 | `pending` → `reversed` | 入账前冲正 | 运营发起 + 超级管理员审批 | 分录尚未 posted | 追加 reversal 分录（`reversal_of` 指向原分录） | 终态 |
| L3 | `posted` → `reversed` | 入账后冲正 | 运营发起 + 超级管理员审批 | 冲正审批通过 | 追加 reversal 分录；反向更新 `apt_accounts` 余额 | 终态 |
| L4 | `pending` → `disputed` | 对账不符/异常标记 | 运营 / 系统 | 对账差异记录 | 冻结（不计入可用余额，见 L4 注） | 可逆（L6/L7 处置） |
| L5 | `posted` → `disputed` | 入账后发现争议 | 运营 / 系统 | 对账差异记录 | 冻结该笔影响（见 L5 注） | 可逆（L6/L7 处置） |
| L6 | `disputed` → `posted` | 仲裁确认有效 | 超级管理员裁决 | 裁决通过 | 解除冻结，按结果过账/保持 | 可逆 |
| L7 | `disputed` → `reversed` | 仲裁判定冲正 | 超级管理员裁决 | 裁决冲正 | 追加 reversal 分录 | 终态 |

- **终端态**：`reversed`（进入后不可再变）。`posted` 为稳定态，可经 L3/L5 流转，不可退回 `pending`。
- **禁止**：`posted → pending`（反过账禁止，须走冲正）、任何态的物理删除/覆盖（append-only）。
- **争议冻结实现（Owner 裁决 #3 + 财务硬骨头 1 = 方案 A）**：`state=disputed` 作为冻结标记，**不追加反向分录、不改原账数字**；业务层在计算可用余额/支取时，将 `disputed` 分录的影响排除（视为冻结）。仲裁后：L6 解除（转 posted，余额恢复可用）或 L7 正式冲正（转 reversed）。
- **L4 注**：`pending` 阶段本就不计余额，进入 `disputed` 只是标记，无余额影响。
- **pending 长驻策略（Owner 裁决 #4）**：允许长驻，不删除不清理；对账任务标记 stale pending 并生成 RiskCase。

### A.2 Robot — `inactive / active / cooling / review / restricted / paused`

> 05:740 `【已确认】`；语义见 MC1 §2「状态语义要点」。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| R1 | `inactive` → `active` | 用户启动 Robot | END_USER | Power 充足（走 PowerImpactPreview）；资格通过 | 消耗 Power；追加审计事件 | 可逆（R3/R4） |
| R2 | `active` → `cooling` | 连续运行达上限 | 系统 | 冷却阈值 = 生产参数 TBC（不硬编码） | 停止产出 | 可逆（R6 自动恢复） |
| R3 | `active` → `inactive` | 用户停止 Robot | END_USER | — | 释放/结算运行中资源 | 可逆（R1） |
| R4 | `active` → `review` | 触发风控/异常 | 系统 / RISK_ANALYST | 风控引擎标记（TBC） | 锁定产出 | 可逆（R5） |
| R5 | `review` → `active` | 风控解除 | RISK_ANALYST / 系统 | 解除条件满足 | 恢复运行 | 可逆 |
| R6 | `cooling` → `active` | 冷却期结束 | 系统 | 冷却时长已满 | 恢复运行 | 可逆（R2） |
| R7 | `active` → `restricted` | 策略受限 | 系统 / OPS_OPERATOR | 受限范围由 allowed_actions 下发 | 部分功能禁用 | 可逆（R8） |
| R8 | `restricted` → `active` | 受限解除 | OPS_OPERATOR | 解除条件满足 | 恢复完整功能 | 可逆 |
| R9 | `active` → `paused` | 管理员手动暂停 | OPS_OPERATOR | 授权 | 暂停产出 | 可逆（R10） |
| R10 | `paused` → `active` | 管理员恢复 | OPS_OPERATOR | 授权 | 恢复运行 | 可逆 |
| R11 | `review` → `inactive` | 风控确认违规停用 | RISK_ANALYST | 风控处置结论 | 停用 | 可逆（R1 重启，Owner 裁决允许） |
| R12 | `cooling`/`review`/`restricted` → `paused` | 管理员强制暂停 | OPS_OPERATOR | 授权 | 暂停 | 可逆 |

- **终端态**：无严格终态（`paused`/`inactive` 均可回到 `active`）；`review`/`cooling`/`restricted` 为中间锁定态。
- **冷却阈值 / review 触发（Owner 裁决 #5）**：冷却阈值 = 生产参数 TBC（矩阵只定义「连续运行超阈值 → cooling」规则，不硬编码值）；review 触发 = 风控引擎标记 TBC。
- **restricted 范围 / `inactive→paused`（Owner 裁决 #6）**：restricted 禁哪些功能由 `allowed_actions` 动态下发（不枚举具体功能）；**`inactive→paused` 不合法**（paused 仅作用于运行态）。

### A.3 AI Reward — `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed`

> 05:743 `【已确认】`；语义见 MC1 §2（8 态）。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| W1 | `candidate` → `held` | 奖励记账确认 | 系统 | 预算内；资格快照通过 | 生成 ledger entry（CREDIT）；回填 `ledger_entry_id` | 可逆（仅经 W9 冲正） |
| W2 | `held` → `pending_claim` | 进入领取窗口 | 系统 | 窗口时长 = 生产参数 TBC | 设置 `expires_at` | 可逆（W5 过期） |
| W3 | `pending_claim` → `claiming` | 用户发起领取 | END_USER | 幂等防重 | 冻结领取 | 可逆（W5 完成 / W6 过期） |
| W4 | `claiming` → `claimed` | 领取完成 | 系统 | 账户状态正常 | 更新 `apt_accounts`；回填 `claim_id` | 可逆（仅经 W10 冲正） |
| W5 | `pending_claim` → `expired_returned` | 领取窗口过期 | 系统 | 超过 `expires_at` | 退回预算池；追加 ledger entry（DEBIT） | 终态 |
| W7 | `candidate` → `review` | 风控冻结 | 系统 / 风控引擎 | 风控标记（TBC） | 冻结 | 可逆（W8/W9） |
| W8 | `review` → `held` | 风控解除 | 超级管理员 / 系统 | 解除条件满足 | 恢复可领 | 可逆 |
| W9 | `held`/`review` → `reversed` | 财务冲正 | 运营发起 + 超级管理员审批 | 冲正审批通过 | 追加 reversal ledger entry | 终态 |
| W10 | `claimed` → `reversed` | 领取后冲正 | 运营发起 + 超级管理员审批 | 冲正审批通过 | 追加 reversal；扣回账户余额 | 终态 |

- **终端态**：`expired_returned`、`reversed`、`claimed`（claimed 仅可经 W10 冲正，不回退）。
- **`held→expired_returned` 直接路径不合法（Owner 裁决 #8）**：held 必须先经 W2（`held→pending_claim`）进入领取窗口，才能经 W5 过期退回，禁止跳过窗口的状态跳跃。
- **领取窗口时长（Owner 裁决 #7）**：= 生产参数 TBC（不硬编码）；过期退回目标 = 原预算池（`budget_snapshot_id` 指向 pool）；review 触发 = 风控标记 TBC。

### A.4 Market — `draft / open / closing / locked / awaiting_result / settlement / settled / void / exception`

> 05:749 `【已确认】`；语义见 MC1 §2 + 05 §4「Prediction 聚合展示映射」。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| M1 | `draft` → `open` | 发布市场 | OPS_OPERATOR | 参数/策略版本已冻结 | 开放投注 | 可逆（仅 M8 void） |
| M2 | `open` → `closing` | 临近锁定 | 系统 | 达到 `lock_at - t` | 促使用户行动 | 可逆 |
| M3 | `closing` → `locked` | 到达锁定时间 | 系统 | 时间到 | 禁止新投注 | 终态（可 M5/M8） |
| M4 | `open` → `locked` | 直接锁定 | OPS_OPERATOR / 系统 | 允许跳过 closing（运营兜底锁定，Owner 裁决） | 禁止新投注 | 终态（可 M5/M8） |
| M5 | `locked` → `awaiting_result` | 赛事开始/等待结果 | 系统 | 已锁定 | 等待 Result | 可逆 |
| M6 | `awaiting_result` → `settlement` | 结果确认 | 系统 | Result = `official` | 开始结算 | 可逆（M9 exception） |
| M7 | `settlement` → `settled` | 结算完成 | 系统 | Settlement = `paid` | 订单批量 settled | 终态 |
| M8 | `awaiting_result`/`open`/`draft` → `void` | 赛事取消/作废 | OPS_OPERATOR / 系统 | 四类原因（赛事取消/延期超期/数据不可用/监管，reason_code 承载） | 触发订单退款 | 终态 |
| M9 | `settlement` → `exception` | 结算异常 | 系统 | 结算失败 | 冻结结算 | 可逆（M10/M11） |
| M10 | `exception` → `settlement` | 异常处理后重试 | OPS_OPERATOR / 系统 | 恢复条件满足 | 重试结算 | 可逆 |
| M11 | `exception` → `settled` | 异常处理直接完成 | 运营 + 超级管理员确认 | 双人确认通过（涉及资金） | 标记完成 | 终态 |

- **终端态**：`settled`、`void`。
- **关键区分**（`【已确认】`，05 §4）：`settlement`（处理中）≠ `settled`（已完成）；`void` 原因之一是赛事取消，但非唯一。
- **`exception→settled` 双人确认（Owner 裁决 #10）**：必须运营（OPS_OPERATOR）+ 超级管理员（ADMIN_SECURITY）确认，不可自动完成；`exception→settlement` 重试可自动。
- **Result corrected 重开结算（Owner 裁决 #11）**：Result corrected 时，已 settled 的 Market 走 `settled → settlement` 重开；关联 Order 走 `settled → correcting → corrected`；Result corrected 仅允许一次。此路径补入转移矩阵如下：
  - M12：`settled → settlement`（Result corrected 触发，仅一次）
- **void 原因清单（Owner 裁决 #9）**：赛事取消 / 赛事延期超期 / 源数据不可用 / 监管要求，用 `reason_code` 承载，不新增状态。

### A.5 Prediction Order — `submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected`

> 05:758 `【已确认】`；语义见 MC1 §2（RESULT_UNKNOWN 不混入、correcting 仅 settlement error）。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| P1 | `submitted` → `locked` | 市场锁定联动 | 系统 | Market 进入 `locked` | 锁定订单 | 终态（可 P3+） |
| P2 | `locked` → `awaiting_result` | 等待结果 | 系统 | 赛事开始 | 等待 | 可逆 |
| P3 | `awaiting_result` → `settling` | 开始结算 | 系统 | Result official + Market settlement | 进入结算 | 可逆 |
| P4 | `settling` → `settled` | 结算完成 | 系统 | 结算计算完成 | 更新账户/ledger | 终态（可 P7 纠错） |
| P5 | `settling` → `refunding` | 结算异常需退款 | 系统 | 仅 `Market void` 触发（Owner 裁决） | 冻结待退 | 可逆（P6） |
| P6 | `refunding` → `refunded` | 退款完成 | 系统 | 退款入账 | 更新账户 | 终态 |
| P7 | `settled` → `correcting` | 结算错误纠错 | 运营发起 + 超级管理员审批 | 仅 settlement error；审批通过 | 冻结 | 可逆（P8） |
| P8 | `correcting` → `corrected` | 纠错完成 | 超级管理员审批 | 纠错审批通过 | 生成 reversal + new ledger | 终态 |
| P9 | `settling` → `correcting` | 结算中发现错误 | 运营发起 | 发现错误 | 冻结纠错 | 可逆（P8） |

- **终端态**：`settled`、`refunded`、`corrected`。
- `【已确认】`：`RESULT_UNKNOWN` 不混入订单状态；`correcting/corrected` 仅在 settlement error 触发。
- **`corrected` 不回 `settled`（Owner 裁决 #12）**：`corrected` 为终态；重新结算产生新 Order/新分录（用 CorrectionCase 追踪），不改旧订单状态。

### A.6 OTC Order — `draft / review / matching / partial / completed / cancelled / expired / rejected / disputed`

> 05:761 `【已确认】`；完整「运营/用户展示映射」见 05 §4（含可执行操作 + 下一步）。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | 可逆性 |
|---|---|---|---|---|---|---|
| O1 | `draft` → `review` | 提交审核 | END_USER | `review_required=1` | 进入审核队列 | 可逆（O3/O4） |
| O2 | `draft` → `matching` | 提交撮合 | END_USER | `review_required=0`；资格通过 | 进入撮合 | 可逆（O6/O7） |
| O3 | `review` → `matching` | 审核通过 | KYC_REVIEWER / OPS_OPERATOR | 审核通过 | 进入撮合 | 可逆 |
| O4 | `review` → `rejected` | 审核驳回 | KYC_REVIEWER / OPS_OPERATOR | 审核驳回 | 保留历史 | 终态 |
| O5 | `matching` → `partial` | 部分成交 | 系统 | 成交部分 | 更新 filled/remaining | 可逆（继续 O5 或 O6/O7） |
| O6 | `matching` → `completed` | 全部成交 | 系统 | 全部成交 | 生成 Trade + Ledger | 终态（可 O11 disputed） |
| O7 | `matching` → `cancelled` | 用户取消 | END_USER | 未成交部分可取消 | 释放 remaining | 终态 |
| O8 | `matching` → `expired` | 有效期到期 | 系统 | 超过有效期 | 释放 remaining | 终态 |
| O9 | `partial` → `completed` | 剩余全部成交 | 系统 | 剩余成交 | 生成 Trade + Ledger | 终态（可 O11） |
| O10 | `partial` → `cancelled`/`expired` | 取消剩余 / 到期 | END_USER / 系统 | 仅释放 remaining | 释放 remaining | 终态 |
| O11 | `completed` → `disputed` | 成交后争议 | END_USER / 系统 | 争议触发 | 冻结 | 可逆（O12） |
| O12 | `disputed` → `cancelled`/`completed` | 争议处置 | 超级管理员裁决 | 裁决二选一 | 取消退钱 或 维持成交；反向冲正走 ledger reversal | 终态 |

- **终端态**：`completed`、`cancelled`、`expired`、`rejected`（`completed` 可经 O11 争议）。
- `【已确认】`（05 §4）：`cancelled`=主动取消，`expired`=自然到期（非取消）；`partial+cancelled/expired` 只释放 remaining；`disputed` 保持冻结直到处置；不删除/覆盖历史 Trade/Ledger/Power Ledger。
- **review_required 触发条件（Owner 裁决 #13）**：大额卖出、单人高频异常需人工确认；有效期 = 生产参数 TBC。
- **争议处置（Owner 裁决 #14）**：超级管理员判成 `cancelled`（退钱）或 `completed`（维持成交）二选一，**不允许回到 `partial`** 中间态。

### A.7 跨实体协同

以下跨实体联动，部分已被 Owner 裁决确认，部分仍待确认：

| 联动 | 推断依据 | 状态 |
|---|---|---|
| Market `void` → Prediction Order `refunding → refunded` | 05 §4「已作废/已取消 → Market void + reason_code」 | ✅ Owner 裁决（05 语义自然推论） |
| Result `corrected` → Market 重开结算 → Order `correcting → corrected` | 05 §3 Result + CorrectionCase 对象 | ✅ Owner #11（`settled→settlement` 重开，仅一次） |
| AI Reward `held` → Ledger `pending → posted` | 05 §3 AIReward.ledger_entry_id + Ledger 四账 | ✅ Owner 裁决（05 语义自然推论） |
| OTC `completed` → Ledger 分录 → Power 释放 | 05 §3 OtcTrade.ledger_entry_ids + power_consumed | ✅ Owner 裁决（05 语义自然推论） |

---

## Part B — Event Catalog（事件目录草案）

### B.0 总则

- 05 §4 **未定义事件码**；`apt_ledger_entries.entry_type`（varchar(64)）与 `entry_direction`（tinyint）「与 Event Catalog 对齐后冻结」（MC1 §4）。
- 事件码命名 `ENTITY_ACTION` 大写下划线风格（与 `sys_operation_logs` 风格一致），**Owner 已采纳（#15）**，全集以「8 核心实体所有状态转移 + 账本分录」为边界。
- **规则**：每个业务事件可能 (a) 触发一个状态转移，(b) 生成 ledger entry（含 entry_type/entry_direction），(c) 产生 Power 影响，(d) 要求审计事件。四者相互独立，需分别声明。

### B.1 事件目录表（草案）

| event_code | 来源实体 | 触发转移 | ledger entry_type | entry_direction | Power 影响 | 审计 |
|---|---|---|---|---|---|---|
| ROBOT_STARTED | robots | R1 inactive→active | — | — | consume | 是 |
| ROBOT_STOPPED | robots | R3 active→inactive | — | — | release | 是 |
| ROBOT_COOLING_ENTERED | robots | R2 active→cooling | — | — | — | 是 |
| ROBOT_REVIEW_LOCKED | robots | R4 active→review | — | — | — | 是 |
| ROBOT_PAUSED | robots | R9 active→paused | — | — | — | 是 |
| REWARD_HELD | robot_rewards | W1 candidate→held | REWARD_ACCRUAL | 1 (CREDIT) | — | 是 |
| REWARD_CLAIMED | robot_rewards | W4 claiming→claimed | REWARD_CLAIM | -1 (DEBIT) | — | 是 |
| REWARD_EXPIRED_RETURNED | robot_rewards | W5/W6 →expired_returned | REWARD_EXPIRY_RETURN | -1 (DEBIT) | — | 是 |
| REWARD_REVERSED | robot_rewards | W9/W10 →reversed | REWARD_REVERSAL | -1 (DEBIT) | — | 是 |
| LEDGER_ENTRY_POSTED | apt_ledger_entries | L1 pending→posted | — | — | — | 是 |
| LEDGER_ENTRY_REVERSED | apt_ledger_entries | L2/L3 →reversed | LEDGER_REVERSAL | 反向 | — | 是 |
| LEDGER_ENTRY_DISPUTED | apt_ledger_entries | L4/L5 →disputed | — | — | — | 是 |
| MARKET_PUBLISHED | prediction_markets | M1 draft→open | — | — | — | 是 |
| MARKET_LOCKED | prediction_markets | M3/M4 →locked | — | — | — | 是 |
| MARKET_VOIDED | prediction_markets | M8 →void | — | — | — | 是 |
| MARKET_SETTLED | prediction_markets | M7 →settled | — | — | — | 是 |
| ORDER_SUBMITTED | prediction_orders | (创建 submitted) | ORDER_STAKE | -1 (DEBIT) | — | 是 |
| ORDER_SETTLED | prediction_orders | P4 →settled | ORDER_SETTLEMENT | 1/-1 视结果 | — | 是 |
| ORDER_REFUNDED | prediction_orders | P6 →refunded | ORDER_REFUND | 1 (CREDIT) | — | 是 |
| ORDER_CORRECTED | prediction_orders | P8 →corrected | ORDER_CORRECTION | 双向 | — | 是 |
| OTC_ORDER_CREATED | otc_orders | (创建 draft) | — | — | freeze | 是 |
| OTC_ORDER_COMPLETED | otc_orders | O6/O9 →completed | OTC_TRADE | 双向 | consume | 是 |
| OTC_ORDER_CANCELLED | otc_orders | O7/O10 →cancelled | — | — | release | 是 |
| OTC_ORDER_EXPIRED | otc_orders | O8/O10 →expired | — | — | release | 是 |
| OTC_ORDER_DISPUTED | otc_orders | O11 →disputed | — | — | — | 是 |

### B.2 已确认 / 待确认（Event Catalog）

- **事件码命名与全集（Owner 裁决 #15）**：采用 Part B 表，命名 `ENTITY_ACTION`，覆盖 8 核心实体，本次不扩到非核心实体。
- **`entry_direction` 语义（Owner 裁决 #16）**：`1=CREDIT(入账)`、`-1=DEBIT(出账)`，与 DDL 注释一致。
- **`ORDER_SETTLED` 区分赢/输/走盘（Owner 裁决 #17）**：赢=本金+盈利 CREDIT；输=不追加（stake 已由 ORDER_STAKE DEBIT 扣减）；走盘=本金退回 CREDIT。
- **审计事件表 schema（Owner 裁决 #18）**：见 Part E `audit_events` 表 DDL 草案（字段对齐 05 §3 AuditLog）。
- **审计表两个细节（Owner 裁决，冻结默认答案）**：① `audit_events` 与 `sys_operation_logs` **不合并**（`sys_operation_logs` 是 V1.x 遗留操作日志，`audit_events` 是 V2.0 领域审计，语义不同）；② `before/after_snapshot_id` **引用 `parameter_snapshots`**（复用现有参数快照，不发明 diff 机制）。

---

## Part C — 非核心实体清单 + DDL 草案

### C.0 范围判定

- 05 §3 对象全集（约 40 个对象）中，MC1 第一批已冻结 8 个核心实体。
- 其余对象分三类：**持久领域实体/工作流对象**（需 DDL + state freeze）、**只读投影/值对象**（是否落表待定）、**05 NOT DEFINED**（需先 Contract Freeze）。

### C.1 建议纳入第二批（DDL + state freeze，草案）

| # | 实体（05 §3） | 建议表名 | 状态机（05 §4） | 优先级 | 说明 |
|---|---|---|---|---|---|
| 1 | Result | `results` | Result: provisional/official/disputed/corrected | P0 | 独立对象，Market.result_status 投影自它（MC1 §4 已注明） |
| 2 | Settlement | `settlements` | Settlement: queued/calculating/review/payable/paid/failed | P0 | Prediction 结算 |
| 3 | SettlementBatch | `settlement_batches` | `【待确认】`（05 §3 有 status 无 canonical enum） | P0 | 批量结算 |
| 4 | RefundCase | `refund_cases` | `【待确认】` | P0 | 退款工作流 |
| 5 | CorrectionCase | `correction_cases` | `【待确认】` | P0 | 纠错工作流 |
| 6 | OtcTrade | `otc_trades` | `【待确认】`（05 §3 有 status 无 enum） | P0 | OTC 成交记录 |
| 7 | RobotUpgradeOrder | `robot_upgrade_orders` | `【待确认】` | P1 | Robot 升级订单 |
| 8 | ConsentReceipt | `consent_receipts` | `【待确认】` | P1 | 同意回执（prediction_orders 引用） |
| 9 | ApprovalRequest | `approval_requests` | Approval: draft/pending/changes_requested/approved/rejected/executing/executed/failed | P1 | 审批工作流 |
| 10 | ParameterRelease | `parameter_releases` | Parameter Release: draft/pending_approval/approved/scheduled/active/paused/rolled_back/archived | P1 | 参数版本化 |
| 11 | ParameterSnapshot | `parameter_snapshots` | 无（只读投影） | P1 | 参数快照 |
| 12 | AuditLog | `audit_events` | 无（只读） | P1 | 审计事件（append-only，MC1 §3.6 硬约束依赖） |
| 13 | Notice | `notices` | 无（read_state） | P2 | 通知 |
| 14 | NotificationDelivery | `notification_deliveries` | delivery_status（`【待确认】`enum） | P2 | 通知投递 |
| 15 | MfaEnrollment | `mfa_enrollments` | `【待确认】` | P2 | MFA |
| 16 | AuthSession | `auth_sessions` | active/mfa_required/restricted/expired/revoked（05 §2.2，非 §4 状态机） | P2 | 会话 |
| 17 | KycCase | `kyc_cases` | KYC: not_started/pending/needs_info/approved/rejected/review | P2 | KYC |
| 18 | RiskCase | `risk_cases` | `【待确认】` | P2 | 风控案件 |
| 19 | Ticket / TicketMessage / TicketAttachment | `tickets` / `ticket_messages` / `ticket_attachments` | Ticket: submitted/in_progress/waiting_user/under_review/resolved/closed | P2 | 工单 |

### C.2 建议延后（需先 Contract Freeze，05 NOT DEFINED）

| 模块 | 说明 |
|---|---|
| Affiliate / Agent（`agents`/`referrals`/`agent_earnings`） | 05: NOT DEFINED，状态枚举 TBC |
| AI Signal / Recommendation / Simulation | 05: NOT DEFINED，状态枚举 TBC |
| APT-C（`balance_apt_c`/`frozen_apt_c`）记账能力 | MC1 §3.10 已标 OUT_OF_SCOPE/Future |

### C.3 已确认（非核心实体清单，Owner 裁决 #19–#22）

- **第二批精确范围（Owner 裁决 #19）**：拆两小批 —— **2B-1** = Result / Settlement / SettlementBatch / RefundCase / CorrectionCase / OtcTrade / RobotUpgradeOrder / ConsentReceipt / audit_events（P0，解锁 Prediction/OTC 完整闭环）；**2B-2** = 审批 / 参数 / 通知 / 会话 / KYC / 风控 / 工单（P1/P2）。
- **只读投影/值对象是否落表（Owner 裁决 #20）**：**不落表** —— FeatureEntitlement / OtcEligibility / OtcCapacity / SecurityProfile / SessionDevice / LoginAudit / PowerImpactPreview（请求时计算投影）；**落表** —— SettlementMethod（持久值对象）。
- **status enum 补充（Owner 裁决 #21）**：`settlement_batches` / `otc_trades` / `refund_cases` / `correction_cases` / `mfa_enrollments` / `risk_cases` 的 status **冻结前补进 05 §4**（走 05 变更流程），否则 FAIL_CLOSED 不建表。
- **auth_sessions.status（Owner 裁决 #22）**：05 §2.2 五态作为 canonical，转移矩阵单独冻结（auth 模块属 2B-2）。

---

## Part D — Owner 裁决记录（2026-08-15，已确认）

> 本节记录 Owner 对 22 项待确认 + 2 项财务硬骨头的**最终裁决**。裁决结果已同步回 Part A（转移矩阵）、Part B（Event Catalog）、Part C（非核心实体清单）正文。
> 状态：**Owner 已裁决，契约内容已收敛**；但**尚未 FROZEN** —— 正式冻结仍需走 Independent Review（State Machine gate）。

### D.0 角色裁决

- **财务审核人 = 超级管理员（ADMIN_SECURITY）**。
- 争议、冲正、结算异常、OTC 争议处置、纠错等涉财审批/裁决，统一由超级管理员承担；发起方为运营（OPS_OPERATOR）或系统。
- ⚠️ 单人项目下角色分离见 Part A.0.1 提醒（`p1_010_override_contract` 兜底）。

### D.1 逐项裁决

| # | 裁决 | 落点 |
|---|---|---|
| 1 | 争议由**运营发起**，**超级管理员裁决**；用户不能自行发起 | A.1 L4/L5/L6/L7 |
| 2 | 冲正由**运营发起**，**超级管理员审批** | A.1 L2/L3 |
| 3 | 争议期间**钱要冻住**（方案 A：不改原账数字，`state=disputed` 标记 + 业务层排除冻结） | A.1 争议冻结实现 |
| 4 | pending 长驻，不删不清理，stale 报 RiskCase | A.1 pending 长驻策略 |
| 5 | 冷却阈值/review 触发 = 生产参数 TBC，只定义规则 | A.2 |
| 6 | restricted 由 allowed_actions 下发；`inactive→paused` 不合法 | A.2 |
| 7 | 领取窗口时长 TBC；退回原预算池；review 触发 TBC | A.3 |
| 8 | `held→expired_returned` 直接路径不合法 | A.3 |
| 9 | void 四类原因（赛事取消/延期超期/数据不可用/监管），reason_code 承载 | A.4 |
| 10 | `exception→settled` 必须**运营 + 超级管理员**确认；`exception→settlement` 可自动 | A.4 |
| 11 | Result corrected 重开 Market 结算（`settled→settlement`），仅一次 | A.4 M12 |
| 12 | `corrected` 为终态，不回 settled，重新结算走新对象 | A.5 |
| 13 | 大额卖出、单人高频异常需人工确认；有效期 TBC | A.6 |
| 14 | OTC 争议：超级管理员判 `cancelled`（退钱）或 `completed`（维持成交），不回 partial | A.6 O12 |
| 15 | 采用 Part B 事件码，覆盖 8 核心实体 | Part B |
| 16 | `1=CREDIT 入账`，`-1=DEBIT 出账` | Part B |
| 17 | 赢=本金+盈利入账；输=不追加；走盘=退本金 | Part B #17 |
| 18 | audit_events 表对齐 05 AuditLog | Part E |
| 19 | 拆 2B-1（P0）/ 2B-2（P1/P2）两小批 | Part C |
| 20 | 只读投影不落表；SettlementMethod 落表 | Part C |
| 21 | 缺 enum 的先补 05 §4 再建表，否则 FAIL_CLOSED | Part C |
| 22 | auth_sessions 单独冻结，归 2B-2 | Part C |

### D.2 财务硬骨头裁决

| # | 裁决 |
|---|---|
| 财务 1 | **方案 A**：争议期间不改原账数字，`state=disputed` 标记 + 业务层排除冻结（已并入 #3） |
| 财务 2 | **投注结算会计**：下注时先扣钱（出账 DEBIT）；赢了「本金+盈利」入账（CREDIT）；输了不额外记；走盘退本金（CREDIT） |

---

## Part E — audit_events 表 DDL 草案

> **DRAFT，不落 `sql/`**。字段对齐 05 §3 AuditLog 对象 + MC1 §3.6 审计不变量（append-only、`ledger_entry_id` 关联、顺序可重建）。
> 本表为 MC1 §3.6「审计事件表 schema 待冻结」的落地方案，供 Owner 审阅。

```sql
-- =============================================================================
-- audit_events — 审计事件表（append-only，Machine Contract 第二批草案）
-- 状态：DRAFT（未冻结，未落 sql/）
-- 依据：05 §3 AuditLog 对象 + MC1 §3.6 审计不变量
-- 规则：append-only（无 UPDATE/DELETE），一事件一行，顺序可重建
-- =============================================================================
CREATE TABLE `audit_events` (
  `audit_event_id` bigint unsigned NOT NULL COMMENT '审计事件ID(Snowflake，主键)',
  `event_code` varchar(64) NOT NULL DEFAULT '' COMMENT '事件码(对齐 Event Catalog)',
  `actor_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '操作者ID(user_id 或系统=0)',
  `actor_role` varchar(32) NOT NULL DEFAULT '' COMMENT '操作者角色(05 §8 RBAC)',
  `target_object_type` varchar(64) NOT NULL DEFAULT '' COMMENT '目标对象类型(如 apt_ledger_entries)',
  `target_object_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '目标对象ID',
  `before_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '变更前快照ID',
  `after_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '变更后快照ID',
  `outcome` varchar(32) NOT NULL DEFAULT '' COMMENT '结果(SUCCESS/FAILED/REJECTED)',
  `reason_code` varchar(64) NOT NULL DEFAULT '' COMMENT '原因码',
  `request_id` varchar(64) NOT NULL DEFAULT '' COMMENT '请求ID',
  `approval_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联审批ID',
  `case_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '关联案件ID',
  `created_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(Unix秒)',
  PRIMARY KEY (`audit_event_id`),
  KEY `idx_target` (`target_object_type`,`target_object_id`),
  KEY `idx_actor` (`actor_id`),
  KEY `idx_event_code` (`event_code`),
  KEY `idx_created_time` (`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审计事件表(append-only，无UPDATE/DELETE)';
```

**append-only 约束**（与 `apt_ledger_entries` 同机制）：无 `updated_time` 列；`$timestamps=false` + `UPDATED_AT=null`；Model/DAO/Builder 三级 fail-closed（复用 MC1 §3.6 已验证的 append-only 强制模式）。

**关联回写**：`apt_ledger_entries.audit_event_id` 是「最新审计事件指针」；完整时间线由 `audit_events`（`target_object_id = ledger_entry_id`，按 `created_time` + `audit_event_id` 排序重建）保留，满足 MC1 §3.6「不会退化为只剩最后一次」。

**已确认（Owner 裁决，冻结默认答案）**：① `audit_events.event_code` 与 `sys_operation_logs` **不合并**（独立表）；② `before/after_snapshot_id` **引用 `parameter_snapshots`**。

---

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md` — §2.2 Session 状态、§3 对象字段、§4 统一状态机 + 展示映射、§6 API、§11 RBAC。
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md` — §2 状态语义要点、§3.6 账本 append-only、§4 未冻结项（CONTRACT GAP）。
- `0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql` — 8 核心实体 DDL（entry_type/entry_direction/audit_event_id 字段）。
- `0.5代码/gainode后端/gainode/library/{model,service}/**` — STAGE-01 骨架（状态常量、FAIL_CLOSED、@authoritative_writer 约定）。
- `.project-ai/manifest.yaml` — `p1_003_two_phase_freeze`（两批冻结决策）。
- `.project-ai/context.md` — API Freeze 推迟至 STAGE-02。

## 已确认信息 / Owner 裁决 / 待确认事项

- **已确认信息**：8 核心实体状态枚举（05 §4 canonical，MC1 已冻结）；账本 append-only 语义（MC1 §3.6）；`entry_type`/`entry_direction`/`audit_event_id` 字段存在且待冻结（MC1 DDL + §4）；状态转移矩阵是 CONTRACT GAP（MC1 §3.6）。
- **Owner 裁决（2026-08-15）**：Part D 的 22 项 + 2 项财务硬骨头全部已裁决；角色映射（财务审核人=超级管理员）；转移矩阵、Event Catalog、非核心实体清单、`audit_events` DDL 均已收敛。
- **待确认事项（不阻塞契约收敛，冻结后由 06 处理）**：① 生产参数数值（冷却阈值、领取窗口、OTC 有效期等，06 TBC）；② 单人项目下 OPS_OPERATOR↔ADMIN_SECURITY 职责分离的落地（`p1_010_override_contract` 兜底，执行时遵守）。
