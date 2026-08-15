# Design: Machine Contract 第二批

> **状态：Owner Signoff 完成，Independent Review = CHANGES_REQUIRED（IR 682，2026-08-15），修复中**。
> IR 629 返回 6 P1 + 2 P2，已修复并重提。IR 638（复审）返回 4 P1 + 2 P2，已按 Owner 二次裁决修复。IR 659（三审）返回 **2 P1 + 3 P2**（dispute hold 四格冻结 / pending reversal 语义 / DisputeCase→RiskCase / object_version 补 CR / 证据完整性），已修复。IR 679（四审）返回 **1 P1 + 2 P2**（posted CREDIT shortfall 边界 / RiskCase 冻结状态矛盾 / 证据截断），已修复。IR 682（五审）返回 **2 P1 + 1 P2**：P1-1 shortfall 检查时机自相矛盾（L5 前阻断 vs L5 后只锁 L6/L7）；P1-2 dispute_hold 账户级并发控制缺失（单条 `object_version` 无法阻止并发 L5 超额冻结）；P2-1 证据截断未闭环（settings.json 不在 commit 且实际 diff 仍截断）。
> 本文件已修复 IR 682 全部 2 P1 + 1 P2（`SHORTFALL_CHECK_PHASE = PRE_L5` 消除歧义 / `dispute_hold` 账户级聚合 + `apt_accounts.object_version` CAS 并发锁 / 证据验收改为 `REVIEW_PACKAGE_TRUNCATED = NO`）。
> 冻结流程：Owner Signoff ✅ → Independent Review（CHANGES_REQUIRED，修复后重提）→ 置 FROZEN。
> 正式 FROZEN 前，8 个核心实体的状态流转保持 **FAIL_CLOSED**。
> 标注约定：`【已确认】` = 05 §4 / MC1 已冻结内容；`【Owner裁决】` = Owner 2026-08-15 拍板内容；`【IR修复】` = 针对 IR 629/IR 638/IR 659/IR 679/IR 682 的修复；`【待确认】` = 仍未决（06 TBC 处理）。

---

## Part A — Ledger Mutation Contract（状态转移矩阵）

### A.0 总则

- **来源**：枚举值全部来自 05 §4 canonical（`【已确认】`，MC1 已冻结）；转移路径由 Owner 2026-08-15 逐项裁决（`【Owner裁决】`，见 Part D），05 §4 本身未定义转移矩阵。
- **通用不变量**（对所有状态机成立，`【已确认】`依据 MC1 §3.6/§3.7 + 05 §11）：
  1. 每个 transfer 必须由该实体唯一 **Authoritative Writer**（Service）执行。
  2. 每个 transfer 必须附带 `object_version` 乐观锁校验（If-Match）。**8 个核心实体均含 `object_version` 列**（`apt_ledger_entries` 经 dated migration `20260815_..._ledger_object_version.sql` 补齐，见 A.1.1；`【IR修复】` P1-2 方案 A）。
  3. 每个 transfer 必须追加一条 append-only 审计事件并回写 `audit_event_id`（同事务原子）。
  4. 状态不可任意流转：只能走本文件定义的合法出边（allowed outgoing transitions），无授权转移 FAIL_CLOSED。
  5. 超级管理员不得绕过状态机（05 §11.2）。
  6. **高风险涉财 transition 必须绑定真实 `ApprovalRequest`**（`【IR修复】` IR 659 STEP 6）：`approval_id required`、`approval.status = approved`、`request_type`/`request_object_type`/`request_object_id` 与操作匹配、`initiating_actor != approved_actor`（发起≠审批）、approval 未被消费/过期。`ApprovalRequest` 契约（归属 2B-2，见 Part C）未冻结 → 相关涉财 transition FAIL_CLOSED。
- **终态概念拆分（消除歧义，`【IR修复】` P2-1）**：废弃模糊的「终态」一词，改用三档精确概念：
  - **TRUE_TERMINAL（真终态）**：无任何出边，进入后不可再变。如 Ledger `reversed`、Reward `expired_returned`、Order `refunded`/`corrected`、Market `void`、OTC `cancelled`/`expired`/`rejected`。
  - **STABLE_WITH_EXCEPTION_TRANSITIONS（稳定态 + 例外转移）**：有出边，但仅限冲正/纠错/争议例外，不能回到业务前序态。如 Ledger `posted`、Order `settled`、Market `settled`、OTC `completed`。
  - **NON_REVERSIBLE_TO_PREVIOUS_STATE（不可回退到前一态）**：单条转移方向不可逆（回退须走冲正/纠错），但不等于无出边。
- **表头说明（`【IR修复】` IR 638 P2-1）**：`direct_reverse` 列 = 是否存在从**目标态直接回到源态**的合法转移，取值仅 `YES`（列出反向转移 ID）/`NO`。终态/稳定态分类统一由表下「状态分类」bullet 承载，不再用自由文本「可逆/不可逆」混用。

### A.0.1 角色映射（05 canonical，Owner 裁决 2026-08-15 修订）

> **本批涉财角色改为 05 canonical 分工**（`【IR修复】` P1-5，Owner 二次裁决 2026-08-15）：不再把财务裁决职责压给 ADMIN_SECURITY（05 定义 ADMIN_SECURITY 仅管角色/权限/安全配置，不可接触资产或业务数据）。财务裁决/审批映射到 05 canonical 的 **RISK_APPROVER**（批准风险处置）；对账/差异发现映射到 **FINANCE_REVIEWER**（读 Ledger/对账，不可写）。发起方为运营（OPS_OPERATOR）或系统。

| 05 canonical 角色 | 职责（05 §8） | 本批承担 |
|---|---|---|
| OPS_OPERATOR | 运营操作 | 争议发起、冲正发起、结算异常确认（参与方之一） |
| FINANCE_REVIEWER | 读 Ledger / 对账（不可写） | 对账差异发现、提交 RiskCase（`risk_type=LEDGER_RECONCILIATION_DISPUTE`，**不直接改 `apt_ledger_entries.state`**，`【IR修复】` IR 638 P1-3 / IR 659 P2-1） |
| RISK_APPROVER | 批准风险处置 | 争议裁决、冲正审批、结算异常确认、OTC 争议处置、纠错审批 |
| ADMIN_SECURITY | 管理角色/权限/安全配置（不可接触资产） | **不承担财务裁决**（保持 05 canonical 语义） |

> **角色与状态写入分离（`【IR修复】` IR 638 P1-3）**：FINANCE_REVIEWER 是只读角色，**不得作为任何 `apt_ledger_entries` 状态转移的直接执行者**。L4/L5 的 `state` 写入由该实体的 **Authoritative Writer（Ledger Service）/ 系统**在合法工作流条件满足后执行（OPS_OPERATOR 发起争议处置；FINANCE_REVIEWER 仅提交对账差异 RiskCase，不改 state）。审批角色（RISK_APPROVER）批准 ≠ 执行；`approval actor != execution authority`。
> **争议案件载体（`【IR修复】` IR 659 P2-1 + IR 679 P2-1）**：本项目已冻结的领域对象中**不存在 `DisputeCase` 实体**。争议/对账差异一律复用 **`RiskCase`**（`risk_type = LEDGER_RECONCILIATION_DISPUTE`），**不得自行发明新实体**。**RiskCase 冻结状态**：`RiskCase object schema = DEFINED`（05 §3 定义 `case_id`/`user_id`/`risk_type`/`severity`/`status` 等字段）；`RiskCase state/type/DDL machine contract = CONTRACT_GAP`（05 §4 统一状态机未冻结 RiskCase canonical state）；`TARGET_BATCH = 2B-2`。`risk_type = LEDGER_RECONCILIATION_DISPUTE` 为本次新增候选类型值，`STATUS = CANDIDATE / PENDING_2B2_FREEZE`，**在 RiskCase type catalog 冻结前不得用于执行**（`UNFROZEN_RISK_TYPE_EXECUTION = 0`）。**L4/L5 dependency gate = RISK_CASE_CONTRACT_FROZEN**：RiskCase 2B-2 未冻结 → L4/L5 相关流转 FAIL_CLOSED。

> **⚠️ 职责分离提醒（诚实边界，非阻碍）**：本项目 11 角色由 OWNER 单人兼任（manifest `p1_004_owner_freeze`）。系统层面 `OPS_OPERATOR(发起) ≠ RISK_APPROVER(审批)` 的角色分离仍成立；但若同一自然人同时持有两角色并自审自批，须满足 `p1_010_override_contract`（非紧急 SELF_APPROVAL=FORBIDDEN；紧急单人需 MFA + 事后 48h 审计）。此约束不影响本契约冻结，但执行时须遵守。

### A.1 Ledger Entry — `pending / posted / reversed / disputed`

> 05:746 `【已确认】`；MC1 §3.6 明确「本文件不授权任何具体 state transition」。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| L1 | `pending` → `posted` | 日记账批次原子过账 | 系统 / OPS_OPERATOR | 批次内分录借贷平衡；账户余额校验通过 | 更新 `apt_accounts` 余额；追加审计事件 | NO（`posted↛pending`） |
| L2 | `pending` → `reversed` | 入账前取消（未过账冲正） | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 分录尚未 posted | **仅取消未生效 entry**：改 `state` + 追加审计事件；`ACCOUNT_DELTA=0`、`ECONOMIC_REVERSAL_ENTRY=NO`（`【IR修复】` IR 659 P1-2） | NO |
| L3 | `posted` → `reversed` | 入账后冲正 | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 冲正审批通过 | 追加经济 reversal 分录（`entry_direction=-原`、`quantity=原`、`reversal_of=原`）；反向更新 `apt_accounts` | NO |
| L4 | `pending` → `disputed` | 对账不符/异常标记 | Authoritative Writer / 系统（OPS_OPERATOR 发起；FINANCE_REVIEWER 仅提交 RiskCase） | 对账差异记录（RiskCase） | 冻结（见 A.1.2 Dispute Hold Matrix） | NO（`disputed↛pending`） |
| L5 | `posted` → `disputed` | 入账后发现争议 | Authoritative Writer / 系统（OPS_OPERATOR 发起；FINANCE_REVIEWER 仅提交 RiskCase） | 对账差异记录（RiskCase） | 冻结该笔影响（见 A.1.2 Dispute Hold Matrix） | YES（L6） |
| L6 | `disputed` → `posted` | 仲裁确认有效 | Authoritative Writer（RISK_APPROVER 裁决后执行） | 裁决通过 | 按 origin 入账或保持（见 A.1.2） | YES（L5） |
| L7 | `disputed` → `reversed` | 仲裁判定冲正 | Authoritative Writer（RISK_APPROVER 裁决后执行） | 裁决冲正 | 按 origin 区分：pending-origin 仅取消标记；posted-origin 追加经济 reversal（见 A.1.2） | NO |

- **状态分类（`【IR修复】` P2-1）**：`reversed` = TRUE_TERMINAL（无出边）；`posted` = STABLE_WITH_EXCEPTION_TRANSITIONS（可经 L3/L5 例外离开，不可退回 `pending`）；`pending`/`disputed` = 中间态。
- **禁止**：`posted → pending`（反过账禁止，须走冲正）、任何态的物理删除/覆盖（append-only）。
- **争议冻结实现（Owner 裁决 #3 + 财务硬骨头 1 = 方案 A，`【IR修复】` IR 659 P1-1 精确化）**：`state=disputed` 作为冻结标记，**不改原账数字（stored balance）、不改 `frozen_*` 字段**；冻结语义由 **A.1.2 Dispute Hold Matrix** 按 `origin × entry_direction` 精确给出。**禁止统一「排除 disputed 分录影响」**——该规则对 DEBIT 会错误释放已扣资金（见 P1-1）。
- **L4/L5 dependency gate（`【IR修复】` IR 679 P2-1）**：`L4_DEPENDENCY_GATE = RISK_CASE_CONTRACT_FROZEN`、`L5_DEPENDENCY_GATE = RISK_CASE_CONTRACT_FROZEN`。RiskCase 2B-2 未冻结 → L4/L5 相关流转 FAIL_CLOSED（`UNFROZEN_RISK_TYPE_EXECUTION = 0`）。
- **pending 长驻策略（Owner 裁决 #4）**：允许长驻，不删除不清理；对账任务标记 stale pending 并生成 RiskCase。

#### A.1.1 Ledger Mutation Field Contract（`【IR修复】` P1-4 + IR 638 P1-2 方案 A：受控 metadata mutation）

`apt_ledger_entries` 采用**受控 metadata mutation**。仅以下**三列**允许状态机控制更新（`object_version` 为 `【IR修复】` IR 638 P1-2 方案 A 新增，见下方迁移说明）：

| 字段 | 可变更性 | 规则 |
|---|---|---|
| `state` | **受控可变** | 仅 Authoritative Writer 按 A.1 合法转移更新；任何未授权流转 FAIL_CLOSED |
| `audit_event_id` | **受控可变** | 每次 state 流转同事务回写「最新审计事件指针」 |
| `object_version` | **受控可变** | 每次成功 state 流转 `object_version = old + 1`；乐观锁 CAS（见下） |

其余全部字段**永久 immutable**（一旦 INSERT 永不 UPDATE/DELETE）：`ledger_entry_id`、`account_id`、`asset`、`quantity`、`entry_direction`、`entry_type`、`source_object_type`、`source_object_id`、`journal_batch_id`、`reversal_of`、`idempotency_key`、`rule_version`、`snapshot_id`、`created_time`。

> **`object_version` 迁移（IR 638 P1-2 方案 A，`【IR修复】` IR 659 P2-2 补 Change Request）**：`apt_ledger_entries` 是 MC1 8 核心实体中唯一缺 `object_version` 列的表（MC1 DDL 已冻结，`【已确认】`不得改历史 SQL）。因此新增独立 dated migration `sql/20260815_machine_contract_batch2_ledger_object_version.sql`，并配套正式 **Change Request `CR-20260815-001`**（BASE_FREEZE=MC1，见 `sql/CHANGE_REQUEST_CR-20260815-001.md`）：
> ```sql
> ALTER TABLE `apt_ledger_entries`
>   ADD COLUMN `object_version` int unsigned NOT NULL DEFAULT '0'
>   COMMENT '并发控制版本号(乐观锁，每次状态流转+1)' AFTER `audit_event_id`;
> ```
> 乐观锁执行：`UPDATE apt_ledger_entries SET state=?, audit_event_id=?, object_version=object_version+1 WHERE ledger_entry_id=? AND object_version=? AND state=?`；`affected_rows ≠ 1` 即 `OBJECT_VERSION_CONFLICT`（fail-closed，禁止重试覆盖）。此机制使 Ledger 满足 A.0 通用不变量 2「每个 transfer 必须 object_version 乐观锁」，与其余 7 实体一致。

> 实现约束：状态机更新 `state`/`audit_event_id`/`object_version` 时**不得放宽** STAGE-01 已验证的 append-only 防线（Model/DAO/Builder 三级 fail-closed）。必须走**显式受控 update 路径**（仅白名单三列 + `object_version` 乐观锁 + transition guard），而非通用 `update()`。

#### A.1.2 Ledger Dispute Hold & Accounting Delta Matrix（`【IR修复】` P1-4 + IR 638 P1-1 + IR 659 P1-1/P1-2：方向机械公式 + 四格冻结 + reversal 语义）

`disputed` 合并了 `pending` 与 `posted` 两个来源，冻结语义与仲裁时的账户 delta 因 **origin_state × entry_direction** 而异。origin_state 由**审计事件链**确定：该分录最近一次进入 `disputed` 的审计事件（L4 vs L5，`event_code=LEDGER_ENTRY_DISPUTED`）其 `before` 快照即 origin。

**方向机械公式（IR 638 P1-1，关键）**：`quantity` 恒为正数，真实资金方向由 `entry_direction` 决定（`1=CREDIT 入账`、`-1=DEBIT 出账`）。账户净变动统一用：

```text
signed_delta = quantity × entry_direction
```

**核心冻结模型（IR 659 P1-1，方案 A 精确化：不改 stored balance，用 `dispute_hold` 投影）**：

```text
stored_available     : apt_accounts 账本存储的可用余额（dispute 期间【不变】，不改 frozen_* 字段）
dispute_hold         : 争议冻结投影（projection，不落 stored 字段）＝ 争议期间需额外冻结、不可支取的金额
effective_available  : = stored_available - dispute_hold（业务层支取/可用判断以此为唯一依据）
```

> **禁止统一规则「state=disputed ⇒ 排除该分录影响」**：该规则对 DEBIT 是灾难性的——posted DEBIT 已扣款，若「排除」等于把已扣金额重新释放给用户；pending DEBIT 从未扣款，若「不改 frozen_* 且不预留」等于这钱从未被占用。必须按下表四格机械处理。

**A. 进入 `disputed` 时的 Dispute Hold（四格 Hold Matrix）**：

| origin | entry_direction | 进入 dispute 前的余额事实 | `dispute_hold_delta` | `stored_balance_delta` | 语义 |
|---|---|---|---|---|---|
| pending | CREDIT (+1) | 未入账（available 未增） | `0` | `0` | 本来就不可用，无需额外冻结 |
| pending | DEBIT (-1) | 未扣账（available 未减） | `+quantity` | `0` | **预留冻结**，防止用户消费后无法扣款 |
| posted | CREDIT (+1) | 已入账（available 已增） | `+quantity` | `0` | **冻结已入账部分**，不可支取 |
| posted | DEBIT (-1) | 已扣账（available 已减） | `0` | `0` | 已扣款，保持扣款，**绝不恢复 available** |

**B. L6 `disputed → posted`（仲裁确认有效）的账户 delta 与 hold 释放**：

| origin | entry_direction | `stored_balance_delta` | `dispute_hold_release` | 语义 |
|---|---|---|---|---|
| pending | CREDIT (+1) | `+quantity`（入账） | `0` | 现入账 |
| pending | DEBIT (-1) | `-quantity`（扣账） | `-quantity` | 释放预留后扣账 |
| posted | CREDIT (+1) | `0`（已入账） | `-quantity` | 释放冻结 |
| posted | DEBIT (-1) | `0`（已扣账） | `0` | 保持已扣款 |

**C. L7 `disputed → reversed`（仲裁判定冲正）的账户 delta、hold 释放与 reversal 分录**：

| origin | entry_direction | `stored_balance_delta` | `dispute_hold_release` | `ECONOMIC_REVERSAL_ENTRY` | 语义 |
|---|---|---|---|---|---|
| pending | CREDIT (+1) | `0` | `0` | **NO**（仅 AUDIT_EVENT） | 取消未生效 entry |
| pending | DEBIT (-1) | `0` | `-quantity` | **NO**（仅 AUDIT_EVENT） | 取消未生效 entry，释放预留 |
| posted | CREDIT (+1) | `-quantity`（冲正） | `-quantity` | **YES** | 冲正 CREDIT，扣回余额 |
| posted | DEBIT (-1) | `+quantity`（冲正恢复） | `0` | **YES** | 冲正 DEBIT，恢复余额 |

**pending vs posted reversal 语义（IR 659 P1-2，消除 phantom credit/debit）**：

- **未过账取消**（`pending → reversed` L2、`pending-origin disputed → reversed` L7）：`ACCOUNT_DELTA = 0`、`ECONOMIC_REVERSAL_ENTRY = NO`、`AUDIT_EVENT = YES`。仅改 `state` + 写审计事件，**不追加任何经济 reversal 分录**（否则 pending CREDIT 凭空产生 DEBIT、pending DEBIT 凭空产生 CREDIT）。
- **已发生经济效果的冲正**（`posted → reversed` L3、`posted-origin disputed → reversed` L7）：`ECONOMIC_REVERSAL_ENTRY = YES`，追加 reversal 分录，字段约束：`entry_direction = -(原 entry_direction)`、`quantity = 原 quantity`、`reversal_of = 原 ledger_entry_id`、`entry_type = LEDGER_REVERSAL`，使 `reversal.signed_delta = -original.signed_delta`（DEBIT 冲正恢复余额、CREDIT 冲正扣回余额，**杜绝二次扣款**）。

**CREDIT / DEBIT 两套示例（禁止只验证 CREDIT，quantity=100）**：

| origin | entry_direction | signed_delta | 进 dispute：hold | L6 → posted：balance / hold | L7 → reversed：balance / hold / reversal |
|---|---|---|---|---|---|
| pending | CREDIT (+1) | +100 | hold 0 | +100 / 0 | 0 / 0 / NO |
| pending | DEBIT (-1) | -100 | hold +100 | -100 / -100 | 0 / -100 / NO |
| posted | CREDIT (+1) | +100 | hold +100 | 0 / -100 | -100 / -100 / YES |
| posted | DEBIT (-1) | -100 | hold 0 | 0 / 0 | +100 / 0 / YES |

**D. DISPUTE_SHORTFALL_POLICY（`【IR修复】` IR 679 P1-1 + IR 682 P1-1/P1-2，安全默认 = FAIL_CLOSED，PRE_L5）**：

`posted CREDIT` 已被部分消费后再 dispute/reversal，会出现余额不足以支撑冻结或冲正的 shortfall。本批**不自行发明经济政策**（不采用「允许负余额 / 部分冲正 / 自动债务 / 自动吞差额」任一种），采用审核建议的安全默认。

**shortfall 检查阶段（`【IR修复】` IR 682 P1-1，消除「L5 前 / L5 后」歧义）**：

```text
SHORTFALL_CHECK_PHASE = PRE_L5      # shortfall 在 L5 前置守卫内、进入 disputed 之前判定
shortfall = max(0, projected_dispute_hold_after - stored_available)
```

- **L5 前置守卫（PRE_L5）**：在 L5 事务内先计算
  ```text
  projected_dispute_hold_after = aggregate_dispute_hold + quantity   # 本 entry 进 dispute 的 hold
  projected_effective_available = stored_available - projected_dispute_hold_after
  ```
  若 `projected_effective_available < 0`（即 shortfall > 0）：
  ```text
  L5 = DENY
  ledger state remains posted
  balance unchanged / hold unchanged
  append REJECTED audit event（reason_code = SHORTFALL_FAIL_CLOSED）only
  ```
  **禁止 L5 成功进入 disputed 后产生负 effective_available**（`NEGATIVE_EFFECTIVE_AVAILABLE = 0`）。

**dispute_hold 账户级聚合（`【IR修复】` IR 682 P1-2）**：

```text
dispute_hold 是 ACCOUNT-LEVEL AGGREGATE（非单条 entry 独立值）
aggregate_dispute_hold(account_id) = Σ 该账户所有 active disputed/reserved entries 的 hold
effective_available = stored_available - aggregate_dispute_hold
```

**账户级并发控制（`【IR修复】` IR 682 P1-2，基于真实 schema：`apt_accounts.object_version` 已存在，MC1 DDL 第 54 列）**：单条 `apt_ledger_entries.object_version` 只能锁单条分录，**不能阻止同一账户两条不同分录并发 L5 共同超额冻结**。因此冻结 **AptAccount object_version CAS** 作为账户级 reservation 权威锁：

```text
L5（及任何改变 aggregate_dispute_hold 的操作）必须同事务原子完成：
1. CAS apt_accounts.object_version（锁定账户级 reservation authority；affected_rows≠1 → ACCOUNT_LOCK_CONFLICT，重试或拒绝）
2. read stored_available（apt_accounts.balance_apt_i）
3. read aggregate_dispute_hold（Σ active disputed/reserved entries 的 hold）
4. calculate projected_hold
5. calculate projected_effective_available
6. shortfall guard（PRE_L5，projected_effective_available < 0 → DENY）
7. CAS apt_ledger_entries.state（object_version 乐观锁）
8. reservation/hold 生效
9. append audit event
10. commit
任何步骤失败全部 rollback
```

- **不变量**：`ACCOUNT_LEVEL_HOLD_OVERSUBSCRIPTION = FORBIDDEN`（`aggregate_dispute_hold <= available_capacity`、`effective_available >= 0`，除非未来 Owner 另行冻结负余额政策）。
- **禁止（不得自行实现）**：允许负 `stored_balance` / 负 `effective_available`；部分冲正；自动债务（debt/liability）；自动吞掉差额；后续 CREDIT/Reward 自动抵扣 deficit。
- **未定义（deferred 至 2B-2 RiskCase 冻结 / Owner 最终经济裁决）**：shortfall 被 DENY 后账户后续风险处置（是否生成 RiskCase、账户是否 restricted、OTC/Withdrawal/Robot Start 是否禁止、是否需要 ApprovalRequest）。**这些维度在 2B-2 冻结前一律不执行**（`SHORTFALL_UNDECIDED_EXECUTION = 0`）。
- **审计**：FAIL_CLOSED/DENY 的拒绝尝试写 `outcome=REJECTED`、`reason_code=SHORTFALL_FAIL_CLOSED` 审计事件（不改任何余额/分录）。
- **机械断言**：`POSTED_CREDIT_SHORTFALL_POLICY = DETERMINISTIC`；`SHORTFALL_UNDECIDED_EXECUTION = 0`；`ACCOUNT_LEVEL_HOLD_OVERSUBSCRIPTION = 0`；`CONCURRENT_L5_CAPACITY_GUARD = PASS`；`NEGATIVE_EFFECTIVE_AVAILABLE = 0`。

**机械字段全集（实现/测试必须逐一覆盖）**：

```text
stored_balance_delta
dispute_hold_delta
effective_available_delta
L6_balance_delta
L6_hold_release
L7_balance_delta
L7_hold_release
shortfall                          # = max(0, projected_dispute_hold_after - stored_available)，PRE_L5 >0 即 DENY
aggregate_dispute_hold             # 账户级聚合，Σ active disputed/reserved entries 的 hold
projected_effective_available      # = stored_available - projected_dispute_hold_after（PRE_L5 守卫）
```

**验收断言（IR 659 STEP 1/2/9）**：

```text
POSTED_DEBIT_DISPUTE_AVAILABLE_INCREASE = 0      # posted DEBIT 进 dispute 后 effective_available 不得增加
PENDING_DEBIT_DISPUTE_RESERVATION = PASS         # pending DEBIT 进 dispute 后必须产生 reservation/hold
PENDING_REVERSAL_ECONOMIC_ENTRY_COUNT = 0        # pending 取消不得生成经济 reversal 分录
POSTED_REVERSAL_DIRECTION = PASS                 # posted 冲正方向必须 = -original.entry_direction
POSTED_CREDIT_SHORTFALL_POLICY = DETERMINISTIC   # posted CREDIT 被部分消费后再 dispute → shortfall>0 → L5 DENY（PRE_L5）
SHORTFALL_UNDECIDED_EXECUTION = 0                # shortfall 的未定义维度（RiskCase/restricted/Approval）不得执行
ACCOUNT_LEVEL_HOLD_OVERSUBSCRIPTION = 0          # 账户级 dispute_hold 聚合不得超额（aggregate_hold <= available）
CONCURRENT_L5_CAPACITY_GUARD = PASS              # 并发 L5 账户级 CAS 串行，第二条 reservation 必须 CONFLICT/FAIL_CLOSED
NEGATIVE_EFFECTIVE_AVAILABLE = 0                 # 任何 transition 不得产生负 effective_available
```

- **不变量**：dispute 期间 `stored_balance` 恒不变（冻结只通过 `dispute_hold` 投影作用于 `effective_available`）；仲裁后每个 origin×direction 恰好产生一次正确的余额效果与 hold 释放，**不二次入账、不二次冲正、不二次扣款**。

### A.2 Robot — `inactive / active / cooling / review / restricted / paused`

> 05:740 `【已确认】`；语义见 MC1 §2「状态语义要点」。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| R1 | `inactive` → `active` | 用户启动 Robot | END_USER | Power 充足（走 PowerImpactPreview）；资格通过 | 消耗 Power；追加审计事件 | YES（R3） |
| R2 | `active` → `cooling` | 连续运行达上限 | 系统 | 冷却阈值 = 生产参数 TBC（不硬编码） | 停止产出 | YES（R6） |
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

- **状态分类（`【IR修复】` P2-1）**：无 TRUE_TERMINAL（`paused`/`inactive` 均可回到 `active`）；`review`/`cooling`/`restricted` 为中间锁定态。
- **冷却阈值 / review 触发（Owner 裁决 #5）**：冷却阈值 = 生产参数 TBC（矩阵只定义「连续运行超阈值 → cooling」规则，不硬编码值）；review 触发 = 风控引擎标记 TBC。
- **restricted 范围 / `inactive→paused`（Owner 裁决 #6）**：restricted 禁哪些功能由 `allowed_actions` 动态下发（不枚举具体功能）；**`inactive→paused` 不合法**（paused 仅作用于运行态）。

### A.3 AI Reward — `candidate / held / pending_claim / claiming / claimed / expired_returned / review / reversed`

> 05:743 `【已确认】`；语义见 MC1 §2（8 态）。

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

- **状态分类（`【IR修复】` P2-1）**：`expired_returned`、`reversed` = TRUE_TERMINAL（无出边）；`claimed` = STABLE_WITH_EXCEPTION_TRANSITIONS（仅可经 W10 冲正离开，不回退到 held/pending_claim）。
- **`held→expired_returned` 直接路径不合法（Owner 裁决 #8）**：held 必须先经 W2（`held→pending_claim`）进入领取窗口，才能经 W5 过期退回，禁止跳过窗口的状态跳跃。
- **领取窗口时长（Owner 裁决 #7）**：= 生产参数 TBC（不硬编码）；过期退回目标 = 原预算池（`budget_snapshot_id` 指向 pool）；review 触发 = 风控标记 TBC。

### A.4 Market — `draft / open / closing / locked / awaiting_result / settlement / settled / void / exception`

> 05:749 `【已确认】`；语义见 MC1 §2 + 05 §4「Prediction 聚合展示映射」。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| M1 | `draft` → `open` | 发布市场 | OPS_OPERATOR | 参数/策略版本已冻结 | 开放投注 | NO（`open↛draft`；void 走 M8，不回 draft） |
| M2 | `open` → `closing` | 临近锁定 | 系统 | 达到 `lock_at - t` | 促使用户行动 | NO（`closing↛open`） |
| M3 | `closing` → `locked` | 到达锁定时间 | 系统 | 时间到 | 禁止新投注 | NO（`locked↛closing`） |
| M4 | `open` → `locked` | 直接锁定 | OPS_OPERATOR / 系统 | 允许跳过 closing（运营兜底锁定，Owner 裁决） | 禁止新投注 | NO（`locked↛open`） |
| M5 | `locked` → `awaiting_result` | 赛事开始/等待结果 | 系统 | 已锁定 | 等待 Result | NO（`awaiting_result↛locked`） |
| M6 | `awaiting_result` → `settlement` | 结果确认 | 系统 | Result = `official` | 开始结算 | NO（`settlement↛awaiting_result`；异常走 M9） |
| M7 | `settlement` → `settled` | 结算完成 | 系统 | Settlement = `paid` | 订单批量 settled | YES（M12，仅 Result corrected 重开） |
| M8 | `draft`/`open`/`closing`/`locked`/`awaiting_result` → `void` | 赛事取消/作废 | OPS_OPERATOR / 系统 | 四类原因（赛事取消/延期超期/数据不可用/监管，reason_code 承载） | 触发订单退款 | NO |
| M9 | `settlement` → `exception` | 结算异常 | 系统 | 结算失败 | 冻结结算 | YES（M10） |
| M10 | `exception` → `settlement` | 异常处理后重试 | OPS_OPERATOR / 系统 | 恢复条件满足 | 重试结算 | YES（M9） |
| M11 | `exception` → `settled` | 异常处理直接完成 | OPS_OPERATOR + RISK_APPROVER 确认 | 双人确认通过（涉及资金） | 标记完成 | NO（`settled↛exception`） |
| M12 | `settled` → `settlement` | Result corrected 重开结算 | 系统 | 仅一次 | 重开结算；关联 Order 走 correcting | YES（M7） |

- **状态分类（`【IR修复】` P2-1）**：`void` = TRUE_TERMINAL（无出边）；`settled` = STABLE_WITH_EXCEPTION_TRANSITIONS（仅可经 M12 重开）；`locked` = STABLE（可 M5/M8，不可退回 open/closing）。
- **`void` 源状态（`【IR修复】` P1-2）**：`draft`/`open`/`closing`/`locked`/`awaiting_result`（结算前所有状态）均可 void；结算开始后（`settlement`/`settled`/`exception`）不 void，改走 exception/refund 路径。
- **关键区分**（`【已确认】`，05 §4）：`settlement`（处理中）≠ `settled`（已完成）；`void` 原因之一是赛事取消，但非唯一。
- **`exception→settled` 双人确认（Owner 裁决 #10）**：必须运营（OPS_OPERATOR）+ 风险审批人（RISK_APPROVER）确认，不可自动完成；`exception→settlement` 重试可自动。
- **Result corrected 重开结算（Owner 裁决 #11）**：Result corrected 时，已 settled 的 Market 走 `settled → settlement` 重开（M12）；关联 Order 走 `settled → correcting → corrected`；Result corrected 仅允许一次。
- **void 原因清单（Owner 裁决 #9）**：赛事取消 / 赛事延期超期 / 源数据不可用 / 监管要求，用 `reason_code` 承载，不新增状态。

### A.5 Prediction Order — `submitted / locked / awaiting_result / settling / settled / refunding / refunded / correcting / corrected`

> 05:758 `【已确认】`；语义见 MC1 §2（RESULT_UNKNOWN 不混入、correcting 仅 settlement error）。

| # | 从 → 到 | 触发事件 | 触发者 | 前置（guard） | 副作用 | direct_reverse |
|---|---|---|---|---|---|---|
| P1 | `submitted` → `locked` | 市场锁定联动 | 系统 | Market 进入 `locked` | 锁定订单 | NO（`locked↛submitted`） |
| P2 | `locked` → `awaiting_result` | 等待结果 | 系统 | 赛事开始 | 等待 | NO（`awaiting_result↛locked`） |
| P3 | `awaiting_result` → `settling` | 开始结算 | 系统 | Result official + Market settlement | 进入结算 | NO（`settling↛awaiting_result`） |
| P4 | `settling` → `settled` | 结算完成 | 系统 | 结算计算完成 | 更新账户/ledger | NO（`settled↛settling`，纠错走 P7） |
| P5 | `settling` → `refunding` | 结算异常需退款（`【IR修复】` IR 638 P1-4 方案 A） | Authoritative Writer（RefundCase 审批通过后执行） | Market = `exception` + RefundCase approved | 冻结待退 | NO（`refunding↛settling`） |
| P6 | `refunding` → `refunded` | 退款完成 | 系统 | 退款入账 | 更新账户 | NO |
| P7 | `settled` → `correcting` | 结算错误纠错 | OPS_OPERATOR 发起 + RISK_APPROVER 审批 | 仅 settlement error；审批通过 | 冻结 | NO（`correcting↛settled`） |
| P8 | `correcting` → `corrected` | 纠错完成 | RISK_APPROVER 审批 | 纠错审批通过 | 生成 reversal + new ledger | NO |
| P9 | `settling` → `correcting` | 结算中发现错误 | OPS_OPERATOR 发起 | 发现错误 | 冻结纠错 | NO（`correcting↛settling`） |
| P10 | `submitted` → `refunding` | Market void 退款（未锁定） | 系统 | 仅 `Market void` 触发（`【IR修复】` P1-2） | 冻结待退 | NO（`refunding↛submitted`） |
| P11 | `locked` → `refunding` | Market void 退款（已锁定） | 系统 | 仅 `Market void` 触发（`【IR修复】` P1-2） | 冻结待退 | NO（`refunding↛locked`） |
| P12 | `awaiting_result` → `refunding` | Market void 退款（等结果中） | 系统 | 仅 `Market void` 触发（`【IR修复】` P1-2） | 冻结待退 | NO（`refunding↛awaiting_result`） |

- **状态分类（`【IR修复】` P2-1）**：`refunded`、`corrected` = TRUE_TERMINAL（无出边）；`settled` = STABLE_WITH_EXCEPTION_TRANSITIONS（仅可经 P7 纠错）；`locked`/`awaiting_result` = STABLE（可继续结算或经 P11/P12 退款）。
- **void→refund 断路修复（`【IR修复】` P1-2）**：Market void 时，结算前的订单状态（`submitted`/`locked`/`awaiting_result`）进入 `refunding`（P10/P11/P12）；退款范围 = 全额本金；`idempotency_key` 防重。
- **settling→refunding 触发（`【IR修复】` IR 638 P1-4 方案 A）**：结算开始后（`settling`）不再走 `Market void` 退款（与「结算开始后不 void」自洽），改由**结算失败/结算异常（Market 进入 `exception`）+ RefundCase 审批通过**触发 P5。退款范围 = 全额本金；`idempotency_key` 防重；走 `ORDER_REFUND(+本金)` 分录。此路径保证 `settling` 阶段异常可退款，且不引入 `settlement→void` 的矛盾。
- **P5 审批依赖 FAIL_CLOSED（`【IR修复】` IR 659 STEP 5）**：`RefundCase`（`refund_cases`）当前为 `【待确认】`、**未冻结**（归属 2B-2/2B-1，见 Part C）。其 Authoritative Writer、状态机、DDL、`refund_scope`（全额本金）/`fee_treatment`/`idempotency_key` 字段契约**冻结前，P5 = FAIL_CLOSED**（`REFUND_CASE_CONTRACT_FROZEN = REQUIRED`）。同理 P10/P11/P12（Market void 退款）也依赖 RefundCase，未冻结前同样 FAIL_CLOSED。
- `【已确认】`：`RESULT_UNKNOWN` 不混入订单状态；`correcting/corrected` 仅在 settlement error 触发。
- **`corrected` 不回 `settled`（Owner 裁决 #12）**：`corrected` 为终态；重新结算产生新 Order/新分录（用 CorrectionCase 追踪），不改旧订单状态。

### A.6 OTC Order — `draft / review / matching / partial / completed / cancelled / expired / rejected / disputed`

> 05:761 `【已确认】`；完整「运营/用户展示映射」见 05 §4（含可执行操作 + 下一步）。

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

- **状态分类（`【IR修复】` P2-1）**：`cancelled`、`expired`、`rejected` = TRUE_TERMINAL（无出边）；`completed` = STABLE_WITH_EXCEPTION_TRANSITIONS（可经 O11 争议）；`disputed` = 中间态（仅可经 O12 处置）。
- `【已确认】`（05 §4）：`cancelled`=主动取消，`expired`=自然到期（非取消）；`partial+cancelled/expired` 只释放 remaining；`disputed` 保持冻结直到处置；不删除/覆盖历史 Trade/Ledger/Power Ledger。
- **review_required 触发条件（Owner 裁决 #13）**：大额卖出、单人高频异常需人工确认；有效期 = 生产参数 TBC。
- **争议处置（Owner 裁决 #14）**：RISK_APPROVER 判成 `cancelled`（退钱）或 `completed`（维持成交）二选一，**不允许回到 `partial`** 中间态。

### A.7 跨实体协同

以下跨实体联动均已由 Owner 裁决确认（其中 void→refund 路径经 `【IR修复】` P1-2 补全）：

| 联动 | 推断依据 | 状态 |
|---|---|---|
| Market `void` → Prediction Order `submitted`/`locked`/`awaiting_result` → `refunding` → `refunded`（P10/P11/P12） | 05 §4「已作废/已取消 → Market void + reason_code」 | ✅ Owner 裁决（05 语义自然推论 + `【IR修复】` P1-2） |
| Market `exception` → Prediction Order `settling` → `refunding` → `refunded`（P5，RefundCase 审批） | 05 §3 RefundCase + `【IR修复】` IR 638 P1-4 方案 A | ✅ Owner 裁决（结算异常退款路径，替代原「settling 走 void」） |
| Result `corrected` → Market `settled → settlement` 重开 → Order `correcting → corrected` | 05 §3 Result + CorrectionCase 对象 | ✅ Owner #11（仅一次） |
| AI Reward `held` → Ledger `pending → posted` | 05 §3 AIReward.ledger_entry_id + Ledger 四账 | ✅ Owner 裁决（05 语义自然推论） |
| OTC `completed` → Ledger 分录 → Power 释放 | 05 §3 OtcTrade.ledger_entry_ids + power_consumed | ✅ Owner 裁决（05 语义自然推论） |

---

## Part B — Event Catalog

### B.0 总则

- 05 §4 **未定义事件码**；`apt_ledger_entries.entry_type`（varchar(64)）与 `entry_direction`（tinyint）「与 Event Catalog 对齐后冻结」（MC1 §4）。
- 事件码命名 `ENTITY_ACTION` 大写下划线风格，**Owner 已采纳（#15）**。
- **全集边界（`【IR修复】` P1-1）**：覆盖 8 核心实体 A.1–A.6 的**每一个 transition ID**。一个 event_code 可映射多个 transition，但必须显式列出全部 transition ID。
- **规则**：每个业务事件可能 (a) 触发一个状态转移，(b) 生成 ledger entry（含 entry_type/entry_direction），(c) 产生 Power 影响，(d) 要求审计事件。四者相互独立，需分别声明。
- **一致性检查（冻结前 gate，`【IR修复】` P1-1）**：`MISSING_EVENT_FOR_TRANSITION = 0`；`UNKNOWN_TRANSITION_REFERENCE = 0`；`DUPLICATE_AMBIGUOUS_MAPPING = 0`。

### B.1 事件目录表（完整 1:1 / 显式多对一）

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
| ORDER_SETTLED | prediction_orders | P4 | ORDER_SETTLEMENT | 见 B.3 结算会计矩阵 | — | 是 |
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

### B.2 已确认 / 待确认（Event Catalog）

- **事件码命名与全集（Owner 裁决 #15）**：采用 B.1 表，命名 `ENTITY_ACTION`，覆盖 8 核心实体 A.1–A.6 全部 transition ID。
- **`entry_direction` 语义（Owner 裁决 #16）**：`1=CREDIT(入账)`、`-1=DEBIT(出账)`，与 DDL 注释一致。
- **审计事件表 schema（Owner 裁决 #18）**：见 Part E `audit_events` 表 DDL 草案（字段对齐 05 §3 AuditLog）。
- **审计表细节（`【IR修复】` P1-6）**：① `audit_events` 与 `sys_operation_logs` **不合并**；② `before/after_snapshot_id` 采用 **snapshot_type + snapshot_id typed reference**（见 Part E），`parameter_snapshots` 仅作为其中一种 type，不再滥用为通用业务对象快照。

### B.3 结算会计矩阵（`【IR修复】` P1-3，ORDER_SETTLED 消歧）

`ORDER_SETTLED` 的 ledger 效果不再使用「1/-1 视结果」这种不可执行定义，改为按结果显式声明（对齐 Owner #17 / 财务 2）：

| Result | Settlement ledger effect | entry_direction |
|---|---|---|
| WIN | CREDIT = principal + profit | 1 (CREDIT) |
| LOSS | NO_LEDGER_ENTRY（stake 已由 ORDER_STAKE DEBIT 扣减，不追加） | — |
| PUSH | CREDIT = principal（本金退回） | 1 (CREDIT) |

> **不变量**：任一订单的净 ledger 效果仅发生一次且方向确定——下注 `ORDER_STAKE(-本金)`；赢 `+本金+盈利`；输 `不追加`；走盘 `+本金`；void 走 `ORDER_REFUND(+本金)` 而非 `ORDER_SETTLED`。**不存在二次扣款**。

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

### D.0 角色裁决（05 canonical，IR 629 P1-5 修订）

- **财务裁决/审批 = 05 canonical 角色分工**：争议裁决、冲正审批、结算异常确认、OTC 争议处置、纠错审批 → **RISK_APPROVER**（批准风险处置）；对账差异发现、发起争议 → **FINANCE_REVIEWER**（读 Ledger/对账，不可写）；发起方 = OPS_OPERATOR（运营）或系统。
- **ADMIN_SECURITY 不承担财务裁决**（05 定义其仅管角色/权限/安全配置，不可接触资产）。
- ⚠️ 单人项目下角色分离见 Part A.0.1 提醒（`p1_010_override_contract` 兜底）。

### D.1 逐项裁决

| # | 裁决 | 落点 |
|---|---|---|
| 1 | 争议由**运营发起**，**RISK_APPROVER 裁决**；用户不能自行发起 | A.1 L4/L5/L6/L7 |
| 2 | 冲正由**运营发起**，**RISK_APPROVER 审批** | A.1 L2/L3 |
| 3 | 争议期间**钱要冻住**（方案 A：不改原账数字，`state=disputed` 标记 + `dispute_hold` 投影四格冻结） | A.1 争议冻结实现 |
| 4 | pending 长驻，不删不清理，stale 报 RiskCase | A.1 pending 长驻策略 |
| 5 | 冷却阈值/review 触发 = 生产参数 TBC，只定义规则 | A.2 |
| 6 | restricted 由 allowed_actions 下发；`inactive→paused` 不合法 | A.2 |
| 7 | 领取窗口时长 TBC；退回原预算池；review 触发 TBC | A.3 |
| 8 | `held→expired_returned` 直接路径不合法 | A.3 |
| 9 | void 四类原因（赛事取消/延期超期/数据不可用/监管），reason_code 承载 | A.4 |
| 10 | `exception→settled` 必须**运营 + RISK_APPROVER**确认；`exception→settlement` 可自动 | A.4 |
| 11 | Result corrected 重开 Market 结算（`settled→settlement`），仅一次 | A.4 M12 |
| 12 | `corrected` 为终态，不回 settled，重新结算走新对象 | A.5 |
| 13 | 大额卖出、单人高频异常需人工确认；有效期 TBC | A.6 |
| 14 | OTC 争议：RISK_APPROVER 判 `cancelled`（退钱）或 `completed`（维持成交），不回 partial | A.6 O12 |
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
| 财务 1 | **方案 A**：争议期间不改原账数字，`state=disputed` 标记 + `dispute_hold` 投影四格冻结（已并入 #3） |
| 财务 2 | **投注结算会计**：下注时先扣钱（出账 DEBIT）；赢了「本金+盈利」入账（CREDIT）；输了不额外记；走盘退本金（CREDIT） |

---

## Part E — audit_events 表 DDL（冻结候选，已落盘 `sql/`）

> **CANDIDATE（冻结候选，已落盘日期命名文件 `sql/20260815_machine_contract_batch2_audit_events.sql`，未 FROZEN）**。字段对齐 05 §3 AuditLog 对象 + MC1 §3.6 审计不变量（append-only、`ledger_entry_id` 关联、顺序可重建）。
> 落盘方式与 MC1 一致（先落日期命名 DDL，再 Owner Signoff + Independent Review，最后置 FROZEN）；冻结前可修改（同日期文件内改，或按变更控制新增日期文件）。

```sql
-- =============================================================================
-- audit_events — 审计事件表（append-only，Machine Contract 第二批冻结候选）
-- 状态：CANDIDATE（已落盘 sql/ 日期文件，未 FROZEN）
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
  `before_snapshot_type` varchar(64) NOT NULL DEFAULT '' COMMENT '变更前快照类型(typed reference，如 parameter_snapshots)',
  `before_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '变更前快照ID(在 before_snapshot_type 命名空间内)',
  `after_snapshot_type` varchar(64) NOT NULL DEFAULT '' COMMENT '变更后快照类型(typed reference)',
  `after_snapshot_id` bigint unsigned NOT NULL DEFAULT '0' COMMENT '变更后快照ID(在 after_snapshot_type 命名空间内)',
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

**快照引用（`【IR修复】` P1-6，typed reference）**：`before/after_snapshot` 采用 **`snapshot_type` + `snapshot_id` 类型化引用**。`snapshot_type` 指明快照归属（如 `parameter_snapshots` = 参数快照；`ledger_entry`/`robot`/`market`/`order`/`otc_order` = 目标对象状态快照）；`snapshot_type=''` 表示无显式快照，此时 before/after 状态由目标对象当前值 + 审计事件链重建。**`parameter_snapshots` 不再被滥用为通用业务对象快照**，仍只负责参数版本回算。

---

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md` — §2.2 Session 状态、§3 对象字段、§4 统一状态机 + 展示映射、§6 API、§11 RBAC。
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_BATCH1_CANONICAL_STATE_FREEZE.md` — §2 状态语义要点、§3.6 账本 append-only、§4 未冻结项（CONTRACT GAP）。
- `0.5代码/gainode后端/gainode/sql/20260813_machine_contract_batch1_8_core_entities.sql` — 8 核心实体 DDL（entry_type/entry_direction/audit_event_id 字段；`apt_ledger_entries` 无 object_version）。
- `0.5代码/gainode后端/gainode/sql/20260815_machine_contract_batch2_ledger_object_version.sql` — 新增 dated migration：给 `apt_ledger_entries` 补 `object_version`（IR 638 P1-2 方案 A）。
- `0.5代码/gainode后端/gainode/sql/CHANGE_REQUEST_CR-20260815-001.md` — object_version 加列的正式 Change Request（IR 659 P2-2）。
- `0.5代码/gainode后端/gainode/library/{model,service}/**` — STAGE-01 骨架（状态常量、FAIL_CLOSED、@authoritative_writer 约定）。
- `.project-ai/manifest.yaml` — `p1_003_two_phase_freeze`（两批冻结决策）。
- `.project-ai/context.md` — API Freeze 推迟至 STAGE-02。

## 已确认信息 / Owner 裁决 / IR 修复 / 待确认事项

- **已确认信息**：8 核心实体状态枚举（05 §4 canonical，MC1 已冻结）；账本 append-only 语义（MC1 §3.6）；`entry_type`/`entry_direction`/`audit_event_id` 字段存在且待冻结（MC1 DDL + §4）；状态转移矩阵是 CONTRACT GAP（MC1 §3.6）。
- **Owner 裁决（2026-08-15）**：Part D 的 22 项 + 2 项财务硬骨头全部已裁决；角色映射采用 05 canonical 分工（RISK_APPROVER/FINANCE_REVIEWER/OPS_OPERATOR，ADMIN_SECURITY 不涉财）；转移矩阵、Event Catalog、非核心实体清单、`audit_events` DDL 均已收敛。
- **IR 修复（IR 629，2026-08-15）**：P1-1 Event Catalog 补全 + 删 W6；P1-2 void→refund 断路修复；P1-3 ORDER_SETTLED 结算会计矩阵消歧；P1-4 Ledger Mutation Field Contract + Accounting Delta Matrix（方案 A）；P1-5 角色改 05 canonical；P1-6 快照改 typed reference；P2-1 终态三档拆分；P2-2 状态统一 + 落盘表述修正。
- **IR 修复（IR 638，2026-08-15，针对修复后 commit 的复审）**：P1-1 Accounting Delta Matrix 改为 `signed_delta = quantity × entry_direction` 机械公式 + CREDIT/DEBIT 双套示例 + reversal 分录字段（A.1.2）；P1-2 方案 A：Ledger 新增 `object_version` 列（dated migration `20260815_machine_contract_batch2_ledger_object_version.sql`，白名单三列 + CAS 乐观锁，A.1.1/A.0）；P1-3 FINANCE_REVIEWER 只读，L4/L5/L6/L7 写入归 Authoritative Writer/系统（A.0.1/A.1）；P1-4 方案 A：P5 `settling→refunding` 触发改为结算异常（Market=exception）+ RefundCase 审批（A.5/A.7）；P2-1 删除自由文本「可逆性」列，改 `direct_reverse`（YES/NO，A.0 + A.1–A.6）；P2-2 修复项以本文件 + Freeze 文档 + DDL 为权威验证源（见 acceptance IR 638 核查表）。
- **IR 修复（IR 659，2026-08-15，三审）**：P1-1 删除统一「排除 disputed 分录影响」，改为四格 Dispute Hold Matrix（`origin × entry_direction`，`stored_balance` 不变 + `dispute_hold` 投影 + `effective_available`，posted DEBIT 保持扣款、pending DEBIT 预留冻结，A.1.2/Freeze §3.1）；P1-2 统一 pending reversal 语义（`pending→reversed`/`pending-origin disputed→reversed` = `ACCOUNT_DELTA=0` + `ECONOMIC_REVERSAL_ENTRY=NO`，仅 posted 冲正才追加 reversal，A.1/A.1.2/Freeze §3.1）；P2-1 删除未冻结 `DisputeCase`，统一为 `RiskCase`（`risk_type=LEDGER_RECONCILIATION_DISPUTE`，A.0.1/A.1/Freeze §2）；P2-2 为 `object_version` 补 Change Request `CR-20260815-001`（A.1.1/Freeze §3.1/§7/§8）；P2-3 内嵌 Dispute Hold/Reversal 验收断言 + 聚焦本 Commit 证据（acceptance IR 659 核查表）；另按 STEP 5 补 P5 RefundCase 未冻结 FAIL_CLOSED、STEP 6 补涉财 transition 绑定 ApprovalRequest（A.0/A.5）。
- **IR 修复（IR 679，2026-08-15，四审）**：P1-1 新增 `DISPUTE_SHORTFALL_POLICY`（posted CREDIT 已被部分消费后再 dispute/reversal 的 shortfall 边界：`shortfall = max(0, dispute_hold - stored_available)`，`shortfall > 0 → FAIL_CLOSED` 安全默认，禁止负余额/部分冲正/自动债务，未定义维度 deferred 至 2B-2，A.1.2/Freeze §3.1）；P2-1 统一 RiskCase 冻结状态（`object schema = DEFINED` + `machine contract = CONTRACT_GAP` + `TARGET_BATCH = 2B-2`），`risk_type=LEDGER_RECONCILIATION_DISPUTE` 标 `CANDIDATE/PENDING_2B2_FREEZE`，新增 L4/L5 dependency gate = RISK_CASE_CONTRACT_FROZEN（A.0.1/A.1/Freeze §2/§3.1）；P2-2 提升 AI Code Review Assistant `max_diff_chars`（25000→100000，工具配置在仓库外，需审核助手重启后生效），并新增机械断言 `POSTED_CREDIT_SHORTFALL_POLICY=DETERMINISTIC`/`SHORTFALL_UNDECIDED_EXECUTION=0`（acceptance IR 679 核查表）。
- **IR 修复（IR 682，2026-08-15，五审）**：P1-1 冻结 `SHORTFALL_CHECK_PHASE = PRE_L5`（shortfall 在 L5 前置守卫内、进入 disputed 之前判定，`projected_effective_available < 0 → L5 = DENY`，state 保持 posted，禁止 L5 成功后产生负 effective_available，A.1.2/Freeze §3.1）；P1-2 冻结 `dispute_hold` 为 **ACCOUNT-LEVEL AGGREGATE**（`aggregate_dispute_hold = Σ active disputed/reserved entries 的 hold`），并冻结账户级并发控制 = **AptAccount object_version CAS**（基于真实 schema，MC1 DDL 第 54 列；L5 十步同事务原子，`ACCOUNT_LEVEL_HOLD_OVERSUBSCRIPTION = FORBIDDEN`，A.1.2/Freeze §3.1）；P2-1 证据验收改 `REVIEW_PACKAGE_TRUNCATED = NO`（不再以「调大 max_diff_chars」为验收条件，acceptance IR 682 核查表）。
- **待确认事项（不阻塞契约收敛，冻结后由 06 处理）**：① 生产参数数值（冷却阈值、领取窗口、OTC 有效期等，06 TBC）；② 单人项目下 OPS_OPERATOR↔RISK_APPROVER 职责分离的落地（`p1_010_override_contract` 兜底，执行时遵守）。
