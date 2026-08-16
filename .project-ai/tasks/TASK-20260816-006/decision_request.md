# Owner Decision Request: S01-P07 Affiliate/Agent P1 合同

> 状态：**OWNER_SIGNED**（2026-08-16，11 项 D1~D11 全部采纳 OPTION_A）
> 起草日期：2026-08-16
> 任务：`.project-ai/tasks/TASK-20260816-006/`
> 关联候选合同：`sql/MACHINE_CONTRACT_AFFILIATE_AGENT_P1_FREEZE.md`
> 裁决后动作：更新 05 §4 / 06 §9 → Freeze Candidate → Independent Review → FROZEN → 快照 2（建 DDL/Model/DAO/Service）
> 默认（未签）：三对象（Agent/Referral/AgentEarning）CONTRACT_GAP/FAIL_CLOSED，不建表；P0 增长奖励写路径 fail-closed；不继承 V1.x 佣金语义。

---

## D1 — Agent 状态枚举

```text
DECISION_ID = AFFILIATE-AGENT-01
DECISION_REQUIRED = Agent.status 的 canonical enum
AFFECTED_OBJECTS = Agent
CURRENT_AUTHORITY = Owner（05 §4 未定义）
OPTION_A = active / suspended / terminated
OPTION_B = active / inactive
RECOMMENDED_OPTION = OPTION_A（需区分「可恢复暂停」与「终态终止」）
RISK_OF_EACH_OPTION = A：三态语义需定义恢复条件；B：无法表达风控暂停
SAFE_WORK_CONTINUING = 是（S01-P08 及后续 P0/P1 不依赖 Agent 状态机）
RESUME_CONDITION = 裁决后补 05 §4
```

## D2 — Referral 状态枚举

```text
DECISION_ID = AFFILIATE-AGENT-02
DECISION_REQUIRED = Referral.status 的 canonical enum
AFFECTED_OBJECTS = Referral
CURRENT_AUTHORITY = Owner（05 §4 未定义）
OPTION_A = active / revoked / expired
OPTION_B = active / revoked
RECOMMENDED_OPTION = OPTION_A（邀请活动可能设有效期）
RISK_OF_EACH_OPTION = A：expired 触发条件需定义；B：无法表达活动到期
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §4
```

## D3 — AgentEarning 状态枚举

```text
DECISION_ID = AFFILIATE-AGENT-03
DECISION_REQUIRED = AgentEarning.status 的 canonical enum
AFFECTED_OBJECTS = AgentEarning
CURRENT_AUTHORITY = Owner（05 §4 未定义；02 §12 提示 Candidate/HELD）
OPTION_A = candidate / held / confirmed / reversed
OPTION_B = pending / confirmed / reversed
RECOMMENDED_OPTION = OPTION_A（对齐 02「Candidate/HELD 不可当成已支付」语义）
RISK_OF_EACH_OPTION = A：held 冻结条件需定义；B：丢失「暂扣」语义
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §4
```

## D4 — 层级最大深度

```text
DECISION_ID = AFFILIATE-AGENT-04
DECISION_REQUIRED = Agent 层级最大深度（直推/二代/更深团队池）
AFFECTED_OBJECTS = Agent / AgentEarning
CURRENT_AUTHORITY = Owner（06 §9 有 first_gen/second_gen + team_pool p3~p6）
OPTION_A = 2 代（直推 + 二代，对齐 first_gen_rate/second_gen_rate）
OPTION_B = N 代（含 team_pool，深度另定）
RECOMMENDED_OPTION = OPTION_A（P1 先冻结 2 代；team_pool 更深层级属 STAGE-02 单独决策）
RISK_OF_EACH_OPTION = A：team_pool 语义暂缓；B：层级过深放大佣金漏洞面
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 06 §9
```

## D5 — 重复归属

```text
DECISION_ID = AFFILIATE-AGENT-05
DECISION_REQUIRED = 一 invitee 是否可有多 inviter（02「邀请关系可以共用」语义澄清）
AFFECTED_OBJECTS = Referral
CURRENT_AUTHORITY = Owner（02 §12 表述模糊）
OPTION_A = 一 invitee 唯一 inviter（严格一对一；邀请码可多人使用但关系唯一）
OPTION_B = 一 invitee 多 inviter（多重归属，按渠道/时间分润）
RECOMMENDED_OPTION = OPTION_A（V1.x parent_id 单一；「共用」最可能指邀请码可复用而非关系可多重）
RISK_OF_EACH_OPTION = A：多来源邀请场景受限；B：分润冲突、重复发放风险高
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后更新 02 §12 措辞
```

## D6 — 解绑 / 更换上级

```text
DECISION_ID = AFFILIATE-AGENT-06
DECISION_REQUIRED = 邀请关系能否解绑 / 更换上级
AFFECTED_OBJECTS = Referral / Agent
CURRENT_AUTHORITY = Owner
OPTION_A = 禁止解绑 / 更换（关系终身固定）
OPTION_B = 允许解绑 / 更换（需审批 + 审计 + 冲突处理）
RECOMMENDED_OPTION = OPTION_A（P1 禁止；更换上级是刷佣高风险操作，需独立合同）
RISK_OF_EACH_OPTION = A：误绑无纠正通道；B：刷佣/双归属风险
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后更新 02 §12
```

## D7 — earning 确认时点

```text
DECISION_ID = AFFILIATE-AGENT-07
DECISION_REQUIRED = AgentEarning 从 candidate → confirmed 的时点
AFFECTED_OBJECTS = AgentEarning
CURRENT_AUTHORITY = Owner（02 §13 收入确认 / §14 Candidate≠已支付）
OPTION_A = 平台收入确认后（关联 Settlement=paid / 收入确认）
OPTION_B = 下单即产生 candidate，结算即 confirmed
RECOMMENDED_OPTION = OPTION_A（严格对齐 02「Candidate/HELD 不可当成已支付」）
RISK_OF_EACH_OPTION = A：确认时点依赖结算完成；B：早确认导致回滚复杂
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后更新 02 §14
```

## D8 — 预算来源

```text
DECISION_ID = AFFILIATE-AGENT-08
DECISION_REQUIRED = 增长奖励的资金来源（02 禁用户本金/退款/Prediction 结算）
AFFECTED_OBJECTS = AgentEarning
CURRENT_AUTHORITY = Owner（02 §12 硬约束，来源未定）
OPTION_A = 独立 growth treasury budget（单独预算池，走 ParameterRelease）
OPTION_B = 复用现有账户/预算
RECOMMENDED_OPTION = OPTION_A（独立预算池，避免污染用户资金账）
RISK_OF_EACH_OPTION = A：需新预算账户模型；B：资金混用违反 02 约束
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 06 §9 / 02 §12
```

## D9 — 回滚 / reversal

```text
DECISION_ID = AFFILIATE-AGENT-09
DECISION_REQUIRED = AgentEarning 回滚机制（02 §14 高风险更正要求 case_id/approval_id）
AFFECTED_OBJECTS = AgentEarning
CURRENT_AUTHORITY = Owner
OPTION_A = append-only reversal（新 reversal 记录 + reversal_of 引用原记录；触发走统一更正合同审批）
OPTION_B = 原地改状态 + 改金额
RECOMMENDED_OPTION = OPTION_A（append-only 不篡改历史；对齐 02 §14 reversal）
RISK_OF_EACH_OPTION = A：需定义 reversal 审批合同；B：破坏 append-only、审计缺失
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后更新 02 §14
```

## D10 — 税务 / 合规

```text
DECISION_ID = AFFILIATE-AGENT-10
DECISION_REQUIRED = 佣金税务 / 地区合规维度
AFFECTED_OBJECTS = Agent / AgentEarning
CURRENT_AUTHORITY = Owner（未定义）
OPTION_A = 预留地区/税号字段，P1 不启用（默认 fail-closed）
OPTION_B = 不做地区合规（全球单一规则）
RECOMMENDED_OPTION = OPTION_A（预留字段，地区维度 fail-closed 防未定义地区发放）
RISK_OF_EACH_OPTION = A：预留字段语义待定；B：合规风险
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §3
```

## D11 — PII

```text
DECISION_ID = AFFILIATE-AGENT-11
DECISION_REQUIRED = Agent/Referral 关联 user 的 PII 暴露边界
AFFECTED_OBJECTS = Agent / Referral
CURRENT_AUTHORITY = Owner（05 §安全）
OPTION_A = 最小化暴露（仅 agent_code/层级，不暴露 email/phone/实名）
OPTION_B = 直接暴露 user 基础信息
RECOMMENDED_OPTION = OPTION_A（最小化 PII；越权不泄露 user 存在性）
RISK_OF_EACH_OPTION = A：功能受限；B：PII 泄露风险
SAFE_WORK_CONTINUING = 是
RESUME_CONDITION = 裁决后补 05 §3/§8
```

---

## 裁决汇总

```text
OWNER_DECISION_COUNT = 11（D1~D11）
OWNER_SIGNOFF_DATE = 2026-08-16
OWNER_SIGNOFF_SCOPE = 全部 11 项采纳 OPTION_A
ALL_OPEN = NO
DEFAULT_FAIL_CLOSED = 解除（三对象可进入快照 2：建 DDL/Model/DAO/Service 骨架）
NO_LEGACY_COMMISSION_INHERITANCE = YES
```

逐项签核结果（2026-08-16，全部 OPTION_A）：

| # | 决策 | 落定值 |
|---|---|---|
| D1 | Agent.status | `active / suspended / terminated` |
| D2 | Referral.status | `active / revoked / expired` |
| D3 | AgentEarning.status | `candidate / held / confirmed / reversed` |
| D4 | 层级最大深度 | 2 代（直推 + 二代；team_pool 更深层级留 STAGE-02） |
| D5 | 归属 | 一 invitee 唯一 inviter（邀请码可复用，关系唯一） |
| D6 | 解绑/更换上级 | 禁止（关系终身固定） |
| D7 | earning 确认时点 | 平台收入确认后（关联 Settlement=paid） |
| D8 | 预算来源 | 独立 growth treasury budget（走 ParameterRelease） |
| D9 | 回滚 | append-only reversal（复用 2B-1 CorrectionCase/RefundCase） |
| D10 | 税务/合规 | 预留地区/税号字段，P1 不启用（fail-closed） |
| D11 | PII | 最小化暴露（仅 agent_code/层级，不暴露 email/phone/实名） |
