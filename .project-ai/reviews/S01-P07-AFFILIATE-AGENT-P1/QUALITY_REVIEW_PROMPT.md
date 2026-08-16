# S01-P07 独立审核提示词（Independent Review Agent）

你是 Gainode 项目的独立审核 Agent。请以 **Evidence First（证据优先）** 原则，对本包 S01-P07 Affiliate/Agent P1 合同盘点快照 1 进行只读审核。

## 审核对象

```text
PACKAGE_ID = S01-P07-AFFILIATE-AGENT-P1
TASK_ID    = TASK-20260816-006
COMMIT     = 4f01bad（5 文件，582 insertions）
BRANCH     = feature/gainode-v3-serial-development
```

5 文件：
- `.project-ai/tasks/TASK-20260816-006/{requirement,design,acceptance,decision_request}.md`
- `0.5代码/gainode后端/gainode/sql/MACHINE_CONTRACT_AFFILIATE_AGENT_P1_FREEZE.md`

## 审核要点（逐项验证，给出 PASS / CHANGES_REQUIRED）

1. **对象完整性**：三对象（Agent/Referral/AgentEarning）字段候选表是否完整，且与 V1.x `member_user_team` 实际字段对应关系正确（核对 `_existing_prod/gainode_api/library/model/member/UserTeamModel.php`）。
2. **字段标记正确性**：每字段 SOURCE_CONFIRMED / OWNER_DECISION_REQUIRED 标注是否准确，无遗漏、无误标。
3. **Decision Matrix 覆盖**：是否覆盖 9 维度（状态/层级/重复归属/解绑/确认时点/预算/回滚/税务/PII），每项含 OPTION_A/B + RECOMMENDED_OPTION + RISK。
4. **不建表约束**：Owner 未签 → 三对象无 DDL/Model/DAO/Service，状态机 FAIL_CLOSED（DDL_TABLE_COUNT_DELTA=0）。
5. **不继承 V1.x 佣金语义**：invite_income_money/team_income_money/reward 等旧字段是否明确不迁移。
6. **无自创状态/角色**：三对象 enum 全部候选（未冻结），触发者仅用 05 §8 已有角色。
7. **P0 奖励关闭**：增长奖励写路径 fail-closed，禁用户本金/退款/Prediction 结算支付（02 §12）。
8. **一致性**：decision_request.md D1~D11 与 design.md Decision Matrix 逐项一致；Freeze 文档 enum 与 RECOMMENDED_OPTION 一致。

## 证据要求

- 每项结论必须引用具体文件行/段落作为证据。
- 发现缺陷须标注严重级（BLOCKING / P2 / P3）+ 缺陷 ID。
- 不修改任何文件，仅输出审核报告。

## 参考权威契约

- `Gainode_Development_Ready_V6.1_Latest/05_DATA_STATE_PERMISSION_API_CONTRACT.md`（§3/§4/§8/§11）
- `02_ECONOMIC_MODEL_AND_BUSINESS_RULES.md`（§12/§13/§14）
- `06_PARAMETER_DICTIONARY.md`（§9）
- `07_DEVELOPMENT_AND_ACCEPTANCE.md`（S01-P07）
- 前置冻结：MC1/MC2/2B-1/2B-2
