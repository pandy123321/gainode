# S01-P07 已知限制（Known Limitations）

## 1. Owner 未签（核心限制）

三对象（Agent/Referral/AgentEarning）status enum、层级深度、预算来源、税务合规、PII 等 11 项（D1~D11）均 **OWNER_DECISION_REQUIRED**。未签前：

```text
CONTRACT_GAP = YES（不建表不建 Service）
STATUS_MACHINE = FAIL_CLOSED（无合法转移）
BUDGET_SOURCE = NULL（禁发放）
```

## 2. 源文档歧义

- **D5「邀请关系可以共用」**（02 §12）语义模糊：可能指「邀请码可多人使用」或「一 invitee 多 inviter」。已列入 D5 请 Owner 澄清，未擅自假设。默认按「一 invitee 唯一 inviter」理解（对齐 V1.x `parent_id` 单值）。

## 3. V1.x 语义边界

- V1.x `member_user_team` 混有「关系/团队统计/佣金金额」。V6.1 拆三对象，**旧佣金字段（invite_income_money/team_income_money/reward）不迁移、不继承**。
- V1.x `parent_level`/`parent_path` 仅作字段候选来源，其语义（最大深度/路径结构）由 D4 裁决，不自动沿用旧逻辑。

## 4. 团队池（team_pool）暂缓

- 06 §9 的 `team_pool p3~p6` 暗示更深层级（>2 代）。本快照 D4 推荐先冻结 2 代（first_gen/second_gen），team_pool 更深层级属 STAGE-02 单独决策，不纳入本包。

## 5. 依赖合同未冻结

- reversal（D9）依赖 2B-1 的 CorrectionCase/RefundCase 合同（候选，未 FROZEN）。
- 确认时点（D7）依赖 02 §13 收入确认 + Settlement 合同（MC2 候选）。
- 本包不消费这些未冻结合同，仅登记为决策依赖。

## 6. 工程边界

- 本包无代码、无 DDL、无测试（合同盘点包）。
- `.gitignore` 忽略 `0.5代码/gainode后端/`，freeze 文档经 `git add -f` 强制跟踪（与既有 S01-P03/P05/P06 文件同机制）。
