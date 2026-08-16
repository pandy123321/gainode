# Machine Contract — Affiliate/Agent P1 合同 Freeze（候选）

> 状态：**CANDIDATE（未 FROZEN）** — Owner Signoff 未签（11 项决策 D1~D11 待裁决）；Independent Review 未开始。
> 说明：本文件为 Affiliate/Agent P1（Agent / Referral / AgentEarning 三对象）的合同冻结候选。Owner 未签前，三对象 **CONTRACT_GAP/FAIL_CLOSED，不建表、不建 Service**；P0 增长奖励写路径 fail-closed；不继承 V1.x 佣金语义。
> 起草日期：2026-08-16
> 关联 DDL：无（本快照为合同盘点，不生成 DDL）
> 权威契约：`Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3 对象字段 / §4 状态机 / §8 RBAC / §11 SoD）、`02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`（§12/§13/§14）、`06_PARAMETER_DICTIONARY.md`（§9）
> 前置冻结：MC1、MC2、2B-1、2B-2
> 任务：`.project-ai/tasks/TASK-20260816-006/`

## 1. 冻结范围

本批冻结 **Affiliate/Agent P1 三对象**的合同候选（字段 + 状态候选）。Owner 签后，三对象的状态流转由本文件授权，非法流转 FAIL_CLOSED。

| 对象 | 类型 | 说明 | 状态 |
|---|---|---|---|
| Agent | 持久领域实体（代理商，一用户一 Agent） | 身份 + 层级 | 候选（enum/字段待 Owner 签） |
| Referral | 持久领域实体（邀请关系） | 邀请码 → 上下级绑定 | 候选（enum/字段待 Owner 签） |
| AgentEarning | 持久领域实体（代理商收益） | append-only + reversal | 候选（enum/字段待 Owner 签） |

**不包含**（拆出本快照，另行交付）：
- 三对象的 DDL / Model / DAO / Service（属快照 2，Owner 签后）。
- 奖励发放写流程（属 STAGE-02）。
- V1.x `member_user_team`（只读盘点对象，不删除、不修改、不迁移佣金字段）。

## 2. 字段合同（候选）

标记：`SOURCE_CONFIRMED`（源文档/工程约束确认）；`OWNER_DECISION_REQUIRED`（待 Owner 裁决）。

### 2.1 Agent

| 字段 | 类型 | 标记 |
|---|---|---|
| agent_id | Snowflake bigint unsigned（主键） | SOURCE_CONFIRMED |
| user_id | bigint unsigned（关联 user，唯一） | SOURCE_CONFIRMED |
| agent_code | varchar（邀请码，唯一） | SOURCE_CONFIRMED |
| status | enum（待 D1 裁决） | OWNER_DECISION_REQUIRED |
| parent_id | bigint unsigned（上级 Agent，可空） | SOURCE_CONFIRMED（字段）；语义见 D5/D6 |
| level | int unsigned（层级深度） | OWNER_DECISION_REQUIRED（D4） |
| parent_path | varchar（层级路径，可空） | OWNER_DECISION_REQUIRED |
| rule_version | varchar | SOURCE_CONFIRMED |
| object_version | int unsigned（乐观锁） | SOURCE_CONFIRMED |
| created_time / updated_time | int unsigned（Unix 秒） | SOURCE_CONFIRMED |

### 2.2 Referral

| 字段 | 类型 | 标记 |
|---|---|---|
| referral_id | Snowflake bigint unsigned（主键） | SOURCE_CONFIRMED |
| inviter_id | bigint unsigned（邀请人/上级） | SOURCE_CONFIRMED |
| invitee_id | bigint unsigned（被邀请人/下级，唯一） | SOURCE_CONFIRMED |
| invite_code | varchar | SOURCE_CONFIRMED |
| status | enum（待 D2 裁决） | OWNER_DECISION_REQUIRED |
| rule_version | varchar | SOURCE_CONFIRMED |
| idempotency_key | varchar(64) UNIQUE（防重复绑定） | SOURCE_CONFIRMED |
| object_version | int unsigned（乐观锁） | SOURCE_CONFIRMED |
| created_time / updated_time | int unsigned（Unix 秒） | SOURCE_CONFIRMED |

### 2.3 AgentEarning

| 字段 | 类型 | 标记 |
|---|---|---|
| earning_id | Snowflake bigint unsigned（主键） | SOURCE_CONFIRMED |
| agent_id | bigint unsigned | SOURCE_CONFIRMED |
| source_object_type | varchar（来源对象类型） | SOURCE_CONFIRMED |
| source_object_id | varchar | SOURCE_CONFIRMED |
| quantity | varchar（decimal string 金额） | SOURCE_CONFIRMED |
| status | enum（待 D3 裁决） | OWNER_DECISION_REQUIRED |
| reversal_of | varchar（冲正引用原 earning_id，可空） | SOURCE_CONFIRMED（D9） |
| budget_source | varchar（待 D8 裁决） | OWNER_DECISION_REQUIRED |
| rule_version / snapshot_id | varchar | SOURCE_CONFIRMED |
| idempotency_key | varchar(64) UNIQUE | SOURCE_CONFIRMED |
| created_time | int unsigned（Unix 秒，append-only 无 updated_time） | SOURCE_CONFIRMED |

## 3. 状态合同（候选，未 FROZEN）

> Owner 未签 → 三对象 status enum 未冻结。以下为候选 enum（对应 decision_request D1/D2/D3 RECOMMENDED_OPTION），**正式 FROZEN 前不生效，状态机全部 FAIL_CLOSED**。

### 3.1 Agent（候选 enum：`active / suspended / terminated`，D1 OPTION_A）

```text
初态 = active
候选合法转移 = active→suspended（风控暂停）/ suspended→active（恢复）/ active|suspended→terminated（终止）
终态 = terminated
触发者 = END_USER（注册成为 Agent）、ADMIN_SECURITY/OPS_OPERATOR（暂停/终止）
Writer = AgentService（Owner 签后）
失败态 = 无
重试 = 无
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = 无
```

### 3.2 Referral（候选 enum：`active / revoked / expired`，D2 OPTION_A）

```text
初态 = active
候选合法转移 = active→revoked（解除/风控）/ active→expired（活动到期）
终态 = revoked / expired
触发者 = END_USER（邀请绑定）、ADMIN_SECURITY（撤销）
Writer = ReferralService（Owner 签后）
失败态 = 无
重试 = 无
幂等 = idempotency_key（防重复绑定）
审计 = append audit_events
账本副作用 = 无
```

### 3.3 AgentEarning（候选 enum：`candidate / held / confirmed / reversed`，D3 OPTION_A）

```text
初态 = candidate
候选合法转移 = candidate→held（暂扣）/ held→confirmed（确认）/ candidate|held→reversed（回滚，新增 reversal 记录）/ confirmed→reversed（回滚）
终态 = confirmed / reversed
触发者 = 系统（结算/收入确认后确认）、RISK_APPROVER（回滚审批）
Writer = AgentEarningService（Owner 签后，append-only + reversal）
失败态 = 无
重试 = 无（reversal 走统一更正合同，不覆盖历史）
幂等 = idempotency_key
审计 = append audit_events
账本副作用 = 依 D8 预算来源（未签 → 禁发放）
```

**FAIL_CLOSED（Owner 未签）**：三对象无合法转移；`status` 未定义前 `varchar(32) NULL` + 不建表、不写业务。

## 4. 跨对象协同（候选，未 FROZEN）

| 规则 | 依据 |
|---|---|
| AgentEarning 确认依赖平台收入确认（D7） | 02 §13/§14 |
| 禁用户本金/退款/Prediction 结算支付增长奖励（D8） | 02 §12 |
| Candidate/HELD 不可当成已支付（D3） | 02 §12 |
| 层级深度 ≤ 2 代（D4 候选）；更深 team_pool 属 STAGE-02 | 06 §9 |
| 一 invitee 唯一 inviter（D5 候选）；禁解绑/更换上级（D6 候选） | 02 §12 |
| reversal 走统一更正合同（D9，复用 2B-1 CorrectionCase/RefundCase） | 02 §14 |
| 地区维度 fail-closed（D10）；PII 最小化（D11） | 05 §安全 |

## 5. 通用工程约束（快照 2 建 DDL/骨架时落实）

| 维度 | 约束 |
|---|---|
| 主键 | Snowflake bigint unsigned（`$incrementing=false`，`$keyType='string'`） |
| 并发 | 每表 `object_version int unsigned` CAS 乐观锁 |
| 幂等 | 每表 `idempotency_key varchar(64) UNIQUE` 可空 |
| 审计 | 敏感写表 `audit_event_id` 指针 + append `audit_events` |
| 金额 | `quantity` 用 decimal string（对齐 07 amount string） |
| 时间 | `created_time/updated_time` int unsigned（Unix 秒）；AgentEarning 仅 `created_time`（append-only） |
| 状态列 | 领域状态用 ENUM（冻结 enum 后），05 未定义前 `varchar(32) NULL` + FAIL_CLOSED |
| 失败安全 | 转移矩阵未冻结一律 FAIL_CLOSED，不建表、不写业务 |

## 6. 冻结状态与 gate

```text
OWNER_SIGNOFF = PENDING（D1~D11 未签）
INDEPENDENT_REVIEW = PENDING
FROZEN_STATUS = CANDIDATE
AGENT_ENUM = active/suspended/terminated（候选，D1）
REFERRAL_ENUM = active/revoked/expired（候选，D2）
AGENT_EARNING_ENUM = candidate/held/confirmed/reversed（候选，D3）
OWNER_DECISION_MATRIX_COUNT = 11（D1~D11）
NO_SELF_INVENTED_STATE = YES（全部候选，未冻结）
NO_SELF_INVENTED_ROLE = YES（复用 05 §8）
CONTRACT_GAP = YES（Owner 未签前不建表不建 Service）
P0_DEFAULT_CLOSED = YES（增长奖励写路径 fail-closed）
NO_LEGACY_COMMISSION_INHERITANCE = YES（V1.x 佣金字段不迁移）
V1X_MEMBER_USER_TEAM_UNTOUCHED = YES（只读盘点）
```

正式 FROZEN 前须：Owner 签 D1~D11 → 更新 05 §4 / 06 §9 / 02 §12/§14 → Freeze Candidate → Independent Review 通过 → FROZEN → 快照 2 建 DDL/Model/DAO/Service。

## 信息来源

- 01 §3 P1、02 §12/§13/§14、06 §9、07 §S01-P07
- 05 §3/§4/§8/§11
- `.project-ai/tasks/TASK-20260816-006/{requirement,design,acceptance,decision_request}.md`
- V1.x `library/{model,dao,service}/member/UserTeam*.php`（只读盘点）
