# Design: S01-P07 Affiliate/Agent P1 合同盘点（快照 1）

## 状态

- **本快照**：合同盘点（不建表）。Owner 未签 → 三对象 CONTRACT_GAP/FAIL_CLOSED。
- **冻结状态**：三对象合同均未冻结；V1.x `member_user_team` 为只读盘点对象，不继承旧佣金语义。

## 1. 源材料提取（步骤 1）

| 源 | 提取内容 | 结论 |
|---|---|---|
| 01 §3 P1 | Referral/Team 深度运营，先留结构不阻塞 P0 | 三对象保留，P0 奖励关闭 |
| 02 §12 | 邀请关系可共用；AI/Prediction 奖励分开；禁用户本金/退款付奖励；Candidate/HELD≠已支付 | 预算来源硬约束 |
| 02 §13/§14 | 收入确认；经济写操作共同要求（object_id/request_id/idempotency_key/rule_version/snapshot_id/ledger ref/status） | 字段基线 |
| 06 §9 | prediction_first_gen_rate/second_gen_rate/team_pool*；P0 可建 Definition 不开正式功能 | 佣金参数 TBC |
| V1.x member_user_team | 扁平表：invite_code/parent_id/parent_level/parent_path/invite_income_money/team_income_money/reward 等 | 只读盘点，不继承佣金语义 |

**V1.x 扁平表 → V6.1 三对象映射原则**：V1.x 把「关系」「团队统计」「佣金金额」混在一张表；V6.1 拆为 Agent（身份+层级）/ Referral（关系）/ AgentEarning（收益，append-only）。旧佣金字段（invite_income_money/team_income_money/reward）不迁移、不自动继承。

## 2. 字段候选表（步骤 2）

标记：`SOURCE_CONFIRMED`（源文档/工程约束确认）；`OWNER_DECISION_REQUIRED`（需 Owner 裁决）。

### 2.1 Agent（代理商）

| 字段 | 类型 | 来源 | 标记 |
|---|---|---|---|
| agent_id | Snowflake bigint（主键） | 工程约束 | SOURCE_CONFIRMED |
| user_id | bigint（关联 user，一用户一 Agent） | 02 + V1.x user_id | SOURCE_CONFIRMED |
| agent_code | varchar（邀请码） | V1.x invite_code | SOURCE_CONFIRMED |
| status | enum（Agent 状态） | 02（未列枚举） | OWNER_DECISION_REQUIRED |
| parent_id | bigint（上级 Agent） | V1.x parent_id | SOURCE_CONFIRMED（字段）；语义见 Matrix |
| level | int（层级深度） | V1.x parent_level | OWNER_DECISION_REQUIRED（最大深度） |
| parent_path | varchar（层级路径） | V1.x parent_path | OWNER_DECISION_REQUIRED（是否保留路径） |
| rule_version | varchar | 02 §14 | SOURCE_CONFIRMED |
| object_version | int unsigned（乐观锁） | 工程约束 | SOURCE_CONFIRMED |
| created_time / updated_time | int（Unix） | 02 §14 | SOURCE_CONFIRMED |

### 2.2 Referral（邀请关系）

| 字段 | 类型 | 来源 | 标记 |
|---|---|---|---|
| referral_id | Snowflake bigint（主键） | 工程约束 | SOURCE_CONFIRMED |
| inviter_id | bigint（邀请人/上级） | V1.x parent_id | SOURCE_CONFIRMED |
| invitee_id | bigint（被邀请人/下级） | V1.x user_id | SOURCE_CONFIRMED |
| invite_code | varchar | V1.x invite_code | SOURCE_CONFIRMED |
| status | enum（关系状态） | 02（未列枚举） | OWNER_DECISION_REQUIRED |
| rule_version | varchar | 02 §14 | SOURCE_CONFIRMED |
| idempotency_key | varchar（幂等，防重复绑定） | 02 §14 | SOURCE_CONFIRMED |
| created_time / updated_time | int（Unix） | 02 §14 | SOURCE_CONFIRMED |

### 2.3 AgentEarning（代理商收益，append-only）

| 字段 | 类型 | 来源 | 标记 |
|---|---|---|---|
| earning_id | Snowflake bigint（主键） | 工程约束 | SOURCE_CONFIRMED |
| agent_id | bigint | 07 关联 Agent | SOURCE_CONFIRMED |
| source_object_type | varchar（来源对象类型） | 02 §14 | SOURCE_CONFIRMED |
| source_object_id | varchar | 02 §14 | SOURCE_CONFIRMED |
| quantity | varchar decimal string（金额） | 02 §14 + 07 amount string | SOURCE_CONFIRMED |
| status | enum（确认状态） | 02 Candidate/HELD | OWNER_DECISION_REQUIRED |
| reversal_of | varchar（冲正引用） | 02 §14 + 07 reversal | SOURCE_CONFIRMED |
| budget_source | varchar（预算来源） | 02（禁本金/退款） | OWNER_DECISION_REQUIRED |
| rule_version / snapshot_id | varchar | 02 §14 | SOURCE_CONFIRMED |
| idempotency_key | varchar | 02 §14 | SOURCE_CONFIRMED |
| created_time | int（Unix，append-only 无 updated_time） | 02 §14 | SOURCE_CONFIRMED |

## 3. Decision Matrix（步骤 3，全部 OWNER_DECISION_REQUIRED）

| # | 决策点 | 候选/依据 | 默认（fail-closed） | 状态 |
|---|---|---|---|---|
| D1 | Agent 状态枚举 | 未冻结；候选 active/suspended/terminated | 未签 → 不建 status 状态机 | OWNER_DECISION_REQUIRED |
| D2 | Referral 状态枚举 | 未冻结；候选 active/revoked/expired | 未签 → 不建状态机 | OWNER_DECISION_REQUIRED |
| D3 | AgentEarning 状态枚举 | 未冻结；02 提示 Candidate/HELD | 未签 → 不建状态机 | OWNER_DECISION_REQUIRED |
| D4 | 层级最大深度 | 02 有 first_gen/second_gen（暗示 ≤2 代）；team_pool p3~p6 暗示更深 | 未签 → 层级写路径关闭 | OWNER_DECISION_REQUIRED |
| D5 | 重复归属 | 02「邀请关系可以共用」语义模糊（一 invitee 多 inviter？） | 未签 → 禁止重复绑定 | OWNER_DECISION_REQUIRED |
| D6 | 解绑/更换上级 | V1.x 未体现；需明确是否允许 | 未签 → 禁止解绑/更换 | OWNER_DECISION_REQUIRED |
| D7 | earning 确认时点 | 下单时 vs 结算时 vs 平台收入确认（02 §13）时 | 未签 → 不确认（Candidate） | OWNER_DECISION_REQUIRED |
| D8 | 预算来源 | 02 禁用户本金/退款/Prediction 结算；候选 treasury growth budget | 未签 → 预算来源 NULL，禁发放 | OWNER_DECISION_REQUIRED |
| D9 | 回滚/reversal | 02 §14 高风险更正要求 case_id/approval_id | 未签 → reversal 走更正合同 | OWNER_DECISION_REQUIRED |
| D10 | 税务/合规 | 未定义；地区合规需明确 | 未签 → 地区维度 fail-closed | OWNER_DECISION_REQUIRED |
| D11 | PII | Agent/Referral 关联 user，PII 脱敏边界 | 未签 → 越权不泄露存在性 | OWNER_DECISION_REQUIRED |

## 4. 关键不变量（快照 1）

```text
NOT_PERSISTED_YET = YES（三对象 Owner 未签，不建表不建 Service）
P0_DEFAULT_CLOSED = YES（growth 奖励写路径 fail-closed）
NO_LEGACY_COMMISSION_INHERITANCE = YES（V1.x 佣金字段不迁移不继承）
OWNER_DECISION_COUNT = 11（D1~D11）
V1X_KEEP_READONLY = YES（member_user_team 只读盘点，不删不改）
```

## 5. 快照 2 预告（合同 FROZEN 后，本快照不执行）

```text
顺序：Agent → Referral → AgentEarning
AgentEarning append-only + reversal（不允许 update 历史金额）
负向测试：duplicate referral / self referral / cycle / cross-tenant / budget unavailable / repeat earning / reversal / 权限
```
